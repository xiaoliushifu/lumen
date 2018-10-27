<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
//    var_dump($router);
//    var_dump($router->app);
    //DB这个facade是通过php内置函数class_alias('Illuminate\Support\Facades\DB','DB',)注册到内存里,
    //其中第一个参数'Illuminate\Support\Facades\DB'是真正的全命名空间的类，第二个参数DB是别名。
    //所以这里并没有写命名空间的话，DB::select('test')就是找的早已注册了别名的'Illuminate\Support\Facades\DB'
    //这就是门面Facade的核心："Facade无需使用命名空间导入，可以在应用任何地方使用"
    //class_alias还有第三个参数，在类不存在时是否自动加载，默认true开启自动加载
    //了解了上述Facade的核心后，大家还关心的是，背后真正的功能类到底是哪个，如何确定？稍后再说

    //我们后续再说
    DB::select('test');
    return $router->app->version();
});

$router->get('foo',['as'=>'foo', function () {
    return 'Hello World';
}]);

// 中间件可以分配到指定的路由,
//中间件的生效范围多得是，还需慢慢研究
$router->get('profile', [
    'middleware' => App\Http\Middleware\OldMiddleware::class,
    'uses' => 'ExampleController@show'
]);
