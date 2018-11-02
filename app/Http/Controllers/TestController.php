<?php
namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Support\Facades\Request;

class TestController extends Controller
{

    //访问List方法
    public function getList()
    {
        $cars = Car::all();
        return response()->json($cars);
    }

    //访问List方法
    public function getDetail(Request $request)
    {
        var_dump($request);
        $cars = Car::find(2);
        return response()->json($cars);
    }
}