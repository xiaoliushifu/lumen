<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * 定义一个After中间件，虽然名字是After，但是它就是一个普通到中间件
 * 仍然随意地可以注册到全局或者路由中间件都行
 * Class AfterMiddleware
 * @package App\Http\Middleware
 */
class AfterMiddleware
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
        Log::info('AfterMiddle');
        $response = $next($request);
        return $response;
    }
}
