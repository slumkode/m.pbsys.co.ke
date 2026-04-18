<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shortcode;
use Illuminate\Http\Request;
use App\Utils\Mpesa;

class Api extends Controller
    {
        protected $data;
        public function  checkout(Request $request)
            {
                $mpesa      =   new Mpesa();
                $shortcode  =   Shortcode::where("group","=",$request->group)->first();
                $trans      =   $mpesa->checkout([      "consumerkey"       =>  $shortcode->consumerkey,
                                                        "consumersecret"    =>  $shortcode->consumersecret,
                                                        "shortcode"         =>  $shortcode->shortcode,
                                                        "passkey"           =>  $shortcode->passkey,
                                                        "amount"            =>  $request->amount,
                                                        "msisdn"            =>  "254".substr(trim($request->msisdn),-9),
                                                        "ref"               =>  $request->account,
                                                        "desc"              =>  $request->description
                                                ]);
                return $trans;

            }
    }
