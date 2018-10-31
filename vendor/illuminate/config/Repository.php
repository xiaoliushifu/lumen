<?php

namespace Illuminate\Config;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Repository implements ArrayAccess, ConfigContract
{

    /**
     * 当前类就是门面Config背后的真正类，就像它的名字一样，其实它是一个配置工厂，
     * All of the configuration items.
     * 所有的配置项目都存在这里了，一个属性数组，多层的数组数据结构而已
     * 和环境的不同是，配置存在与某个对象的属性里；而环境变量存在与getenv这个PHP原生对象，这是和当前进程有关的操作系统级别的设置。
     * @var array
     */
    protected $items = [];

    /**
     * Create a new configuration repository.
     * 创建配置对象时，就初始化了它的items属性
     * 它的实例化也是通过容器resolve过程，惰性加载
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     * 给定配置项是否存在
     * 使用了助手类Arr数组快捷方便操作的方法has
     * 这是一套操作，Arr::has(),Arr::get(),Arr::set()，必须全部使用Arr的方法，因为items数据结构的关系
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     * 获得指定的配置项
     * @param  array|string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get many configuration values.
     * 一次获得多个配置项
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            // 索引数组的情况，默认值就是null
            if (is_numeric($key)) {
                list($key, $default) = [$default, null];
            }
            //一个个去获得，最后已数组形式返回去
            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     * 设置一个或者多个配置项目
     * Example::
     *      Config::set(['key'=>'val'] );   数组形式
     *      Config::set('key','val');
     * @param  array|string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     * 给数组的头部填充一个元素，不是覆盖哦,$key表示的配置项必须是数组，否则报错
     * Config::prepend('main','newValue');
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     * 给配置项加一个元素在末尾（原先的配置项不一定是数组）
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
