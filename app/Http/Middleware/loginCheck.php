<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\JsonResponse;

class loginCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        if ($token==""){
            return response()->json(["status"=>401,"success"=>false,'message' => 'Unauthenticated!!, Please login first to get access.'],JsonResponse::HTTP_UNAUTHORIZED);
        }else{
            return $next($request);
        }

        if(Auth::guard('api')->user())
        {
            return $next($request);
        }else{
           
            return response()->json(["status"=>401,"success"=>false,'message' => 'Unauthenticated!!, Please login first to get access.']);
        }
    }
}
