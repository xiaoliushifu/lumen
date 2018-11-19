<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     * guard工厂类，它管理各种guard的生产，目前是AuthManager类
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     * $auth就是manager
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        //guard返回$guard命名的guard实例，管理用户
        //这里只判断是否是登陆状态（如果本次请求带着账号密码也会通过）
        //这会触发判断逻辑
        if ($this->auth->guard($guard)->guest()) {
            return response('Unauthorized.', 401);
        }
        
        //下面这样也可以,看了guest()的内部其实也是调用了check(),取非而已
        if (!\Auth::check()) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
