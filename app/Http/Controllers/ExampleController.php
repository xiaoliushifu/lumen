<?php

namespace App\Http\Controllers;

use App\Http\Middleware\LogMiddleware;
use Illuminate\Support\Facades\Config;

class ExampleController extends Controller
{
    /**
     * ExampleController constructor.
     */
    public function __construct()
    {
        //控制器级别的中间件，仍然是在构造函数里执行
        $this->middleware(LogMiddleware::class, ['only' => [
            'show',
        ]]);
    }
    
    

    //
    public function show()
    {
        print_r(Config::all());
        return 'Hello show';
    }

}
