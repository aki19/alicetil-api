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

        echo "check ip " . $request->ip() . PHP_EOL;
        echo "check HTTP_X_FORWARDED_FOR " . $request->header('HTTP_X_FORWARDED_FOR') . PHP_EOL;

        $ip = $request->ip();
        if ($request->header('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(",", $request->header('HTTP_X_FORWARDED_FOR'));
            $ip  = $ips[0];
        }

        echo "check ip " . $ip . PHP_EOL;

        if (!IpUtils::checkIp($ip, explode(",", env("WHITE_LIST", "")))) {
            throw new AccessDeniedHttpException('IPNotAllowed');
        }

        echo "ip ok".PHP_EOL;

        return $next($request);
    }
}
