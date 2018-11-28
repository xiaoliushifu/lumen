<?php
namespace App\Http\Controllers;

use App\Models\Car;
use App\Validates\UserValidate;
use Illuminate\Support\Facades\Request;
use App\Http\Services\TestService;

class TestController extends Controller
{

    protected $validator;
    public function __construct(UserValidate $validate)
    {
        $this->validator = $validate;
    }

    //访问List方法
    public function getList()
    {
        //交给UserValidate验证器的create场景验证下
        $param = ['phone'=>'13589998981','name'=>3];
        if ($err = $this->validator->check($param,UserValidate::SCENARIO_LIST)) {
            return response()->json(['ServerTime' => time(), 'ServerNo' => 400, 'ResultData' => $err]);
        }
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