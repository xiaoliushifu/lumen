<?php

/**
 * 实例中间件入门
 *
 * 中间件就是【请求】到达应用的一个个【关卡】，每个【关卡】都可以做些处理并决定【请求】
 * 是否可以继续走下去
 */
namespace App\Http\Middleware;

use Closure;

class OldMiddleware
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
        \Log::info('hello world');
//        var_dump($request);
//        if ($request->input('age') <= 200) {
//            return redirect('foo');
//        }

        return $next($request);
    }

}