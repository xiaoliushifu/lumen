<?php

namespace Laravel\Lumen\Routing;

use Exception;
use Throwable;
use Closure as BaseClosure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Pipeline\Pipeline as BasePipeline;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 */
class Pipeline extends BasePipeline
{
    /**
     * Get a Closure that represents a slice of the application onion.
     * 表示应用这个洋葱的一个切片（匿名函数），这个解释目前还不是很懂
     * 注意该函数是array_reduce的第二个参数。该函数的签名必须是两个参数：
     * 第一个参数$stack是上一次调用时的返回值，第二个参数$pipe是本次的新值。
     * 也就是说，第一个参数$stack是迭代的累积结果，第二个参数$pipe是不断添加进来的新值
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    $slice = parent::carry();

                    return call_user_func($slice($stack, $pipe), $passable);
                } catch (Exception $e) {
                    return $this->handleException($passable, $e);
                } catch (Throwable $e) {
                    return $this->handleException($passable, new FatalThrowableError($e));
                }
            };
        };
    }

    /**
     * Get the initial slice to begin the stack call.
     * 开始栈调用之前，得到这个初始切片，该切片也是匿名函数
     * 这是array_reduce函数的第三个参数。将会是中间参数的初始值
     * 要对array_reduce各个参数理解就好了。
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(BaseClosure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return call_user_func($destination, $passable);
            } catch (Exception $e) {
                return $this->handleException($passable, $e);
            } catch (Throwable $e) {
                return $this->handleException($passable, new FatalThrowableError($e));
            }
        };
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Exception  $e
     * @return mixed
     */
    protected function handleException($passable, Exception $e)
    {
        if (! $this->container->bound(ExceptionHandler::class) || ! $passable instanceof Request) {
            throw $e;
        }

        $handler = $this->container->make(ExceptionHandler::class);

        $handler->report($e);

        return $handler->render($passable, $e);
    }
}
