<?php

namespace App\Providers;

use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.
        //  api是自定义的guard名字，背后是RequestGuard这个类
        //auth是管理guard的类，管理各种guard(session,cookie,token等guard)
        //viaRequest('api', function ($request){})  这里是注册一个自定义guard,guard的名字是api(对应auth配置文件的guard里的driver)
        //确切的说是注册一段【认证逻辑】，但不是触发认证判断。触发是在路由中间件里。
        //比如Auth::check(),Auth::user(),Auth::guest()，Auth::authenticate()这些方法都会触发【认证逻辑】
        //因为这些方法都有一个共性：它们最终都调用了Auth::user()方法

        $this->app['auth']->viaRequest('api', function ($request) {
            \Log::info('触发判断认证逻辑');
            if ($request->input('api_token')) {
                return User::where('api_token', $request->input('api_token'))->first();
            }
        });
    }
}
