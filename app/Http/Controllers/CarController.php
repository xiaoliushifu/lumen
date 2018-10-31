<?php
namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function createCar(Request $request)
    {
        //Model不存在这样的create方法，实则是查询构造器的方法
        //原理就是利用PHP的魔术方法__callStatic()和__call()
        $car = Car::create($request->all());
        return response()->json($car);
    }

    public function updateCar(Request $request, $id)
    {
        //根据主键获得该模型
        $car = Car::find($id);
         //直接修改
        $car->make = $request->input('make');
        $car->model = $request->input('model');
        $car->year = $request->input('year');
        
        $car->save();

        return response()->json($car);
    }

    public function deleteCar($id)
    {
        $car = Car::find($id);
        $car->delete();

        return response()->json('删除成功');
    }

    public function index()
    {
        $cars = Car::all();
        return response()->json($cars);
    }
}