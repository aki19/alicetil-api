<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
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

        $ips = env("WHITE_LIST");
        if (empty($ips)) {
            return $next($request);
        }

        if (!IpUtils::checkIp($request->ip(), explode(",", $ips))) {
            throw new AccessDeniedHttpException('IPNotAllowed');
        }

        return $next($request);
    }
}
