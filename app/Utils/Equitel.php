<?php
namespace App\Utils;

use app\Interfaces\Payments;
use app\Traits\Jenga_trait as conf;

class Equitel implements Payments
    {
    private $jenga;
    private $privatekey;
    private $plainText;
    use conf;
    public function __construct()
        {
            if(config('app.env') === 'production')
                {
                    $this->jenga   =   $this->production();
                }
            else
                {
                    $this->jenga   =   $this->development();
                }
            $this->privatekey = openssl_pkey_get_private($this->readkey($this->jenga->privekey));
            $this->plainText  = $this->jenga->country_code.$this->jenga->account_id;
        }
    public function generatetoken($request)
        {
            $curl 	= 	curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->jenga->token_url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$request['api_key']));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'username='.$this->jenga->username.'&password='.$this->jenga->password);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $curl_response = curl_exec($curl);
            curl_close($curl);
            return $curl_response;
        }

    public function readkey($key)
        {
            $fp         =   fopen($this->jenga->privekey,"r");
            $privKey    =   fread($fp,filesize($key));
            fclose($fp);
            return $privKey;
        }
    //------------------------------------------ ACCOUNT SERVICES -------------------------------
    public function get_account_info($request)
        {
            $auth		=   json_decode($this->generatetoken($request));
            $url		=   $this->jenga->account_enquiry_url;
            $token      =   $auth->access_token;
            openssl_sign($this->plainText, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
            $data = [
                        "countryCode"   => $request['country_code'],
                        "accountNumber" => $request['account_id']
                    ];

            return $this->curl_function($token, $signature, $url, $data, "GET");
        }

    public function get_account_balance($request)
        {
            $auth		= json_decode($this->generatetoken($request));
            $url		= $this->jenga->account_balance_url;
            $token      = $auth->access_token;
            openssl_sign($this->plainText, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
            $data = [
                        "countryCode"   => $request['country_code'],
                        "accountId"     => $request['account_id']
                    ];
            return $this->curl_function($token, $signature, $url, $data, "GET");
        }

    public function get_mini_statement($request)
        {
            $auth		=   json_decode($this->generatetoken($request));
            $url		=   $this->jenga->mini_statement_url;
            $token      = $auth->access_token;

            openssl_sign($this->plainText, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
            $data = [
                        "countryCode"   => $request['country_code'],
                        "accountNumber" => $request['account_id']
                    ];
            return $this->curl_function($token, $signature, $url, $data, "GET");
        }
    // *************************************
    //{"response_status":"error","response_code":"900101","response_msg":"Invalid merchant Cipher Text"}
    //*******************

    //{"response_status":"error","response_code":"900101","response_msg":"Invalid merchant Cipher Text"}
    public function opening_balance($request)
        {
            $auth		= json_decode($this->generatetoken($request));
            $url		= $this->jenga->opening_closing_balance_url;
            $token      = $auth->access_token;
            openssl_sign($this->plainText, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
            $data = [
                        "countryCode"	=> $request['country_code'],
                        "accountId"		=> $request['account_id'],
                        "date"			=> $request['date']
                    ];

            return $this->curl_function($token, $signature, $url, $data, "POST");
        }
    public function get_full_statement($request)
        {
            $auth		= json_decode($this->generatetoken($request));
            $url		= $this->jenga->full_statement_url;
            $token      = $auth->access_token;
            openssl_sign($this->plainText, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
            $data = [
                "countryCode"	=> $request['country_code'],
                "accountNumber"	=> $request['account_id'],
                "fromDate"		=> $request['startdate'],//1st of January
                "toDate"		=> $request['enddate'],
                "limit"			=> 20,		//This is the minimum limit where 20 is the default
                "reference"		=> "",		//transaction reference
                "serial"		=> "",		//transaction serial number
                "postedDateTime"	=> "",	//transaction posted date
                "date"				=> "",
                "runningBalance"	=> [
                    "currency"	=> "KES",
                    "amount"	=> 0
                ]

            ];

            return $this->curl_function($token, $signature, $url, $data, "POST");
        }

    //*************************************************** CORE FUNCTIONS


    protected function transfer_money($request)
        {
            $auth		=   json_decode($this->generatetoken($request));
            $url		=   $this->jenga->opening_closing_balance_url;
            $token      =   $auth->access_token;
            openssl_sign($this->plainText, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
            $source     =   [
                                "countryCode"	=> $request['country_code'],
                                "name"			=> $request['company_name'],
                                "accountNumber"	=> $request['account_id'],
                            ];
            $data       =   [
                                "source"        => $source,
                                "destination"   => $request['destination'],
                                "transfer"      => $request['transfer_details']
                            ];

            $result     = json_decode($this->curl_function($token, $signature, $url, $data, "POST"));
            return $result;
        }

    private function curl_function($token, $signature, $url, $data, $request_type = "POST")
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                                            CURLOPT_URL             => $url,
                                            CURLOPT_RETURNTRANSFER  => true,
                                            CURLOPT_ENCODING        => "",
                                            CURLOPT_MAXREDIRS       => 10,
                                            CURLOPT_TIMEOUT         => 30,
                                            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                                            CURLOPT_CUSTOMREQUEST   => $request_type,
                                            CURLOPT_POSTFIELDS      => json_encode($data),
                                            CURLOPT_HTTPHEADER      => array(
                                                                                "Authorization: Bearer " . $token,
                                                                                "cache-control: no-cache",
                                                                                "Content-Type: application/json",
                                                                                "signature: " . base64_encode($signature)
                                                                            )
                                        )
                                );
            $result = curl_exec($curl);
            $err    = curl_error($curl);

            curl_close($curl);

            if ($err)
                {
                    $dt = "cURL Error #:" . $err;
                }
            else
                {
                    $dt = $result;
                }
            return $dt;
        }
}
