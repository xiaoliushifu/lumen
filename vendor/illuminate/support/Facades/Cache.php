<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Cache\CacheManager
 * @see \Illuminate\Cache\Repository
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     * 继承Facade抽象类，只需实现这个方法即可
     * 这里的cache不是指缓存对象，而是引导出缓存管理类，cacheManager
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cache';
    }
}
