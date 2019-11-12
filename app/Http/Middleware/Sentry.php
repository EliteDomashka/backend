<?php

namespace App\Http\Middleware;

use Closure;
use Sentry\State\Scope;

class Sentry{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
    	$ip = $request->getClientIp();
		app('sentry')->configureScope(function (Scope $scope)use($ip): void {
			$scope->clear()->setUser([
				'ip' => $ip,
			]);
		});

		return $next($request);
    }
}
