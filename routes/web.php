<?php
use Illuminate\Support\Facades\DB;
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
    var_dump($router);
    var_dump($router->app);
    //DB这个facade没有解析到，说明并没有注册到容器里
    //我们后续再说
    DB::select('test');
    return $router->app->version();
});
