<?php


namespace app\Traits;


trait Jenga_trait
    {
        public function development()
            {
                $this->account_enquiry_url           =  "";
                $this->country_code                  =  "";
                $this->company_name                  =  "";
                $this->account_id                    =  "";
                $this->account_balance_url           =  "";
                $this->privkey                       =  app_path("/Resource/privkey.pem");
                $this->mini_statement_url            =  "";
                $this->full_statement_url            =  "";
                $this->opening_closing_balance_url   =  "";
                $this->token_url                     =  "";
                $this->api_key                       =  "";
                $this->username                      =  "";
                $this->password                      =  "";
                $this->opening_closing_balance_url   =  "";
                return $this;
            }
        public function production()
            {
                $this->account_enquiry_url           =  "";
                $this->country_code                  =  "";
                $this->company_name                  =  "";
                $this->account_id                    =  "";
                $this->account_balance_url           =  "";
                $this->privkey                       =  app_path("/Resource/privkey.pem");
                $this->mini_statement_url            =  "";
                $this->full_statement_url            =  "";
                $this->opening_closing_balance_url   =  "";
                $this->token_url                     =  "";
                $this->api_key                       =  "";
                $this->username                      =  "";
                $this->password                      =  "";
                $this->opening_closing_balance_url   =  "";
                return $this;
            }
    }
