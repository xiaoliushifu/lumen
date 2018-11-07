<?php
/**
 * 自定义一个服务提供者玩玩
 */
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Services\TestService;

class SmsServiceProvider extends ServiceProvider
{

    /**
     * 注册完自己后，都有一个boot方法被调用
     * 可以做一个初始化的操作
     * 该方法是所有的服务提供者都调用了register之后，而不一定是当前的register之后
     */
    public function boot()
    {
        \Log::info('boot called');
    }

    /**
     * 每个服务提供者必须提供一个register，把自己注册到容器里
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //使用singleton方法把我们到好多service直接绑定到容器
        $this->app->singleton('test', function () {
            return new TestService();
        });
    }
}
