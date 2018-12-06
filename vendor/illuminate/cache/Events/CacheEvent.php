<?php

namespace Illuminate\Cache\Events;

/**
 * 缓存事件的抽象类
 * 基本涵盖了缓存整个体系下预定义的所有事件的共性操作
 * 它就是最高的事件基类
 * Class CacheEvent
 * @package Illuminate\Cache\Events
 */
abstract class CacheEvent
{
    /**
     * The key of the event.
     *
     * @var string
     */
    public $key;

    /**
     * The tags that were assigned to the key.
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
    }

    /**
     * Set the tags for the cache event.
     *
     * @param  array  $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
