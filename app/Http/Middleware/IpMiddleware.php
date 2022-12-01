<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class IpMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if (!app()->runningUnitTests() && !app()->environment('production')) {
            return $next($request);
        }

        $request::setTrustedProxies(array($request->ip()), $request::HEADER_X_FORWARDED_ALL);

        $ip = $request->ip();
        if ($request->server('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(",", $request->server('HTTP_X_FORWARDED_FOR'));
            $ip  = $ips[0];
        }

        if (!IpUtils::checkIp($ip, explode(",", env("WHITE_LIST", "")))) {
            throw new AccessDeniedHttpException('IPNotAllowed');
        }

        return $next($request);
    }
}
