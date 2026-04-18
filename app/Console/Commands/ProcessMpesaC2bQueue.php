<?php

namespace App\Console\Commands;

use App\Http\Controllers\Callbacks;
use App\Models\Shortcode;
use App\Services\RabbitMqService;
use App\Utils\Mpesa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMpesaC2bQueue extends Command
{
    protected $signature = 'mpesa:process-c2b-queue {--once : Process one available message and exit} {--sleep=3 : Seconds to wait when the queue is empty}';

    protected $description = 'Process queued M-Pesa C2B notifications from RabbitMQ.';

    public function handle()
    {
        $rabbit = app(RabbitMqService::class);

        if (! $rabbit->isAvailable()) {
            $this->error('RabbitMQ PHP client is not installed. Run composer require php-amqplib/php-amqplib:^3.7');

            return 1;
        }

        $queue = config('rabbitmq.queues.c2b_notifications');
        $this->info('Listening for M-Pesa C2B notifications on '.$queue);

        do {
            $message = $rabbit->pop($queue);

            if (! $message) {
                if ($this->option('once')) {
                    return 0;
                }

                sleep((int) $this->option('sleep'));
                continue;
            }

            try {
                $payload = json_decode($message->body, true);

                if (! is_array($payload) || ! isset($payload['detail'])) {
                    throw new \RuntimeException('Invalid queued C2B notification payload.');
                }

                $detail = $payload['detail'];
                $statusRequested = $this->requestTransactionStatus($detail);

                if ($statusRequested) {
                    $statusResult = $this->waitForTransactionStatusResult($rabbit, $detail['transid'] ?? null);

                    if ($statusResult) {
                        $detail = $this->mergeTransactionStatusIntoDetail($detail, $statusResult);
                    }
                }

                app(Callbacks::class)->notification($detail);
                $message->ack();

                $this->info('Processed C2B notification '.($detail['transid'] ?? '-'));
            } catch (\Throwable $e) {
                Log::error('M-Pesa C2B queue processing failed', [
                    'error' => $e->getMessage(),
                    'payload' => isset($message) ? $message->body : null,
                ]);

                $message->nack(false, true);

                if ($this->option('once')) {
                    $this->error($e->getMessage());

                    return 1;
                }

                sleep((int) $this->option('sleep'));
            }
        } while (true);
    }

    protected function requestTransactionStatus(array $detail)
    {
        $shortcode = Shortcode::where('shortcode', $detail['shortcode'] ?? null)->first();

        if (! $shortcode) {
            Log::warning('M-Pesa transaction status query skipped because shortcode was not found.', [
                'transid' => $detail['transid'] ?? null,
                'shortcode' => $detail['shortcode'] ?? null,
            ]);

            return null;
        }

        if (! $this->shortcodeTransactionStatusCredentialsConfigured($shortcode)) {
            Log::info('M-Pesa transaction status query bypassed because this shortcode has no transaction status credentials.', [
                'transid' => $detail['transid'] ?? null,
                'shortcode' => $detail['shortcode'] ?? null,
            ]);

            return null;
        }

        return app(Mpesa::class)->transactionstatus([
            'consumerkey' => $shortcode->consumerkey,
            'consumersecret' => $shortcode->consumersecret,
            'initiator' => $shortcode->transaction_status_initiator,
            'credential' => $shortcode->transaction_status_credential,
            'credential_is_encrypted' => (bool) ($shortcode->transaction_status_credential_encrypted ?? false),
            'transID' => $detail['transid'] ?? null,
            'partyA' => $shortcode->shortcode,
            'identifier' => $shortcode->transaction_status_identifier ?: 'shortcode',
            'remarks' => config('mpesa.transaction_status.remarks', 'C2B notification enrichment'),
            'occasion' => config('mpesa.transaction_status.occasion', 'C2B notification enrichment'),
        ]);
    }

    protected function shortcodeTransactionStatusCredentialsConfigured(Shortcode $shortcode)
    {
        return $this->configValueIsFilled($shortcode->transaction_status_initiator ?? null)
            && $this->configValueIsFilled($shortcode->transaction_status_credential ?? null);
    }

    protected function configValueIsFilled($value)
    {
        $value = trim((string) $value);

        return $value !== '' && strtolower($value) !== 'null';
    }

    protected function waitForTransactionStatusResult(RabbitMqService $rabbit, $receipt)
    {
        $waitSeconds = (int) config('mpesa.transaction_status.wait_seconds', 15);

        if (! $receipt || $waitSeconds <= 0) {
            return null;
        }

        $queue = config('rabbitmq.queues.transaction_status_results');
        $deadline = time() + $waitSeconds;
        $deferred = [];

        while (time() <= $deadline) {
            $message = $rabbit->pop($queue);

            if (! $message) {
                sleep(1);
                continue;
            }

            $payload = json_decode($message->body, true);
            $message->ack();

            if (! is_array($payload) || ! isset($payload['result'])) {
                continue;
            }

            $payloadReceipt = $payload['result']['ReceiptNo'] ?? $payload['result']['transactionID'] ?? null;

            if ($payloadReceipt === $receipt) {
                $this->republishDeferredStatusResults($rabbit, $deferred);

                return $payload['result'];
            }

            $deferred[] = $payload;
        }

        $this->republishDeferredStatusResults($rabbit, $deferred);

        return null;
    }

    protected function republishDeferredStatusResults(RabbitMqService $rabbit, array $payloads)
    {
        foreach ($payloads as $payload) {
            $rabbit->publish(config('rabbitmq.queues.transaction_status_results'), $payload);
        }
    }

    protected function mergeTransactionStatusIntoDetail(array $detail, array $statusResult)
    {
        $debitParty = $this->parseDebitPartyName($statusResult['DebitPartyName'] ?? null);

        if (! empty($debitParty['msisdn'])) {
            $detail['msisdn'] = $debitParty['msisdn'];
        }

        if (! empty($debitParty['name'])) {
            $detail['customer_name'] = $debitParty['name'];
            $detail['firstname'] = $debitParty['name'];
            $detail['middlename'] = null;
            $detail['lastname'] = null;
        }

        if (! empty($statusResult['Amount'])) {
            $detail['amount'] = $statusResult['Amount'];
        }

        if (! empty($statusResult['FinalisedTime'])) {
            $detail['trans_time'] = $this->formatMpesaDate($statusResult['FinalisedTime']);
        }

        return $detail;
    }

    protected function parseDebitPartyName($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['msisdn' => null, 'name' => null];
        }

        $parts = array_map('trim', explode('-', $value, 2));
        $msisdn = preg_replace('/\D+/', '', $parts[0] ?? '');
        $name = $parts[1] ?? null;

        if ($msisdn === '' || strlen($msisdn) < 9) {
            $msisdn = null;
            $name = $name ?: $value;
        }

        return [
            'msisdn' => $msisdn,
            'name' => $name ? ucwords(strtolower(trim($name))) : null,
        ];
    }

    protected function formatMpesaDate($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^\d{14}$/', $value)) {
            $date = \DateTime::createFromFormat('YmdHis', $value);

            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }
}
