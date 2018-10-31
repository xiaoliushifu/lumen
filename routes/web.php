<?php
//由于Config门面没有在默认的加载里，所以我们需要引入这个门面才可以使用
use Illuminate\Support\Facades\Config;
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


//    dd(get_class(Config::getFacadeRoot()));
    //获得当个配置项，注意，配置文件都得是文件名.item,
    //比如config下有一个main.php文件，里面有一个access_key配置项，那么如下获得该配置即可
//    echo Config::get('main.access_key');


//    Config::set('name.me','我');//点语法将生成数组,$name['me']='我'
    Config::set(['b','alias']);  //索引数组形式的设置  0=>b, 1=>alias
    Config::set(['name.a'=>'alias']);//关联数组形式支持点语法  name['a']='alias'
    Config::set('a.b.c','lumen');//a['b']['c']='lumen'支持多层数组配置
    Config::prepend('main','word');// 给数组main的头部添加一个元素（索引下标0）
    Config::push('main','word2');// 给main的尾部添加一个元素（索引下标递增）
    print_r(Config::get(['0','1']));//数组一次获取多个配置，已数组形式返回
    //获得所有的配置
//    dd(Config::all());




    echo Config::get('main.access_key');

    return $router->app->version();
});

$router->get('foo',['as'=>'foo', function () {
    Log::Info('Hello world');
    return 'Hello World';
}]);

// 中间件可以分配到指定的路由,
//中间件的生效范围多得是，还需慢慢研究
$router->get('profile', [
    'middleware' => App\Http\Middleware\OldMiddleware::class,
    'uses' => 'ExampleController@show'
]);
