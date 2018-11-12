<?php

namespace Illuminate\Contracts\Auth;
//一个认证接口管理器（管理guard的初始化）
interface Factory
{
    /**
     * Get a guard instance by name.
     * 根据guard名（一般就是在配置文件中）返回guard实例
     * @param  string|null  $name
     * @return mixed
     */
    public function guard($name = null);

    /**
     * Set the default guard the factory should serve.
     *
     * @param  string  $name
     * @return void
     */
    public function shouldUse($name);
}
