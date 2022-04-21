<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use App\Models\User;
use Closure;

class ApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->header('FrontendRequest')){
            $user = User::checkToken();
            if($user && $user != 'expired'){
                return $next($request);
            }elseif($request->header('TokenApi') && $request->header('TokenApi') == env('API_KEY'))
                return $next($request);
        }else{
            $staff = Staff::checkToken();
            if (
                ($staff = Staff::checkToken() && $staff != 'expired')
                ||
                ($request->header('TokenApi') && $request->header('TokenApi') == env('API_KEY'))
            ) {
                return $next($request);
            }
        }

        return response()->json('Unauthorized', 401);
    }
}
