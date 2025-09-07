<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * You can set this in .env with TRUSTED_PROXIES=* 
     * to trust all proxies (like Render/Heroku).
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * This tells Laravel to respect X-Forwarded-* headers.
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        // Load proxies from env, defaults to trusting all (*)
        $this->proxies = env('TRUSTED_PROXIES', '*');
    }
}
