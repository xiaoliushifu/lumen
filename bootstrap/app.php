<?php

//很多框架的第一步，几乎都是这一行代码，【首先引入composer的自动加载器】
//Composer\Autoload\ClassLoader 就是composer的【自动加载器】
require_once __DIR__.'/../vendor/autoload.php';

try {
    //实例化Dotenv\Dotenv时只传递了路径，其实还可以传递第二个参数，默认是.env
    //从这里可以看出，如果想修改.env的文件名，并且修改路径。只需修改俩参数即可。厉害不？
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);
//加载自定义的配置文件（文件中的配置项目就加载进来了）
$app->configure('main');//配置对象在第一次使用时实例化，后续再有其它配置文件加载时就不必再次实例化了
//配置对象是共享的，单例的，配置文件是随时加载进来的
$app->configure('main_local');

//一个服务提供者需要的配置文件
$app->configure('ding');

//邮件的配置文件
$app->configure('mail');


//注册门面（也就是所谓的组件别名机制）
// $app->withFacades();
//下面这一行，第二个数组是自定义的门面机制
$app->withFacades(true, [
    'Tymon\JWTAuth\Facades\JWTAuth' => 'JWTAuth',
    'Tymon\JWTAuth\Facades\JWTFactory' => 'JWTFactory',
]);

//需要操作数据库了，使用Model要去注册相关库
 $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
| singleton就是注册单例（bind方法的第三个参数是true)
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
| 注册全局中间件，任何请求都拦截
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

 $app->middleware([
//    App\Http\Middleware\OldMiddleware::class,
//     App\Http\Middleware\BeforeMiddleware::class,
 ]);

//注册路由中间件，注册后在路由里使用middleware引用即可
 $app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
//     'before' => App\Http\Middleware\BeforeMiddleware::class,
//     'after' => App\Http\Middleware\AfterMiddleware::class,
 ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

/*
 * 在这里完成自定义服务提供者SmsServiceProvider的注册
 * 
*/
 $app->register(App\Providers\SmsServiceProvider::class);
$app->register(\Tymon\JWTAuth\Providers\LumenServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
 //注册钉钉消息服务提供者
// $app->register(DingNotice\DingNoticeServiceProvider::class);

 //注册邮件服务提供者
// $app->register(Illuminate\Mail\MailServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    //注册路由，就是通过直接引入web.php实现的，把文件中的get,post,等存储到router对象到几个数组里保存后续使用而已。
    require __DIR__.'/../routes/web.php';
});

return $app;
