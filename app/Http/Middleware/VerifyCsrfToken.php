<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'https://trans.standardmedia.co.ke/app/c2bconfirmation',
        'https://trans.standardmedia.co.ke/app/c2bvalidation',
        'https://trans.standardmedia.co.ke/dashboard'
    ];
}
