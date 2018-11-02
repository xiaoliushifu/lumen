<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * 不要被它到名字吓坏了，Before也是一个普通到中间件
 * Class BeforeMiddleware
 * @package App\Http\Middleware
 */
class BeforeMiddleware
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
        Log::info('beforeMiddle');
        return $next($request);
    }
}
