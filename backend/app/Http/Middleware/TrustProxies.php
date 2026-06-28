<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Configure which upstream proxies are trusted for X-Forwarded-* headers.
 *
 * SECURITY: Without this, an attacker can spoof X-Forwarded-For to bypass
 * IP-based rate limiting. Only trust the proxies (load balancer, CDN) that
 * actually sit in front of the application.
 *
 * Deployment notes:
 *  - Single Nginx reverse-proxy on the same host: use '127.0.0.1'
 *  - AWS ALB / ELB: use Request::HEADER_X_FORWARDED_AWS_ELB
 *  - Cloudflare: add Cloudflare IP ranges and use HEADER_X_FORWARDED_FOR
 *  - No proxy (direct Internet): leave $proxies = [] and $headers = 0
 */
class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Set to '127.0.0.1' for a single local Nginx proxy, or '*' only when
     * the proxy is the sole public entrypoint AND you trust it completely
     * (e.g., a managed cloud load balancer). Never use '*' when the app is
     * directly accessible from the Internet.
     *
     * @var string[]|string|null
     */
    protected $proxies = '127.0.0.1';

    /**
     * The headers that should be used to detect proxies.
     *
     * HEADER_X_FORWARDED_TRAEFIK / HEADER_X_FORWARDED_FOR are the most common.
     * For AWS ELB use HEADER_X_FORWARDED_AWS_ELB instead.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO;
}
