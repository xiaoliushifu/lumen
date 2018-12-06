<?php

namespace Illuminate\Contracts\Cache;

interface Factory
{
    /**
     * 工厂模式里的三种分类之一：工厂方法
     * Get a cache store instance by name.
     * 缓存系统也使用了工厂模式，但是目前来看，好像只是一个接口而已
     * 需要引入缓存组件的地方，设置参数为 Cache\Factory接口就行
     * 不用理会具体的底层驱动
     * store方法就是初始化底层驱动的
     * 把具体底层的实例化过程交给子类去实现
     * 这就是工厂方法的工厂模式（还有简单工厂，抽象工厂等再说）
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null);
}
