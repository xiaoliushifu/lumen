<?php

namespace Illuminate\Support\Facades;

use Mockery;
use RuntimeException;
use Mockery\MockInterface;

abstract class Facade
{
    /**
     * 这个属性是啥时候设置的呢？
     * The application instance being facaded.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected static $app;

    /**
     * 解析的对象都临时存放到Facade基类的静态属性（数组）里，不用再去容器中找
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance;

    /**
     * Convert the facade into a Mockery spy.
     *
     * @return void
     */
    public static function spy()
    {
        if (! static::isMock()) {
            $class = static::getMockableClass();

            static::swap($class ? Mockery::spy($class) : Mockery::spy());
        }
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @return \Mockery\Expectation
     */
    public static function shouldReceive()
    {
        $name = static::getFacadeAccessor();

        $mock = static::isMock()
                    ? static::$resolvedInstance[$name]
                    : static::createFreshMockInstance();

        return $mock->shouldReceive(...func_get_args());
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \Mockery\Expectation
     */
    protected static function createFreshMockInstance()
    {
        return tap(static::createMock(), function ($mock) {
            static::swap($mock);

            $mock->shouldAllowMockingProtectedMethods();
        });
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \Mockery\MockInterface
     */
    protected static function createMock()
    {
        $class = static::getMockableClass();

        return $class ? Mockery::mock($class) : Mockery::mock();
    }

    /**
     * Determines whether a mock is set as the instance of the facade.
     *
     * @return bool
     */
    protected static function isMock()
    {
        $name = static::getFacadeAccessor();

        return isset(static::$resolvedInstance[$name]) &&
               static::$resolvedInstance[$name] instanceof MockInterface;
    }

    /**
     * Get the mockable class for the bound instance.
     *
     * @return string|null
     */
    protected static function getMockableClass()
    {
        if ($root = static::getFacadeRoot()) {
            return get_class($root);
        }
    }

    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap($instance)
    {
        static::$resolvedInstance[static::getFacadeAccessor()] = $instance;

        if (isset(static::$app)) {
            static::$app->instance(static::getFacadeAccessor(), $instance);
        }
    }

    /**
     * 得到门面facade背后的根对象
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * 该方法是每个想要实现Facade必须实现的，返回组件的真正名称即可
     * 具体实现的Facades背后的底层类都是谁，可以去这里看看
     * https://laravel-china.org/docs/laravel/5.7/facades/2251
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Resolve the facade root instance from the container.
     * 从容器里解析出Facade根实例对象,也就是所谓的底层类
     * 但是要注意，$name在这里仍然是别名，真正背后的绑定关系并不是在Facade里确定的
     * Facade可以理解是容器里组件别名的别名。比如
     * DB==>db==>databaseManager
     * 这里DB是facade，它是db的别名
     * db是容器组件的别名，它是databaseManager的别名
     * @param  string|object  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        //已经是对象，直接返回即可
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        //最终来源于$app，也就是容器
        return static::$resolvedInstance[$name] = static::$app[$name];
    }

    /**
     * Clear a resolved facade instance.
     *
     * @param  string  $name
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    /**
     * Get the application instance behind the facade.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public static function getFacadeApplication()
    {
        return static::$app;
    }

    /**
     * 设置应用实例（容器），门面解析对象时使用
     * Set the application instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
     * 这就是Facade实现的关键，根据PHP__callStatic魔术方法就是在静态调用一个不存在的方法时，
     * 就会调用它。
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        //获得类的真正代理的实例（别名背后的真正身份）
        $instance = static::getFacadeRoot();

        //没有实例就抛异常
        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }
        //以真正实例再次调用该方法一次。到此大家Facade是啥了吧？
        return $instance->$method(...$args);
    }
}
