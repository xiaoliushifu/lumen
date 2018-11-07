<?php

/*
 * 这个是自定义的目录
 * 完全可以在这里写我们的Service
 * 这是服务提供者注册的真正功能类
 */
namespace App\Http\Services;


class TestService
{
    public function callMe($controller)
    {
        dd('Call Me From TestServiceProvider In '.$controller);
    }
}