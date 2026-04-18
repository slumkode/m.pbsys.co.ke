<?php
// file: config/mpesa.php
return [
            'checkout_rcallbackurl'     =>	env('APP_URL')."/api/requeststkcallback",
            'checkout_qcallbackurl'     =>	env('APP_URL')."/api/querystkcallback",
            'reversal_resultUrl'		=>	env('APP_URL')."/api/reversalcallback",
            'reversal_timeoutURL'	    =>	env('APP_URL')."/api/reversalcallback",
            'balance_timeoutUrl'		=>	env('APP_URL')."/api/accountbalballback",
            'balance_resultUrl'		    =>	env('APP_URL')."/api/accountbalcallback",
            'c2b_confirmationUrl'	    => 	env('APP_URL')."/api/c2bconfirmation",
            'c2b_validationUrl'		    => 	env('APP_URL')."/api/c2bvalidation",
            'transtat_resultURL'		=>	env('APP_URL')."/api/transstatcallback",
            'transtat_timeoutURL'	    =>	env('APP_URL')."/api/transstatcallback",
            'b2b_timeoutURL'			=>	env('APP_URL')."/api/b2bcallback",
            'b2b_resultURL'			    =>	env('APP_URL')."/api/b2bcallback",
            'b2c_timeoutURL'			=>	env('APP_URL')."/api/b2ccallback",
            'b2c_resultURL'			    =>	env('APP_URL')."/api/b2ccallback",
            'transaction_status'        =>  [
                                                'remarks'       => env('MPESA_TRANSACTION_STATUS_REMARKS', 'C2B notification enrichment'),
                                                'occasion'      => env('MPESA_TRANSACTION_STATUS_OCCASION', 'C2B notification enrichment'),
                                                'wait_seconds'  => (int) env('MPESA_TRANSACTION_STATUS_WAIT_SECONDS', 15),
                                            ],
            "production"                =>  [
                                                'token_link'                =>  'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
                                                'checkout_processlink'	    =>	'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
                                                'checkout_querylink'        =>	'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
                                                'reversal_link'			    =>	'https://api.safaricom.co.ke/mpesa/reversal/v1/request',
                                                'balance_link'			    =>	'https://api.safaricom.co.ke/mpesa/accountbalance/v1/query',
                                                // 'c2b_regiterUrl'            =>  'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl',
                                                'c2b_regiterUrl'            =>  'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl',
                                                'transtat_link'			    =>	'https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query',
                                                'b2b_link'				    => 	'https://api.safaricom.co.ke/mpesa/b2b/v1/paymentrequest',
                                                'b2c_link'				    =>	'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
                                                'cert'                      =>  app_path('Resource/Mpesa_public_cert.cer')

                                            ],

            "development"               =>  [
                                                'token_link'			=>	'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
                                                'checkout_processlink'	=>	'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
                                                'checkout_querylink'	=>	'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
                                                'reversal_link'			=>	'https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request',
                                                'balance_link'			=>	'https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query',
                                                'c2b_regiterUrl'		=>  'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl',
                                                'transtat_link'			=>	'https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query',
                                                'b2b_link'				=> 	'https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest',
                                                'b2c_link'				=>	'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
                                                'c2b_transactionUrl'    =>  'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate',
                                                'cert'                  =>  app_path('Resource/Mpesa_public_sandbox_cert.cer')
                                            ]
    ];
