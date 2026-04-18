<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CheckIpMiddleware
    {
        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */public $whiteIps = [     '127.0.0.1',
                                    '198.211.113.248',
                                    '196.201.214.200',
                                    '196.201.214.206',
                                    '196.201.213.114',
                                    '196.201.214.207',
                                    '196.201.214.208',
                                    '196.201.213.44',
                                    '196.201.212.127',
                                    '196.201.212.128',
                                    '196.201.212.129',
                                    '196.201.212.132',
                                    '196.201.212.136',
                                    '196.201.212.138',
                                    '196.201.212.74'
                                ];
        public function handle($request, Closure $next)
            {
                if ($this->isMpesaC2BEndpoint($request))
                    {
                        $rawPayload = $request->getContent();
                        $decodedPayload = json_decode($rawPayload, true);

                        Log::info('M-Pesa C2B endpoint hit', [
                            'ip' => $request->ip(),
                            'allowed_ip' => in_array($request->ip(), $this->whiteIps),
                            'method' => $request->method(),
                            'url' => $request->fullUrl(),
                            'path' => $request->path(),
                            'trans_id' => is_array($decodedPayload) ? ($decodedPayload['TransID'] ?? null) : null,
                            'shortcode' => is_array($decodedPayload) ? ($decodedPayload['BusinessShortCode'] ?? null) : null,
                            'amount' => is_array($decodedPayload) ? ($decodedPayload['TransAmount'] ?? null) : null,
                            'account' => is_array($decodedPayload) ? ($decodedPayload['BillRefNumber'] ?? null) : null,
                            'payload' => $decodedPayload ?: $rawPayload,
                        ]);
                    }

                if (!in_array($request->ip(), $this->whiteIps))
                    {
                        Log::info('Blocked IP ADDRESS : '.$request->ip());
                        //return abort(403, 'your ip address is not valid.');
                    }
                return $next($request);
            }

        protected function isMpesaC2BEndpoint($request)
            {
                return in_array(trim($request->path(), '/'), [
                    'api/c2bconfirmation',
                    'api/c2bvalidation',
                    'app/c2bconfirmation',
                    'app/c2bvalidation',
                ]);
            }
    }
