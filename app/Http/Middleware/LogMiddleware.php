<?php
/**
 * 测试控制器
 */
namespace App\Http\Middleware;

use Closure;

class LogMiddleware
{
    /**
     * 进行请求过滤
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        return $next($request);
    }

}