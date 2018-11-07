<?php
namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Support\Facades\Request;
use App\Http\Services\TestService;

class TestController extends Controller
{

    //访问List方法
    public function getList()
    {
        $cars = Car::all();
        return response()->json($cars);
    }

    //利用框架的特性，直接注入一个Service，当然在Yii2中这样用也可以
    //我们一般直接new
    public function getTest()
    {
        //使用app()快捷函数从容器中实例化我们的对象，这种方式不用引入
//        $test = app('test');
        //直接实例化service当然需要引入
        $test = new TestService();
        $test->callMe('Test2Controller');
    }

    //访问List方法
    public function getDetail(Request $request)
    {
        var_dump($request);
        $cars = Car::find(2);
        return response()->json($cars);
    }
}