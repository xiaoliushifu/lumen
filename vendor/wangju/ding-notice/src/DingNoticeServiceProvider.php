<?php

namespace DingNotice;

use Illuminate\Support\ServiceProvider;

class DingNoticeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {//需要控制台的vendor::publish命令才生效
//        $this->publishes([
//            __DIR__ . '/../config/ding.php' => $this->app->basePath('config/ding.php'),
//        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLaravelBindings();
    }


    /**
     * Register Laravel bindings.
     *
     * @return void
     */
    protected function registerLaravelBindings()
    {
        $this->app->singleton(DingTalk::class, function ($app) {
            return new DingTalk($app['config']['ding']);
        });
    }

}
