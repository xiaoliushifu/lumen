<?php

namespace Dotenv;

use Dotenv\Exception\InvalidPathException;

/**
 * This is the dotenv class.
 * DOtenv类是管理类，它的子对象loader是真正实现读取【.env】文件并加载环境变量的类
 * It's responsible for loading a `.env` file in the given directory and
 * setting the environment vars.
 */
class Dotenv
{
    /**
     * The file path.
     * 被加载文件的路径
     * @var string
     */
    protected $filePath;

    /**
     * The loader instance.
     * 加载器实例，子对象
     * @var \Dotenv\Loader|null
     */
    protected $loader;

    /**
     * Create a new dotenv instance.
     *
     * @param string $path
     * @param string $file 默认是.env文件
     *
     * @return void
     */
    public function __construct($path, $file = '.env')
    {
        $this->filePath = $this->getFilePath($path, $file);
        $this->loader = new Loader($this->filePath, true);
    }

    /**
     * Load environment file in given directory.
     *
     * @return array
     */
    public function load()
    {
        return $this->loadData();
    }

    /**
     * Load environment file in given directory, suppress InvalidPathException.
     *
     * @return array
     */
    public function safeLoad()
    {
        try {
            return $this->loadData();
        } catch (InvalidPathException $e) {
            // suppressing exception
            return array();
        }
    }

    /**
     * Load environment file in given directory.
     *
     * @return array
     */
    public function overload()
    {
        return $this->loadData(true);
    }

    /**
     * Returns the full path to the file.
     *
     * @param string $path
     * @param string $file
     *
     * @return string
     */
    protected function getFilePath($path, $file)
    {
        if (!is_string($file)) {
            $file = '.env';
        }

        $filePath = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;

        return $filePath;
    }

    /**
     * Actually load the data.
     *
     * @param bool $overload
     *
     * @return array
     */
    protected function loadData($overload = false)
    {
        return $this->loader->setImmutable(!$overload)->load();
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variable
     *
     * @return \Dotenv\Validator
     */
    public function required($variable)
    {
        return new Validator((array) $variable, $this->loader);
    }

    /**
     * Get the list of environment variables declared inside the 'env' file.
     * 返回环境变量的名字列表
     * @return array
     */
    public function getEnvironmentVariableNames()
    {
        return $this->loader->variableNames;
    }
}
