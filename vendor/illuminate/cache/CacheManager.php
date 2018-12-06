<?php

namespace Illuminate\Cache;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

/**
 * @mixin \Illuminate\Contracts\Cache\Repository
 * 缓存管理器，符合xxxxManager的命名习惯
 * 提供了缓存对象实例化（根据各个缓存驱动的不同），读取配置等对缓存对象的管理
 * 而缓存对象的所有通用方法，则都交给Store接口来定义，每个实现Store接口的
 * 子类（也就是缓存驱动）负责具体实现继承自Store的方法，比如put,get,increment方法等
 *
 */
class CacheManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved cache stores.
     * 可以保存多个缓存驱动对象
     * @var array
     */
    protected $stores = [];

    /**
     * 还可以构建自定义的缓存对象
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new Cache manager instance.
     * 缓存管理器自己的实例化，没啥说的
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a cache store instance by name.
     * 实例化缓存对象的方法，这也是工厂模式的具体代码实现
     * 缓存管理器通过继承工厂模式的store方法，统一了创建缓存对象的接口
     * 具体构建过程交给缓存底层完成
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] = $this->get($name);
    }

    /**
     * Get a cache driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        return $this->store($driver);
    }

    /**
     * Attempt to get the store from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function get($name)
    {
        return $this->stores[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given store.
     * 这里是比较底层的创建缓存对象的方法
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //根据$name读取配置
        $config = $this->getConfig($name);

        //没有配置缓存驱动，必须报异常，不得容忍
        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }
        //优先检测下，是不是自定义构建缓存对象（非redis,database,file这些）
        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        } else {
            // 拼接为固定格式的字符串
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';
            //再次检测是否有这个方法
            if (method_exists($this, $driverMethod)) {
                //调用缓存管理器提前写好的几个底层驱动实例化的方法
                return $this->{$driverMethod}($config);
            } else {
                //没有仍然报异常！
                throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
            }
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the APC cache driver.
     * Apc缓存驱动的实例化方法
     * @param  array  $config
     * @return \Illuminate\Cache\ApcStore
     */
    protected function createApcDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        return $this->repository(new ApcStore(new ApcWrapper, $prefix));
    }

    /**
     * Create an instance of the array cache driver.
     * 数组缓存驱动的实例化方法
     * @return \Illuminate\Cache\ArrayStore
     */
    protected function createArrayDriver()
    {
        return $this->repository(new ArrayStore);
    }

    /**
     * Create an instance of the file cache driver.
     * 文件缓存驱动的实例化方法，直接new然后统一封装到Repository对象中
     * @param  array  $config
     * @return \Illuminate\Cache\FileStore
     */
    protected function createFileDriver(array $config)
    {
        return $this->repository(new FileStore($this->app['files'], $config['path']));
    }

    /**
     * Create an instance of the Memcached cache driver.
     * 类似xxx的实例化方法
     * @param  array  $config
     * @return \Illuminate\Cache\MemcachedStore
     */
    protected function createMemcachedDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        $memcached = $this->app['memcached.connector']->connect(
            $config['servers'],
            $config['persistent_id'] ?? null,
            $config['options'] ?? [],
            array_filter($config['sasl'] ?? [])
        );

        return $this->repository(new MemcachedStore($memcached, $prefix));
    }

    /**
     * Create an instance of the Null cache driver.
     * Null这个
     * @return \Illuminate\Cache\NullStore
     */
    protected function createNullDriver()
    {
        return $this->repository(new NullStore);
    }

    /**
     * Create an instance of the Redis cache driver.
     * redis的
     * @param  array  $config
     * @return \Illuminate\Cache\RedisStore
     */
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];

        $connection = $config['connection'] ?? 'default';

        return $this->repository(new RedisStore($redis, $this->getPrefix($config), $connection));
    }

    /**
     * Create an instance of the database cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\DatabaseStore
     */
    protected function createDatabaseDriver(array $config)
    {
        $connection = $this->app['db']->connection($config['connection'] ?? null);

        return $this->repository(
            new DatabaseStore(
                $connection, $config['table'], $this->getPrefix($config)
            )
        );
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return \Illuminate\Cache\Repository
     */
    public function repository(Store $store)
    {
        //缓存驱动的使用统一封装为Repository这个对象，又多了一层抽象
        //再次说明CacheManager只是管理缓存对象的实例化过程，而创建缓存对象后的使用
        //则封装到Repository对象来实现
        $repository = new Repository($store);

        //DispatcherContract是否已经注册到容器中
        if ($this->app->bound(DispatcherContract::class)) {
            //缓存对象设置DispatcherContract（理解有点难度，估计是为缓存时考虑到一些事件的触发处理吧）
            $repository->setEventDispatcher(
                $this->app[DispatcherContract::class]
            );
        }

        return $repository;
    }

    /**
     * Get the cache prefix.
     * 缓存前缀，是在缓存管理器这处理的
     * @param  array  $config
     * @return string
     */
    protected function getPrefix(array $config)
    {
        return $config['prefix'] ?? $this->app['config']['cache.prefix'];
    }

    /**
     * Get the cache connection configuration.
     * 读取指定名称的缓存配置，用来实例化缓存对象
     * @param  string  $name 驱动名称
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["cache.stores.{$name}"];
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['cache.default'];
    }

    /**
     * Set the default cache driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['cache.default'] = $name;
    }

    /**
     * Register a custom driver creator Closure.
     * 如果需要扩展缓存驱动，则通过缓存管理器的extend方法就行
     * 考虑的多周到呀（自带的缓存驱动就够用了，还考虑自定义扩展）
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     * 通过php魔术方法，具体交给底层驱动去执行对应的方法。
     * 各司其职
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}
