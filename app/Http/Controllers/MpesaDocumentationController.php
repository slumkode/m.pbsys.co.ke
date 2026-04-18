<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class MpesaDocumentationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'documentation', 'view');
        $shortcodes = $this->accessibleShortcodeQuery($authUser)
            ->with('service')
            ->orderBy('shortcode')
            ->get();
        $environmentKey = config('app.env') === 'production' ? 'production' : 'development';
        $environmentLabel = $environmentKey === 'production' ? 'Production' : 'Sandbox';
        $mpesaLinks = config('mpesa.'.$environmentKey, []);

        return view('admin.modules.mpesa-documentation', $this->baseViewData($request, [
            'environmentLabel' => $environmentLabel,
            'environmentKey' => $environmentKey,
            'internalEndpoint' => url('/api/checkout'),
            'mpesaLinks' => [
                'token_link' => $mpesaLinks['token_link'] ?? null,
                'checkout_processlink' => $mpesaLinks['checkout_processlink'] ?? null,
                'checkout_querylink' => $mpesaLinks['checkout_querylink'] ?? null,
                'c2b_regiterUrl' => $mpesaLinks['c2b_regiterUrl'] ?? null,
                'c2b_transactionUrl' => $mpesaLinks['c2b_transactionUrl'] ?? null,
            ],
            'callbackEndpoints' => [
                'stk_request_callback' => config('mpesa.checkout_rcallbackurl'),
                'stk_query_callback' => config('mpesa.checkout_qcallbackurl'),
                'c2b_validation' => config('mpesa.c2b_validationUrl'),
                'c2b_confirmation' => config('mpesa.c2b_confirmationUrl'),
                'reversal_result' => config('mpesa.reversal_resultUrl'),
                'account_balance_result' => config('mpesa.balance_resultUrl'),
                'transaction_status_result' => config('mpesa.transtat_resultURL'),
            ],
            'quickStartSteps' => $this->quickStartSteps(),
            'apiRequestFields' => $this->apiRequestFields(),
            'shortcodeFields' => $this->shortcodeFields(),
            'serviceFields' => $this->serviceFields(),
            'responseExamples' => $this->responseExamples(),
            'sandboxCatalog' => $this->sandboxCatalog($shortcodes),
            'shortcodes' => $shortcodes,
        ]));
    }

    public function preview(Request $request)
    {
        $this->requireActionPermission($request, 'documentation', 'view');

        $validatedData = $request->validate([
            'shortcode_id' => ['required', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'msisdn' => ['required', 'string', 'min:9', 'max:20'],
            'account' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:100'],
        ]);

        $authUser = $request->user();
        $shortcode = $this->resolveAccessibleShortcode($authUser, (int) $validatedData['shortcode_id']);
        $service = null;

        if (! empty($validatedData['service_id']))
            {
                $service = Service::where('shortcode_id', $shortcode->id)
                    ->where('id', (int) $validatedData['service_id'])
                    ->first();

                if (! $service)
                    {
                        return response()->json([
                            'message' => 'Validation failed.',
                            'errors' => [
                                'service_id' => ['Please choose a service linked to the selected shortcode.'],
                            ],
                        ], 422);
                    }
            }
        else
            {
                $service = Service::where('shortcode_id', $shortcode->id)->orderBy('service_name')->first();
            }

        $amount = (float) $validatedData['amount'];
        $timestamp = date('YmdHis');
        $originalMsisdn = trim($validatedData['msisdn']);
        $normalizedMsisdn = $this->normalizeMsisdn($originalMsisdn);
        $accountReference = trim($validatedData['account']);
        $servicePrefix = $service && $service->prefix ? trim($service->prefix) : '';
        $prefixedAccountReference = $this->prefixedAccountReference($accountReference, $servicePrefix);
        $callbackUrls = $service ? array_values(array_filter(array_map('trim', explode(',', (string) $service->callback_url)))) : [];
        $warnings = [];

        if (empty($shortcode->group))
            {
                $warnings[] = 'This shortcode does not have a group value. The internal checkout API uses the group field to select credentials.';
            }

        if (! $shortcode->status)
            {
                $warnings[] = 'C2B notification registration is not active yet for this shortcode.';
            }

        if (! $service)
            {
                $warnings[] = 'No service is linked to this shortcode yet, so downstream callbacks cannot be routed to a specific service.';
            }
        elseif (empty($callbackUrls))
            {
                $warnings[] = 'The selected service does not have a callback URL configured.';
            }

        if ($service && empty($service->verification_url))
            {
                $warnings[] = 'The selected service has no verification URL. C2B validation will auto-accept requests.';
            }

        return response()->json([
            'status' => true,
            'msg' => 'Sandbox preview generated successfully.',
            'header' => 'MPesa Sandbox',
            'preview' => [
                'credential_source' => [
                    'group' => $shortcode->group,
                    'shortcode' => (string) $shortcode->shortcode,
                    'shortcode_type' => $shortcode->shortcode_type,
                    'notification_status' => $shortcode->status ? 'Active' : 'Inactive',
                    'credentials_note' => 'Consumer key, consumer secret, and passkey are loaded server-side from the selected shortcode and are never exposed in this page.',
                ],
                'request_summary' => [
                    'environment' => config('app.env') === 'production' ? 'production' : 'sandbox',
                    'normalized_msisdn' => $normalizedMsisdn,
                    'account_reference' => $accountReference,
                    'prefixed_bill_ref' => $prefixedAccountReference,
                ],
                'internal_payload' => [
                    'group' => $shortcode->group,
                    'amount' => $amount,
                    'msisdn' => $originalMsisdn,
                    'account' => $accountReference,
                    'description' => trim($validatedData['description']),
                ],
                'internal_curl' => $this->buildInternalCurl([
                    'group' => $shortcode->group,
                    'amount' => $amount,
                    'msisdn' => $originalMsisdn,
                    'account' => $accountReference,
                    'description' => trim($validatedData['description']),
                ]),
                'daraja_payload' => [
                    'BusinessShortCode' => (string) $shortcode->shortcode,
                    'Password' => '[generated server-side from shortcode, passkey, and timestamp]',
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $normalizedMsisdn,
                    'PartyB' => (string) $shortcode->shortcode,
                    'PhoneNumber' => $normalizedMsisdn,
                    'CallBackURL' => config('mpesa.checkout_rcallbackurl'),
                    'AccountReference' => $accountReference,
                    'TransactionDesc' => trim($validatedData['description']),
                ],
                'service_routing' => [
                    'service_name' => $service ? $service->service_name : null,
                    'prefix' => $servicePrefix ?: null,
                    'verification_url' => $service ? $service->verification_url : null,
                    'callback_urls' => $callbackUrls,
                ],
                'callback_examples' => [
                    'api_checkout_success' => $this->responseExamples()['success'],
                    'stk_success_callback' => [
                        'Body' => [
                            'stkCallback' => [
                                'MerchantRequestID' => '29115-34620561-1',
                                'CheckoutRequestID' => 'ws_CO_090420262216000001',
                                'ResultCode' => 0,
                                'ResultDesc' => 'The service request is processed successfully.',
                                'CallbackMetadata' => [
                                    'Item' => [
                                        ['Name' => 'Amount', 'Value' => $amount],
                                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'SBX123TEST'],
                                        ['Name' => 'Balance', 'Value' => 0],
                                        ['Name' => 'TransactionDate', 'Value' => (int) date('YmdHis')],
                                        ['Name' => 'PhoneNumber', 'Value' => (int) $normalizedMsisdn],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'c2b_validation_request' => [
                        'transcode' => $prefixedAccountReference,
                        'amount' => $amount,
                    ],
                    'forwarded_service_callback' => [
                        'TransID' => 'SBX'.date('His').'XYZ',
                        'TransTime' => date('Y-m-d H:i:s'),
                        'TransAmount' => $amount,
                        'BusinessShortCode' => (string) $shortcode->shortcode,
                        'BillRefNumber' => $prefixedAccountReference,
                        'MSISDN' => $normalizedMsisdn,
                        'FirstName' => 'Test',
                        'MiddleName' => 'User',
                        'LastName' => 'Demo',
                    ],
                ],
                'warnings' => $warnings,
            ],
        ]);
    }

    protected function sandboxCatalog($shortcodes)
    {
        return $shortcodes->map(function ($shortcode) {
            return [
                'id' => (int) $shortcode->id,
                'shortcode' => (string) $shortcode->shortcode,
                'group' => (string) $shortcode->group,
                'type' => (string) $shortcode->shortcode_type,
                'status' => (bool) $shortcode->status,
                'status_label' => $shortcode->status ? 'Active' : 'Inactive',
                'services' => $shortcode->service->map(function ($service) {
                    return [
                        'id' => (int) $service->id,
                        'name' => (string) $service->service_name,
                        'prefix' => (string) $service->prefix,
                        'verification_url' => (string) $service->verification_url,
                        'callback_url' => (string) $service->callback_url,
                        'description' => trim(strip_tags((string) $service->service_description)),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    protected function buildInternalCurl(array $payload)
    {
        return "curl --request POST '".url('/api/checkout')."' \\\n"
            ."  --header 'Content-Type: application/json' \\\n"
            ."  --data '".json_encode($payload, JSON_UNESCAPED_SLASHES)."'";
    }

    protected function prefixedAccountReference($accountReference, $prefix)
    {
        if ($prefix === '')
            {
                return $accountReference;
            }

        if (stripos($accountReference, $prefix) === 0)
            {
                return $accountReference;
            }

        return $prefix.$accountReference;
    }

    protected function normalizeMsisdn($msisdn)
    {
        $digits = preg_replace('/\D+/', '', $msisdn);

        if (strpos($digits, '254') === 0)
            {
                return $digits;
            }

        return '254'.substr($digits, -9);
    }

    protected function quickStartSteps()
    {
        return [
            [
                'title' => 'Register a shortcode',
                'description' => 'Save the shortcode, group, shortcode type, and Daraja credentials in the Shortcode page.',
            ],
            [
                'title' => 'Enable notifications',
                'description' => 'Start notification registration so Safaricom can post C2B validation and confirmation callbacks to this portal.',
            ],
            [
                'title' => 'Map a service',
                'description' => 'Create a service with a prefix, optional verification URL, and one or more callback URLs to receive confirmed payments.',
            ],
            [
                'title' => 'Send checkout requests',
                'description' => 'Call the internal checkout endpoint using the shortcode group, then let the portal normalize the request and push it to MPesa.',
            ],
        ];
    }

    protected function apiRequestFields()
    {
        return [
            [
                'field' => 'group',
                'type' => 'string',
                'required' => 'Yes',
                'description' => 'The internal lookup key saved on the shortcode record. The API uses it to load the correct consumer key, consumer secret, passkey, and shortcode.',
                'example' => 'school-paybill',
            ],
            [
                'field' => 'amount',
                'type' => 'number',
                'required' => 'Yes',
                'description' => 'Transaction amount to be pushed to the customer handset.',
                'example' => '1500',
            ],
            [
                'field' => 'msisdn',
                'type' => 'string',
                'required' => 'Yes',
                'description' => 'Customer phone number. The app accepts local formats like 07XXXXXXXX and normalizes them to 2547XXXXXXXX.',
                'example' => '0712345678',
            ],
            [
                'field' => 'account',
                'type' => 'string',
                'required' => 'Yes',
                'description' => 'Account reference passed to MPesa as AccountReference and later used by the callback flow. Prefix-based services use this value to decide where to route payment notifications.',
                'example' => 'ADM10042',
            ],
            [
                'field' => 'description',
                'type' => 'string',
                'required' => 'Yes',
                'description' => 'Human-readable transaction description forwarded as TransactionDesc.',
                'example' => 'April school fees',
            ],
        ];
    }

    protected function shortcodeFields()
    {
        return [
            [
                'field' => 'shortcode',
                'description' => 'The paybill or till number registered with Safaricom.',
            ],
            [
                'field' => 'group',
                'description' => 'Internal alias used by the existing `/api/checkout` endpoint. It should be unique and easy for integrators to remember.',
            ],
            [
                'field' => 'type',
                'description' => 'Describes the shortcode category such as paybill or till.',
            ],
            [
                'field' => 'consumerkey',
                'description' => 'Daraja consumer key used to generate OAuth tokens server-side.',
            ],
            [
                'field' => 'consumersecret',
                'description' => 'Daraja consumer secret paired with the consumer key.',
            ],
            [
                'field' => 'passkey',
                'description' => 'STK passkey used to build the MPesa password together with the shortcode and timestamp.',
            ],
        ];
    }

    protected function serviceFields()
    {
        return [
            [
                'field' => 'service_name',
                'description' => 'Friendly label used across reports, transactions, and callback routing.',
            ],
            [
                'field' => 'prefix',
                'description' => 'Optional leading text inside the bill reference. The callback flow uses this prefix to match the incoming payment to a specific service.',
            ],
            [
                'field' => 'verification_url',
                'description' => 'Optional upstream endpoint called during C2B validation. If it is empty, the request is accepted automatically.',
            ],
            [
                'field' => 'callback_url',
                'description' => 'One or more downstream endpoints, comma-separated, that receive confirmed transaction JSON after the portal stores the payment.',
            ],
            [
                'field' => 'service_description',
                'description' => 'Useful operational note for staff and integrators describing what the service handles.',
            ],
        ];
    }

    protected function responseExamples()
    {
        return [
            'success' => [
                'MerchantRequestID' => '29115-34620561-1',
                'CheckoutRequestID' => 'ws_CO_090420262216000001',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ],
            'failure' => [
                'requestId' => 'f63d4a4c-portal-demo',
                'errorCode' => '400.002.02',
                'errorMessage' => 'Invalid shortcode group or missing credentials.',
            ],
        ];
    }
}
