<?php

namespace App\Http\Middleware;

use App\ClassM;
use Closure;

class GetClass
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
    	if($request->get('class_id') == null){
			$host = $request->header('origin') ?? $request->header('referer');
			preg_match('/:\/\/((.+).domashka.cloud).*/', $host, $exp_host);
			if($host == "http://localhost:8002") $exp_host = [2=> '13'];
			if(isset($exp_host[2])){
				$request->attributes->add([
					'class_domain' => $exp_host[2],
					'class' => $class = ClassM::getByDomain($exp_host[2]),
					'class_id' => $class_id = isset($class['id']) ? $class['id'] : null
				]);

				if($class !== null) return $next($request);
				return response()->json(['message' => "{$host} not found"])->setStatusCode(403);
			}
		}else{
			$request->attributes->add([
				'class' => $class = ClassM::find($request->get('class_id')),
				'class_id' => $class_id = isset($class['id']) ? $class['id'] : null
			]);

			if($class !== null) return $next($request);
    	}
    	return response()->json(['message' => "err get class"])->setStatusCode(403);
	}
}
