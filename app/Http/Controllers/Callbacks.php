<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmail;
use App\Models\Phone_number;
use App\Models\Shortcode;
use App\Models\Transaction;
use App\Models\Service;
use App\Services\RabbitMqService;
use App\Traits\Access;
use App\Utils\Mpesa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Callbacks
    {
        use Access;
        public function processB2BRequestCallback()
            {
                $callbackJSONData 						=	file_get_contents('php://input');
                $callbackData 							=	json_decode($callbackJSONData)->Result;
                $resultCode 							=	$callbackData->ResultCode;
                $resultDesc 							=	$callbackData->ResultDesc;
                $originatorConversationID 				=	$callbackData->OriginatorConversationID;
                $conversationID 						=	$callbackData->ConversationID;
                $transactionID 							=	$callbackData->TransactionID;
                $transactionReceipt						=	$callbackData->ResultParameters->ResultParameter[0]->Value;
                $transactionAmount						=	$callbackData->ResultParameters->ResultParameter[1]->Value;
                $b2CWorkingAccountAvailableFunds		=	$callbackData->ResultParameters->ResultParameter[2]->Value;
                $b2CUtilityAccountAvailableFunds		=	$callbackData->ResultParameters->ResultParameter[3]->Value;
                $transactionCompletedDateTime			=	$callbackData->ResultParameters->ResultParameter[4]->Value;
                $receiverPartyPublicName				=	$callbackData->ResultParameters->ResultParameter[5]->Value;
                $B2CChargesPaidAccountAvailableFunds	=	$callbackData->ResultParameters->ResultParameter[6]->Value;
                $B2CRecipientIsRegisteredCustomer		=	$callbackData->ResultParameters->ResultParameter[7]->Value;

                $result=array(
                    "resultCode"							=>	$resultCode,
                    "resultDesc"							=>	$resultDesc,
                    "originatorConversationID"				=>	$originatorConversationID,
                    "conversationID"						=>	$conversationID,
                    "transactionID"							=>	$transactionID,
                    "transactionReceipt"					=>	$transactionReceipt,
                    "transactionAmount"						=>	$transactionAmount,
                    "b2CWorkingAccountAvailableFunds"		=>	$b2CWorkingAccountAvailableFunds,
                    "b2CUtilityAccountAvailableFunds"		=>	$b2CUtilityAccountAvailableFunds,
                    "transactionCompletedDateTime"			=>	$transactionCompletedDateTime,
                    "receiverPartyPublicName"				=>	$receiverPartyPublicName,
                    "B2CChargesPaidAccountAvailableFunds"	=>	$B2CChargesPaidAccountAvailableFunds,
                    "B2CRecipientIsRegisteredCustomer"		=>	$B2CRecipientIsRegisteredCustomer
                );


            }

        public function processB2CRequestCallback()
            {
                $callbackJSONData	 				=	file_get_contents('php://input');
                $callbackData 						= 	json_decode($callbackJSONData);
                $resultCode 						=  	$callbackData->Result->ResultCode;
                $resultDesc 						=	$callbackData->Result->ResultDesc;
                $originatorConversationID 			= 	$callbackData->Result->OriginatorConversationID;
                $conversationID 					=	$callbackData->Result->ConversationID;
                $transactionID 						=	$callbackData->Result->TransactionID;
                $initiatorAccountCurrentBalance 	= 	$callbackData->Result->ResultParameters->ResultParameter[0]->Value;
                $debitAccountCurrentBalance 		=	$callbackData->Result->ResultParameters->ResultParameter[1]->Value;
                $amount 							=	$callbackData->Result->ResultParameters->ResultParameter[2]->Value;
                $debitPartyAffectedAccountBalance	=	$callbackData->Result->ResultParameters->ResultParameter[3]->Value;
                $transCompletedTime 				=	$callbackData->Result->ResultParameters->ResultParameter[4]->Value;
                $debitPartyCharges 					= 	$callbackData->Result->ResultParameters->ResultParameter[5]->Value;
                $receiverPartyPublicName 			= 	$callbackData->Result->ResultParameters->ResultParameter[6]->Value;
                $currency							=	$callbackData->Result->ResultParameters->ResultParameter[7]->Value;

                $result=array(
                    "resultCode"						=>	$resultCode,
                    "resultDesc"						=>	$resultDesc,
                    "originatorConversationID"			=>	$originatorConversationID,
                    "conversationID"					=>	$conversationID,
                    "transactionID"						=>	$transactionID,
                    "initiatorAccountCurrentBalance"	=>	$initiatorAccountCurrentBalance,
                    "debitAccountCurrentBalance"		=>	$debitAccountCurrentBalance,
                    "amount"							=>	$amount,
                    "debitPartyAffectedAccountBalance"	=>	$debitPartyAffectedAccountBalance,
                    "transCompletedTime"				=>	$transCompletedTime,
                    "debitPartyCharges"					=>	$debitPartyCharges,
                    "receiverPartyPublicName"			=>	$receiverPartyPublicName,
                    "currency"							=>	$currency
                );



            }

        public function C2BRequestValidation()
            {

                $callbackJSONData 	=	file_get_contents('php://input');
                $this->logMpesaCallbackReceived('c2b_validation', $callbackJSONData);
                $callbackData 		=	json_decode($callbackJSONData);
                //Log::error('Validation: '.$callbackJSONData);
                $transactionType 	=	$callbackData->TransactionType;
                $transID 			=	$callbackData->TransID;
                $transTime 			=	$callbackData->TransTime;
                $transAmount 		=	$callbackData->TransAmount;
                $businessShortCode 	=	$callbackData->BusinessShortCode;
                $billRefNumber 		=	$callbackData->BillRefNumber;
                $invoiceNumber 		=	$callbackData->InvoiceNumber;
                $orgAccountBalance 	= 	$callbackData->OrgAccountBalance;
                $thirdPartyTransID 	=	$callbackData->ThirdPartyTransID;
                $MSISDN 			=	$callbackData->MSISDN;
                $firstName 			=	$callbackData->FirstName;
                $middleName 		=	$callbackData->MiddleName;
                $lastName 			=	$callbackData->LastName;

                $result =   array(
                                    "transTime"			=>	$transTime,
                                    "transAmount"		=>	$transAmount,
                                    "businessShortCode"	=>	$businessShortCode,
                                    "billRefNumber"		=>	$billRefNumber,
                                    "invoiceNumber"		=>	$invoiceNumber,
                                    "orgAccountBalance"	=>	$orgAccountBalance,
                                    "thirdPartyTransID"	=>	$thirdPartyTransID,
                                    "MSISDN"			=>	$MSISDN,
                                    "firstName"			=>	$firstName,
                                    "lastName"			=>	$lastName,
                                    "middleName"		=>	$middleName,
                                    "transID"			=>	$transID,
                                    "transactionType"	=>	$transactionType
                                );

                $callback = Service::where('prefix',Access::getprefix($result["billRefNumber"]) )
                                    ->where('shortcode',$result["businessShortCode"])
                                    ->first()
                                    ->validation_url;
                if($callback != TRUE)
                    {
                        return array("ResultCode"=>0,"ResultDesc"=>"Accepted");
                    }
                else
                    {
                        $check = $this->curl_post($callback,['transcode'=>$result["billRefNumber"],"amount"=>$result["orgAccountBalance"]]);
                        if($check->status)
                            {
                                return array("ResultCode"=>0,"ResultDesc"=>"Accepted");
                            }
                        else
                            {
                                return	array("ResultCode"=>'C2B00012',"ResultDesc"=>$check->message);
                            }
                    }


            }

        public function processC2BRequestConfirmation()
            {
                $callbackJSONData 	=	file_get_contents('php://input');
                $this->logMpesaCallbackReceived('c2b_confirmation', $callbackJSONData);
                $callbackData 		=	json_decode($callbackJSONData);

                if (! $callbackData) {
                    Log::error('Invalid M-Pesa C2B confirmation payload', ['payload' => $callbackJSONData]);

                    return array("ResultCode" => 1, "ResultDesc" => "Invalid payload");
                }

                $detail = [
                    "transid"       =>  $callbackData->TransID ?? null,
                    'msisdn'        =>  $callbackData->MSISDN ?? null,
                    'ref'           =>  $callbackData->TransID ?? null,
                    'amount'        =>  $callbackData->TransAmount ?? null,
                    'account'       =>  $callbackData->BillRefNumber ?? null,
                    'customer_name' =>  ucwords(trim(($callbackData->FirstName ?? '').' '.($callbackData->MiddleName ?? '').' '.($callbackData->LastName ?? ''))),
                    'shortcode'     =>  $callbackData->BusinessShortCode ?? null,
                    'trans_type'    =>  $callbackData->TransactionType ?? null,
                    'trans_time'    =>  $this->formatMpesaDate($callbackData->TransTime ?? null),
                    'firstname'     =>  $callbackData->FirstName ?? null,
                    "middlename"    =>  $callbackData->MiddleName ?? null,
                    "lastname"      =>  $callbackData->LastName ?? null,
                ];

                if (! $this->transactionStatusCredentialsConfiguredForShortcode($detail['shortcode'] ?? null)) {
                    Log::info('M-Pesa C2B notification stored directly because transaction status credentials are not configured.', [
                        'transid' => $detail['transid'] ?? null,
                        'shortcode' => $detail['shortcode'] ?? null,
                    ]);

                    $this->notification($detail);

                    return array("ResultCode" => 0, "ResultDesc" => "Accepted");
                }

                $queued = $this->queueC2BNotification($callbackJSONData, $detail);

                if (! $queued) {
                    // Preserve the transaction if RabbitMQ is temporarily unavailable.
                    $this->notification($detail);
                }

                return array("ResultCode" => 0, "ResultDesc" => "Accepted");
            }

        public function processAccountBalanceRequestCallback()
            {
                $callbackJSONData=file_get_contents('php://input');
                $callbackData=json_decode($callbackJSONData);
                $resultType=$callbackData->Result->ResultType;
                $resultCode=$callbackData->Result->ResultCode;
                $resultDesc=$callbackData->Result->ResultDesc;
                $originatorConversationID=$callbackData->Result->OriginatorConversationID;
                $conversationID=$callbackData->Result->ConversationID;
                $transactionID=$callbackData->Result->TransactionID;
                $accountBalance=$callbackData->Result->ResultParameters->ResultParameter[0]->Value;
                $BOCompletedTime=$callbackData->Result->ResultParameters->ResultParameter[1]->Value;

                $result=array(
                    "resultDesc"                  =>$resultDesc,
                    "resultCode"                  =>$resultCode,
                    "originatorConversationID"    =>$originatorConversationID,
                    "conversationID"              =>$conversationID,
                    "transactionID"               =>$transactionID,
                    "accountBalance"              =>$accountBalance,
                    "BOCompletedTime"             =>$BOCompletedTime,
                    "resultType"                  =>$resultType
                );
            }

        public function processReversalRequestCallBack()
            {

                $callbackJSONData=file_get_contents('php://input');

                $callbackData = json_decode($callbackJSONData);
                $resultType=$callbackData->Result->ResultType;
                $resultCode=$callbackData->Result->ResultCode;
                $resultDesc=$callbackData->Result->ResultDesc;
                $originatorConversationID=$callbackData->Result->OriginatorConversationID;
                $conversationID=$callbackData->Result->ConversationID;
                $transactionID=$callbackData->Result->TransactionID;

                $result=array(
                    "resultType"                  =>$resultType,
                    "resultCode"                  =>$resultCode,
                    "resultDesc"                  =>$resultDesc,
                    "conversationID"              =>$conversationID,
                    "transactionID"               =>$transactionID,
                    "originatorConversationID"    =>$originatorConversationID
                );


            }

        public function processSTKPushRequestCallback()
            {
                $callbackJSONData   =   file_get_contents('php://input');
                $callbackData       =   json_decode($callbackJSONData)->Body;
                $resultCode         =   $callbackData->stkCallback->ResultCode;
                $resultDesc         =   $callbackData->stkCallback->ResultDesc;
                $merchantRequestID  =   $callbackData->stkCallback->MerchantRequestID;
                $checkoutRequestID  =   $callbackData->stkCallback->CheckoutRequestID;
                $amount             =   $callbackData->stkCallback->CallbackMetadata->Item[0]->Value;
                $mpesaReceiptNumber =   $callbackData->stkCallback->CallbackMetadata->Item[1]->Value;
                $balance            =   $callbackData->stkCallback->CallbackMetadata->Item[2]->Value;
                $transactionDate    =   $callbackData->stkCallback->CallbackMetadata->Item[3]->Value;
                $phoneNumber        =   $callbackData->stkCallback->CallbackMetadata->Item[4]->Value;

                $result = array(
                                    "resultDesc"            =>  $resultDesc,
                                    "resultCode"            =>  $resultCode,
                                    "merchantRequestID"     =>  $merchantRequestID,
                                    "checkoutRequestID"     =>  $checkoutRequestID,
                                    "amount"                =>  $amount,
                                    "mpesaReceiptNumber"    =>  $mpesaReceiptNumber,
                                    "balance"               =>  $balance,
                                    "transactionDate"       =>  $transactionDate,
                                    "phoneNumber"           =>  $phoneNumber
                                );


            }

        public function processSTKPushQueryRequestCallback()
            {
                $callbackJSONData 		=	file_get_contents('php://input');
                $callbackData 			=	json_decode($callbackJSONData);
                $responseCode 			=	$callbackData->ResponseCode;
                $responseDescription 	=	$callbackData->ResponseDescription;
                $merchantRequestID 		=	$callbackData->MerchantRequestID;
                $checkoutRequestID 		=	$callbackData->CheckoutRequestID;
                $resultCode 			=	$callbackData->ResultCode;
                $resultDesc 			=	$callbackData->ResultDesc;

                $result=array(
                                "resultCode" 			=>	$resultCode,
                                "responseDescription" 	=>	$responseDescription,
                                "responseCode" 			=>	$responseCode,
                                "merchantRequestID" 	=>	$merchantRequestID,
                                "checkoutRequestID" 	=> 	$checkoutRequestID,
                                "resultDesc" 			=>	$resultDesc
                            );


            }

        public function processTransactionStatusRequestCallback()
            {
                $callbackJSONData           =   file_get_contents('php://input');
                $this->logMpesaCallbackReceived('transaction_status', $callbackJSONData);
                $callbackData               =   json_decode($callbackJSONData);
                if (! $callbackData || ! isset($callbackData->Result)) {
                    Log::error('Invalid M-Pesa transaction status payload', ['payload' => $callbackJSONData]);

                    return array("ResultCode" => 1, "ResultDesc" => "Invalid payload");
                }

                $parameters = $this->resultParametersByKey($callbackData->Result->ResultParameters->ResultParameter ?? []);
                $result = array(
                                "resultCode"                =>  $callbackData->Result->ResultCode ?? null,
                                "resultDesc"                =>  $callbackData->Result->ResultDesc ?? null,
                                "originatorConversationID"  =>  $callbackData->Result->OriginatorConversationID ?? null,
                                "conversationID"            =>  $callbackData->Result->ConversationID ?? null,
                                "transactionID"             =>  $callbackData->Result->TransactionID ?? null,
                                "ReceiptNo"                 =>  $parameters['ReceiptNo'] ?? $callbackData->Result->TransactionID ?? null,
                                "ConversationID"            =>  $parameters['ConversationID'] ?? null,
                                "FinalisedTime"             =>  $parameters['FinalisedTime'] ?? null,
                                "Amount"                    =>  $parameters['Amount'] ?? null,
                                "TransactionStatus"         =>  $parameters['TransactionStatus'] ?? null,
                                "ReasonType"                =>  $parameters['ReasonType'] ?? null,
                                "TransactionReason"         =>  $parameters['TransactionReason'] ?? null,
                                "DebitPartyCharges"         =>  $parameters['DebitPartyCharges'] ?? null,
                                "DebitAccountType"          =>  $parameters['DebitAccountType'] ?? null,
                                "InitiatedTime"             =>  $parameters['InitiatedTime'] ?? null,
                                "OriginatorConversationID"  =>  $parameters['OriginatorConversationID'] ?? null,
                                "CreditPartyName"           =>  $parameters['CreditPartyName'] ?? null,
                                "DebitPartyName"            =>  $parameters['DebitPartyName'] ?? null
                            );

                $this->queueTransactionStatusResult($callbackJSONData, $result);
                $this->updateTransactionFromStatusResult($result);

                return array("ResultCode" => 0, "ResultDesc" => "Accepted");
            }

        public function notification($detail)
            {
                try
                    {
                        $moneyfromnumber    =   $detail['msisdn'];
                        $ref                =   $detail['ref'];
                        $amount             =   $detail['amount'];
                        $account            =   $detail['account'];
                        $channel            =   $detail['trans_type'];
                        $shortcode          =   Shortcode::where('shortcode',$detail['shortcode'])
                                                         ->first();
                        if (! $shortcode) {
                            Log::error('M-Pesa notification skipped because shortcode was not found', ['shortcode' => $detail['shortcode'] ?? null, 'transid' => $detail['transid'] ?? null]);

                            return false;
                        }

                        $data               =   array(
                                                        "shortcode_id"      =>  $shortcode->id,
                                                        "msisdn"            =>  $moneyfromnumber,
                                                        "amount"            =>  $amount,
                                                        "account"           =>  $account,
                                                        "channel"           =>  $channel,
                                                        "transaction_code"  =>  $ref,
                                                        "trans_time"        =>  $detail["trans_time"]
                                                    );
                        list($service, $matchedPrefix, $prefixCandidates) = $this->resolveNotificationService($shortcode, $account);

                        if(is_null($service))
                            {
                                $service            =   Service::where("shortcode_id",$shortcode->id)
                                                                ->where(function ($query) {
                                                                    $query->whereNull('prefix')
                                                                        ->orWhere('prefix', '');
                                                                })
                                                                ->first();
                            }

                        if (! $service) {
                            Log::error('M-Pesa notification skipped because service was not found', ['shortcode' => $detail['shortcode'] ?? null, 'account' => $account, 'transid' => $detail['transid'] ?? null]);

                            return false;
                        }

                        $data['type']       =   $service->service_name;

                        $param              =   array(
                                                        "TransID"           =>  $detail["transid"],
                                                        "TransTime"         =>  $detail["trans_time"],
                                                        "TransAmount"       =>  $detail["amount"],
                                                        "BusinessShortCode" =>  $detail['shortcode'],
                                                        "BillRefNumber"     =>  $detail["account"],
                                                        "MSISDN"            =>  $detail["msisdn"],
                                                        "FirstName"         =>  $detail["firstname"],
                                                        "MiddleName"        =>  $detail["middlename"],
                                                        "LastName"          =>  $detail["lastname"]
                                                    );
                        $url                =   $this->callbackUrls($service->callback_url);

                        Log::info('M-Pesa notification matched service', [
                            'transid' => $detail['transid'] ?? null,
                            'shortcode' => $detail['shortcode'] ?? null,
                            'account' => $account,
                            'matched_prefix' => $matchedPrefix,
                            'prefix_candidates' => $prefixCandidates,
                            'service_id' => $service->id,
                            'service_name' => $service->service_name,
                            'callback_urls' => $url,
                        ]);

                        $trans                      =   Transaction::where('transaction_code', $data["transaction_code"])->first();

                        if (! $trans) {
                            $trans                  =   new Transaction();
                        }

                        $trans->shortcode_id        =   $data["shortcode_id"];
                        $trans->msisdn              =   $data["msisdn"];
                        $trans->amount              =   $data["amount"];
                        $trans->account             =   $data["account"];
                        $trans->channel             =   $data["channel"];
                        $trans->transaction_code    =   $data["transaction_code"];
                        $trans->trans_time          =   $data["trans_time"];
                        $trans->type                =   $data["type"];
                        $trans->source              =   "MPESA";
                        if (Schema::hasColumn('transactions', 'customer_name')) {
                            $trans->customer_name   =   $detail["customer_name"];
                        }
                        $trans->save();
                        $this->curl_function($url, json_encode($param), [
                            'transid' => $detail['transid'] ?? null,
                            'shortcode' => $detail['shortcode'] ?? null,
                            'service_id' => $service->id,
                            'service_name' => $service->service_name,
                        ]);
                        $this->add_numbers($detail["customer_name"],$data["msisdn"],$detail['shortcode']);
                        //$this->emailnotify($detail);
                    }
                catch(\Throwable $e)
                    {
                        Log::error($e->getMessage());
                    }
            }

        protected function resolveNotificationService(Shortcode $shortcode, $account)
            {
                $prefixCandidates = $this->servicePrefixCandidates($account);

                foreach ($prefixCandidates as $prefix) {
                    $service = Service::where("shortcode_id", $shortcode->id)
                        ->where('prefix', $prefix)
                        ->first();

                    if ($service) {
                        return [$service, $prefix, $prefixCandidates];
                    }
                }

                return [null, null, $prefixCandidates];
            }

        protected function servicePrefixCandidates($account)
            {
                $account = trim((string) $account);
                $candidates = [];

                if ($account !== '') {
                    $candidates[] = Access::getprefix($account);

                    if (preg_match('/^([A-Za-z0-9]+)[\-_\/\s]/', $account, $matches)) {
                        $candidates[] = $matches[1];
                    }
                }

                return array_values(array_unique(array_filter($candidates, function ($candidate) {
                    return trim((string) $candidate) !== '';
                })));
            }

        protected function queueC2BNotification($rawPayload, array $detail)
            {
                try
                    {
                        app(RabbitMqService::class)->publish(config('rabbitmq.queues.c2b_notifications'), [
                            'source' => 'mpesa_c2b_confirmation',
                            'received_at' => date('Y-m-d H:i:s'),
                            'detail' => $detail,
                            'raw' => json_decode($rawPayload, true) ?: $rawPayload,
                        ]);

                        Log::info('M-Pesa C2B notification queued', [
                            'queue' => config('rabbitmq.queues.c2b_notifications'),
                            'transid' => $detail['transid'] ?? null,
                            'shortcode' => $detail['shortcode'] ?? null,
                        ]);

                        return true;
                    }
                catch(\Throwable $e)
                    {
                        Log::error('M-Pesa C2B RabbitMQ publish failed; falling back to direct processing', [
                            'error' => $e->getMessage(),
                            'transid' => $detail['transid'] ?? null,
                            'shortcode' => $detail['shortcode'] ?? null,
                        ]);

                        return false;
                    }
            }

        protected function logMpesaCallbackReceived($callbackName, $rawPayload)
            {
                $decoded = json_decode($rawPayload, true);
                $context = [
                    'callback' => $callbackName,
                    'ip' => request()->ip(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'trans_id' => $decoded['TransID'] ?? data_get($decoded, 'Result.TransactionID'),
                    'shortcode' => $decoded['BusinessShortCode'] ?? null,
                    'amount' => $decoded['TransAmount'] ?? null,
                    'account' => $decoded['BillRefNumber'] ?? null,
                    'payload' => $decoded ?: $rawPayload,
                ];

                Log::info('M-Pesa callback received', $context);
            }

        protected function transactionStatusCredentialsConfiguredForShortcode($shortcodeValue)
            {
                if (! $shortcodeValue || ! Schema::hasColumn('shortcodes', 'transaction_status_initiator')) {
                    return false;
                }

                $shortcode = Shortcode::where('shortcode', $shortcodeValue)->first();

                if (! $shortcode) {
                    return false;
                }

                return $this->configValueIsFilled($shortcode->transaction_status_initiator)
                    && $this->configValueIsFilled($shortcode->transaction_status_credential);
            }

        protected function configValueIsFilled($value)
            {
                $value = trim((string) $value);

                return $value !== '' && strtolower($value) !== 'null';
            }

        protected function queueTransactionStatusResult($rawPayload, array $result)
            {
                try
                    {
                        app(RabbitMqService::class)->publish(config('rabbitmq.queues.transaction_status_results'), [
                            'source' => 'mpesa_transaction_status',
                            'received_at' => date('Y-m-d H:i:s'),
                            'result' => $result,
                            'raw' => json_decode($rawPayload, true) ?: $rawPayload,
                        ]);

                        Log::info('M-Pesa transaction status result queued', [
                            'queue' => config('rabbitmq.queues.transaction_status_results'),
                            'receipt' => $result['ReceiptNo'] ?? null,
                        ]);

                        return true;
                    }
                catch(\Throwable $e)
                    {
                        Log::error('M-Pesa transaction status RabbitMQ publish failed', [
                            'error' => $e->getMessage(),
                            'receipt' => $result['ReceiptNo'] ?? null,
                        ]);

                        return false;
                    }
            }

        protected function resultParametersByKey($parameters)
            {
                $result = [];

                foreach ((array) $parameters as $parameter)
                    {
                        $key = $parameter->Key ?? $parameter->Name ?? null;

                        if ($key) {
                            $result[$key] = $parameter->Value ?? null;
                        }
                    }

                return $result;
            }

        protected function updateTransactionFromStatusResult(array $result)
            {
                $receipt = $result['ReceiptNo'] ?? null;

                if (! $receipt) {
                    return false;
                }

                $transaction = Transaction::where('transaction_code', $receipt)->first();

                if (! $transaction) {
                    Log::info('M-Pesa transaction status result received before local transaction insert', ['receipt' => $receipt]);

                    return false;
                }

                $debitParty = $this->parseDebitPartyName($result['DebitPartyName'] ?? null);
                $updated = false;

                if (! empty($debitParty['msisdn'])) {
                    $transaction->msisdn = $debitParty['msisdn'];
                    $updated = true;
                }

                if (Schema::hasColumn('transactions', 'customer_name') && ! empty($debitParty['name'])) {
                    $transaction->customer_name = $debitParty['name'];
                    $updated = true;
                }

                if (! empty($result['Amount'])) {
                    $transaction->amount = $result['Amount'];
                    $updated = true;
                }

                if (! empty($result['FinalisedTime'])) {
                    $transaction->trans_time = $this->formatMpesaDate($result['FinalisedTime']);
                    $updated = true;
                }

                if ($updated) {
                    $transaction->save();

                    if (! empty($debitParty['name']) && ! empty($debitParty['msisdn'])) {
                        $shortcode = Shortcode::find($transaction->shortcode_id);

                        if ($shortcode) {
                            $this->add_numbers($debitParty['name'], $debitParty['msisdn'], $shortcode->shortcode);
                        }
                    }
                }

                return $updated;
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

        public function emailnotify($dt)
            {

                $eto            =   "info@taifamobile.co.ke";
                $ecc            =   ["timwasilwa2013@gmail.com","caydee209@gmail.com"];
                $esub           =   "MPESA INSTANT PAYMENT NOTIFICATION - PAYBILL NUMBER ".$dt['shortcode'];
                $data           =   json_decode(json_encode($dt));
                $data->email    =   $eto;
                $data->subject  =   $esub;
                $data->cc       =   $ecc;
                SendEmail::dispatch($data);
                return TRUE;
            }

        public function postdata($url,$data)
            {
                $client = new \GuzzleHttp\Client();
                foreach($url as $link)
                    {
                        $request = $client->post($link,  ['form_params'=>$data]);

                    }
            }

        protected function callbackUrls($callbackUrl)
            {
                $urls = preg_split('/\s*,\s*/', (string) $callbackUrl, -1, PREG_SPLIT_NO_EMPTY);

                return array_values(array_filter(array_map('trim', $urls)));
            }

        public function curl_function($url, $param, array $context = [])
            {
                $urls = is_array($url) ? $url : [$url];
                $sent = false;

                foreach($urls as $link)
                    {
                        $link = trim((string) $link);

                        if ($link === '') {
                            continue;
                        }

                        $ch = curl_init($link);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
                        $result = curl_exec($ch);
                        $curlError = curl_error($ch);
                        $curlErrno = curl_errno($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        $sent = true;

                        $logContext = array_merge($context, [
                            'url' => $link,
                            'http_code' => $httpCode,
                            'curl_errno' => $curlErrno,
                            'curl_error' => $curlError,
                            'response' => is_string($result) ? substr($result, 0, 1000) : $result,
                        ]);

                        if ($curlErrno || $httpCode < 200 || $httpCode >= 300) {
                            Log::warning('M-Pesa service callback forwarding failed or returned non-success status.', $logContext);
                        } else {
                            Log::info('M-Pesa service callback forwarded', $logContext);
                        }
                    }

                if (! $sent) {
                    Log::warning('M-Pesa service callback was not forwarded because no callback URL is configured.', $context);
                }

                return $sent;
            }

        public function add_numbers($name,$phone,$shortcode)
            {
                Phone_number::firstOrCreate(['phone_number'=>$phone],["customer_name"=>$name,"shortcode"=>$shortcode]);
                return TRUE;
            }
    }
