<?php


namespace app\Traits;


trait Mpesa_trait
    {
        public $reversal_resultUrl;
        public $reversal_timeoutURL;
        public $checkout_rcallbackurl;
        public $checkout_qcallbackurl;
        public $balance_timeoutUrl;
        public $balance_resultUrl;
        public $c2b_confirmationUrl;
        public $c2b_validationUrl;
        public $transtat_resultURL;
        public $transtat_timeoutURL;
        public $b2b_timeoutURL;
        public $b2b_resultURL;
        public $b2c_timeoutURL;
        public $b2c_resultURL;
        public $token_link;
        public $checkout_processlink;
        public $checkout_querylink;
        public $reversal_link;
        public $balance_link;
        public $c2b_regiterUrl;
        public $transtat_link;
        public $b2b_link;
        public $b2c_link;
        public $cert;
        public function development()
            {
                $this->checkout_rcallbackurl    =	config('mpesa.checkout_rcallbackurl', url("api/requeststkcallback"));
                $this->checkout_qcallbackurl    =	config('mpesa.checkout_qcallbackurl', url("api/querystkcallback"));
                $this->reversal_resultUrl		=	config('mpesa.reversal_resultUrl', url("api/reversalcallback"));
                $this->reversal_timeoutURL	    =	config('mpesa.reversal_timeoutURL', url("api/reversalcallback"));
                $this->balance_timeoutUrl		=	config('mpesa.balance_timeoutUrl', url("api/accountbalballback"));
                $this->balance_resultUrl        =	config('mpesa.balance_resultUrl', url("api/accountbalcallback"));
                $this->c2b_confirmationUrl	    = 	config('mpesa.c2b_confirmationUrl', url("api/c2bconfirmation"));
                $this->c2b_validationUrl        = 	config('mpesa.c2b_validationUrl', url("api/c2bvalidation"));
                $this->transtat_resultURL		=	config('mpesa.transtat_resultURL', url("api/transstatcallback"));
                $this->transtat_timeoutURL	    =	config('mpesa.transtat_timeoutURL', url("api/transstatcallback"));
                $this->b2b_timeoutURL			=	config('mpesa.b2b_timeoutURL', url("api/b2bcallback"));
                $this->b2b_resultURL            =	config('mpesa.b2b_resultURL', url("api/b2bcallback"));
                $this->b2c_timeoutURL			=	config('mpesa.b2c_timeoutURL', url("api/b2ccallback"));
                $this->b2c_resultURL            =	config('mpesa.b2c_resultURL', url("api/b2ccallback"));
                $this->cert                     =   config('mpesa.development.cert', app_path('Resource/Mpesa_public_sandbox_cert.cer'));
                $this->token_link				=	config('mpesa.development.token_link', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
                $this->checkout_processlink     =	config('mpesa.development.checkout_processlink', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
                $this->checkout_querylink		=	config('mpesa.development.checkout_querylink', 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query');
                $this->reversal_link			=	config('mpesa.development.reversal_link', 'https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request');
                $this->balance_link             =	config('mpesa.development.balance_link', 'https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query');
                $this->c2b_regiterUrl			=  	config('mpesa.development.c2b_regiterUrl', 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl');
                $this->transtat_link			=	config('mpesa.development.transtat_link', 'https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query');
                $this->b2b_link                 = 	config('mpesa.development.b2b_link', 'https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest');
                $this->b2c_link                 =	config('mpesa.development.b2c_link', 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest');
                $this->c2b_transactionUrl       =   config('mpesa.development.c2b_transactionUrl', 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate');
                return $this;
            }
        public function production()
            {
                $this->checkout_rcallbackurl    =	config('mpesa.checkout_rcallbackurl', url("api/requeststkcallback"));
                $this->checkout_qcallbackurl    =	config('mpesa.checkout_qcallbackurl', url("api/querystkcallback"));
                $this->reversal_resultUrl		=	config('mpesa.reversal_resultUrl', url("api/reversalcallback"));
                $this->reversal_timeoutURL	    =	config('mpesa.reversal_timeoutURL', url("api/reversalcallback"));
                $this->balance_timeoutUrl		=	config('mpesa.balance_timeoutUrl', url("api/accountbalballback"));
                $this->balance_resultUrl        =	config('mpesa.balance_resultUrl', url("api/accountbalcallback"));
                $this->c2b_confirmationUrl	    = 	config('mpesa.c2b_confirmationUrl', url("api/c2bconfirmation"));
                $this->c2b_validationUrl        = 	config('mpesa.c2b_validationUrl', url("api/c2bvalidation"));
                $this->transtat_resultURL		=	config('mpesa.transtat_resultURL', url("api/transstatcallback"));
                $this->transtat_timeoutURL	    =	config('mpesa.transtat_timeoutURL', url("api/transstatcallback"));
                $this->b2b_timeoutURL			=	config('mpesa.b2b_timeoutURL', url("api/b2bcallback"));
                $this->b2b_resultURL            =	config('mpesa.b2b_resultURL', url("api/b2bcallback"));
                $this->b2c_timeoutURL			=	config('mpesa.b2c_timeoutURL', url("api/b2ccallback"));
                $this->b2c_resultURL            =	config('mpesa.b2c_resultURL', url("api/b2ccallback"));
                $this->cert                     =   config('mpesa.production.cert', app_path('Resource/Mpesa_public_cert.cer'));
                $this->token_link				=	config('mpesa.production.token_link', 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
                $this->checkout_processlink	    =	config('mpesa.production.checkout_processlink', 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
                $this->checkout_querylink		=	config('mpesa.production.checkout_querylink', 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query');
                $this->reversal_link			=	config('mpesa.production.reversal_link', 'https://api.safaricom.co.ke/mpesa/reversal/v1/request');
                $this->balance_link             =	config('mpesa.production.balance_link', 'https://api.safaricom.co.ke/mpesa/accountbalance/v1/query');
                $this->c2b_regiterUrl			=  	config('mpesa.production.c2b_regiterUrl', 'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl');
                $this->transtat_link			=	config('mpesa.production.transtat_link', 'https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query');
                $this->b2b_link				    = 	config('mpesa.production.b2b_link', 'https://api.safaricom.co.ke/mpesa/b2b/v1/paymentrequest');
                $this->b2c_link                 =	config('mpesa.production.b2c_link', 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest');
                return $this;
            }
    }
