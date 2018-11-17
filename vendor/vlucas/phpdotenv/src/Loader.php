<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the loaded class.
 * Loader就是ENV包里实现加载功能的类，它有责任通过【读取硬盘文件，加载变量】完成任务，
 * 还有过滤【注释符号#】【export】安全的作用
 * It's responsible for loading variables by reading a file from disk and:
 * - stripping comments beginning with a `#`,
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"`.
 */
class Loader
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Are we immutable?
     * 是否允许覆盖，后续同名的环境变量覆盖之前的环境变量
     * @var bool
     */
    protected $immutable;

    /**
     * The list of environment variables declared inside the 'env' file.
     * 只存储环境变量的名字
     * @var array
     */
    public $variableNames = array();

    /**
     * Create a new loader instance.
     *
     * @param string $filePath
     * @param bool   $immutable
     *
     * @return void
     */
    public function __construct($filePath, $immutable = false)
    {
        $this->filePath = $filePath;
        $this->immutable = $immutable;
    }

    /**
     * Set immutable value.
     *
     * @param bool $immutable
     * @return $this
     */
    public function setImmutable($immutable = false)
    {
        $this->immutable = $immutable;

        return $this;
    }

    /**
     * Get immutable value.
     *
     * @return bool
     */
    public function getImmutable()
    {
        return $this->immutable;
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function load()
    {
        $this->ensureFileIsReadable();

        $filePath = $this->filePath;
        $lines = $this->readLinesFromFile($filePath);
        foreach ($lines as $line) {
            if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
                $this->setEnvironmentVariable($line);
            }
        }

        return $lines;
    }

    /**
     * 存在且可读性，我想说得是，这个方法的命名非常有意思 Ensure
     * Ensures the given filePath is readable.
     * 否则抛异常
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return void
     */
    protected function ensureFileIsReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new InvalidPathException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }
    }

    /**
     * 注意这个方法的命名 Normalise,就是正常化（就是做一些过滤，修剪操作确保没有问题，达到正常化）
     * Normalise the given environment variable.
     *
     * Takes value as passed in by developer and:
     * 拆分
     * - ensures we're dealing with a separate name and value, breaking apart the name string if needed,
     * - cleaning the value of quotes, 去除引号
     * - cleaning the name of quotes,
     * - resolving nested variables. 解析嵌套变量
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function normaliseEnvironmentVariable($name, $value)
    {
        //拆分，过滤$name,过滤$value
        list($name, $value) = $this->processFilters($name, $value);
        // 是否嵌套变量,比如 ${APP_ENV}
        $value = $this->resolveNestedVariables($value);

        return array($name, $value);
    }

    /**
     * Process the runtime filters.
     *
     * Called from `normaliseEnvironmentVariable` and the `VariableFactory`, passed as a callback in `$this->loadFromFile()`.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    public function processFilters($name, $value)
    {
        list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);//拆分
        list($name, $value) = $this->sanitiseVariableName($name, $value);//过滤$name
        list($name, $value) = $this->sanitiseVariableValue($name, $value);//过滤$value

        return array($name, $value);
    }

    /**
     * Read lines from the file, auto detecting line endings.
     * 这个方法也是有意思，使用自己的习惯，又不污染php原有的功能
     * 那就是：
     *  1先读取PHP原有的变量auto_detect_line_endings配置
     *  2设置自己的配置
     *  3使用自己的功能
     *  4还原php原有的配置
     * 类似这样的用法，以后还会见到很多的。
     * @param string $filePath
     *
     * @return array
     */
    protected function readLinesFromFile($filePath)
    {
        // Read file into an array of lines with auto-detected line endings
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        return $lines;
    }

    /**
     * Determine if the line in the file is a comment, e.g. begins with a #.
     * 从这里知道了,.env文件里如何写注释：以#开头
     * @param string $line
     *
     * @return bool
     */
    protected function isComment($line)
    {
        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
    }

    /**
     * Determine if the given line looks like it's setting a variable.
     * 我还是想说，这个方法名字looklike有意思，不懂点英语还真不知道
     * php命名函数还能这么用
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false;
    }

    /**
     * Split the compound string into parts.
     * 就像注释所说，这个方法的名字就是五个单词构成的。split-compound-string-into-part
     * If the `$name` contains an `=` sign, then we split it into 2 parts, a `name` & `value`
     * disregarding the `$value` passed in.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function splitCompoundStringIntoParts($name, $value)
    {
        if (strpos($name, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $name, 2));
        }

        return array($name, $value);
    }

    /**
     * Strips quotes from the environment variable value.
     *
     * @param string $name
     * @param string $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    protected function sanitiseVariableValue($name, $value)
    {
        $value = trim($value);
        if (!$value) {
            return array($name, $value);
        }

        if ($this->beginsWithAQuote($value)) { // value starts with a quote
            $quote = $value[0];
            $regexPattern = sprintf(
                '/^
                %1$s           # match a quote at the start of the value
                (              # capturing sub-pattern used
                 (?:           # we do not need to capture this
                  [^%1$s\\\\]* # any character other than a quote or backslash
                  |\\\\\\\\    # or two backslashes together
                  |\\\\%1$s    # or an escaped quote e.g \"
                 )*            # as many characters that match the previous rules
                )              # end of the capturing sub-pattern
                %1$s           # and the closing quote
                .*$            # and discard any string after the closing quote
                /mx',
                $quote
            );
            $value = preg_replace($regexPattern, '$1', $value);
            $value = str_replace("\\$quote", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            //从这里可以看出，env文件里的注释还可以写在行的末尾
            $parts = explode(' #', $value, 2);
            $value = trim($parts[0]);

            // Unquoted values cannot contain whitespace
            if (preg_match('/\s+/', $value) > 0) {
                // Check if value is a comment (usually triggered when empty value with comment)
                if (preg_match('/^#/', $value) > 0) {
                    $value = '';
                } else {
                    throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
                }
            }
        }

        return array($name, trim($value));
    }

    /**
     * Resolve the nested variables.
     * 支持嵌套，高级不？
     * Look for ${varname} patterns in the variable value and replace with an
     * existing environment variable.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function resolveNestedVariables($value)
    {
        if (strpos($value, '$') !== false) {
            $loader = $this;
            $value = preg_replace_callback(
                '/\${([a-zA-Z0-9_.]+)}/',
                function ($matchedPatterns) use ($loader) {
                    $nestedVariable = $loader->getEnvironmentVariable($matchedPatterns[1]);
                    if ($nestedVariable === null) {
                        return $matchedPatterns[0];
                    } else {
                        return $nestedVariable;
                    }
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Strips quotes and the optional leading "export " from the environment variable name.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function sanitiseVariableName($name, $value)
    {
        $name = trim(str_replace(array('export ', '\'', '"'), '', $name));

        return array($name, $value);
    }

    /**
     * Determine if the given string begins with a quote.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function beginsWithAQuote($value)
    {
        return isset($value[0]) && ($value[0] === '"' || $value[0] === '\'');
    }

    /**
     * Search the different places for environment variables and return first value found.
     * 按照优先级搜索环境变量，注意第一次见到使用switch(true)
     * @param string $name
     *
     * @return string|null
     */
    public function getEnvironmentVariable($name)
    {
        //这种用法有意思吗？true这里就是布尔，并不是一定转化为1，因为switch语法支持表达式？
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                $value = getenv($name);
                return $value === false ? null : $value; // switch getenv default to null
        }
    }

    /**
     * Set an environment variable.
     *
     * This is done using:
     * - putenv,
     * - $_ENV,
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);

        $this->variableNames[] = $name;

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
         //immutable 这里明白了，就是说环境变量是否允许覆盖和修改
        //mutable 可修改可变化的；immutable就是不可更改的
        if ($this->immutable && $this->getEnvironmentVariable($name) !== null) {
            return;
        }

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            //这里是通过php的方式设置操作系统的环境变量（用户级别）
            putenv("$name=$value");
        }
        //两个超全局数组里也保留一份
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Clear an environment variable.
     * 清除某个环境变量
     * This is not (currently) used by Dotenv but is provided as a utility
     * method for 3rd party code.
     *
     * 所谓清除，就是下述两行代码而已
     * This is done using:
     * - putenv,
     * - unset($_ENV, $_SERVER).
     *
     * @param string $name
     *
     * @see setEnvironmentVariable()
     *
     * @return void
     */
    public function clearEnvironmentVariable($name)
    {
        // Don't clear anything if we're immutable.
        if ($this->immutable) {
            return;
        }

        if (function_exists('putenv')) {
            putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);
    }
}
