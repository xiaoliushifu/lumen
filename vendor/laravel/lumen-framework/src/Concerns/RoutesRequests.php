<?php

namespace Laravel\Lumen\Concerns;

use Closure;
use Exception;
use Throwable;
use FastRoute\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Pipeline;
use Illuminate\Contracts\Support\Responsable;
use Laravel\Lumen\Routing\Closure as RoutingClosure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Lumen\Routing\Controller as LumenController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

trait RoutesRequests
{
    /**
     * All of the global middleware for the application.
     * 注册到这里的中间件，都是全局中间件
     * 注意，中间件本身来说没有全局，或者局部或者路由中间件的区别
     * 主要看注册到哪里，同一个中间件即可以注册到全局，也可以注册为路由中间件。
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the route specific middleware short-hands.
     *
     * @var array
     */
    protected $routeMiddleware = [];

    /**
     * The current route being dispatched.
     *
     * @var array
     */
    protected $currentRoute;

    /**
     * The FastRoute dispatcher.
     *
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    /**
     * Add new middleware to the application.
     *
     * @param  Closure|array  $middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->middleware = array_unique(array_merge($this->middleware, $middleware));

        return $this;
    }

    /**
     * Define the route middleware for the application.
     *
     * @param  array  $middleware
     * @return $this
     */
    public function routeMiddleware(array $middleware)
    {
        $this->routeMiddleware = array_merge($this->routeMiddleware, $middleware);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(SymfonyRequest $request)
    {
        $response = $this->dispatch($request);

        if (count($this->middleware) > 0) {
            $this->callTerminableMiddleware($response);
        }

        return $response;
    }

    /**
     * Run the application and send the response.
     *
     * @param  SymfonyRequest|null  $request
     * @return void
     */
    public function run($request = null)
    {
        $response = $this->dispatch($request);

        if ($response instanceof SymfonyResponse) {
            $response->send();
        } else {
            echo (string) $response;
        }

        if (count($this->middleware) > 0) {
            $this->callTerminableMiddleware($response);
        }
    }

    /**
     * Call the terminable middleware.
     *
     * @param  mixed  $response
     * @return void
     */
    protected function callTerminableMiddleware($response)
    {
        if ($this->shouldSkipMiddleware()) {
            return;
        }

        $response = $this->prepareResponse($response);

        foreach ($this->middleware as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            $instance = $this->make(explode(':', $middleware)[0]);

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($this->make('request'), $response);
            }
        }
    }

    /**
     * Dispatch the incoming request.
     *
     * @param  SymfonyRequest|null  $request
     * @return Response
     */
    public function dispatch($request = null)
    {
        //解析http 请求，返回请求方法和pathInfo，其中包含了实例化lumen的请求对象和Symfony的请求对象的过程
        list($method, $pathInfo) = $this->parseIncomingRequest($request);

        try {
            //先走全局中间件
            return $this->sendThroughPipeline($this->middleware, function () use ($method, $pathInfo) {
                //路由是否匹配？
                if (isset($this->router->getRoutes()[$method.$pathInfo])) {
                    //传递一个数组，第一个元素是true；第二个元素是action（一般就是App\Http\Controllers\TestController@getList）,第三个元素是空数组。
                    return $this->handleFoundRoute([true, $this->router->getRoutes()[$method.$pathInfo]['action'], []]);
                }

                return $this->handleDispatcherResponse(
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            });
        } catch (Exception $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        } catch (Throwable $e) {
            return $this->prepareResponse($this->sendExceptionToHandler($e));
        }
    }

    /**
     * Parse the incoming request and return the method and path info.
     * 所谓解析请求，就是实例化请求实例的过程（根据五个超全局数组）
     * @param  \Symfony\Component\HttpFoundation\Request|null  $request
     * @return array
     */
    protected function parseIncomingRequest($request)
    {
        if (! $request) {
            //实例了两回，一个是父类Symfony的request,然后基于这个Symfony再实例化lumen的request,不知是何用意，难道就为了方便初始化lumen的request？
            $request = Request::capture();
        }
        //lumen的请求对象放到容器里，key就是illuminate\http\request
        $this->instance(Request::class, $this->prepareRequest($request));
        //这才去解析uri,返回格式是一个数组，第一个是http请求参数，第一个是PATH_INFO
        return [$request->getMethod(), '/'.trim($request->getPathInfo(), '/')];
    }

    /**
     * Create a FastRoute dispatcher instance for the application.
     *
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        return $this->dispatcher ?: \FastRoute\simpleDispatcher(function ($r) {
            foreach ($this->router->getRoutes() as $route) {
                $r->addRoute($route['method'], $route['uri'], $route['action']);
            }
        });
    }

    /**
     * Set the FastRoute dispatcher instance.
     *
     * @param  \FastRoute\Dispatcher  $dispatcher
     * @return void
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the response from the FastRoute dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException;
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException($routeInfo[1]);
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    /**
     * Handle a route found by the dispatcher.
     * 处理找到的路由对应的action.
     * @param  array  $routeInfo 数组。第一个参数，第二个参数
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        $this->currentRoute = $routeInfo;

        $this['request']->setRouteResolver(function () {
            return $this->currentRoute;
        });

        $action = $routeInfo[1];

        // Pipe through route middleware...
        if (isset($action['middleware'])) {
            $middleware = $this->gatherMiddlewareClassNames($action['middleware']);

            return $this->prepareResponse($this->sendThroughPipeline($middleware, function () {
                return $this->callActionOnArrayBasedRoute($this['request']->route());
            }));
        }

        return $this->prepareResponse(
            $this->callActionOnArrayBasedRoute($routeInfo)
        );
    }

    /**
     * Call the Closure on the array based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callActionOnArrayBasedRoute($routeInfo)
    {
        $action = $routeInfo[1];

        if (isset($action['uses'])) {
            return $this->prepareResponse($this->callControllerAction($routeInfo));
        }

        foreach ($action as $value) {
            if ($value instanceof Closure) {
                $closure = $value->bindTo(new RoutingClosure);
                break;
            }
        }

        try {
            return $this->prepareResponse($this->call($closure, $routeInfo[2]));
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Call a controller based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callControllerAction($routeInfo)
    {
        $uses = $routeInfo[1]['uses'];

        if (is_string($uses) && ! Str::contains($uses, '@')) {
            $uses .= '@__invoke';
        }

        list($controller, $method) = explode('@', $uses);

        if (! method_exists($instance = $this->make($controller), $method)) {
            throw new NotFoundHttpException;
        }

        if ($instance instanceof LumenController) {
            return $this->callLumenController($instance, $method, $routeInfo);
        } else {
            return $this->callControllerCallable(
                [$instance, $method], $routeInfo[2]
            );
        }
    }

    /**
     * Send the request through a Lumen controller.
     *
     * @param  mixed  $instance
     * @param  string  $method
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callLumenController($instance, $method, $routeInfo)
    {
        //先看看有没有控制器层级的中间件
        $middleware = $instance->getMiddlewareForMethod($method);

        if (count($middleware) > 0) {
            return $this->callLumenControllerWithMiddleware(
                $instance, $method, $routeInfo, $middleware
            );
        } else {
            return $this->callControllerCallable(
                [$instance, $method], $routeInfo[2]
            );
        }
    }

    /**
     * Send the request through a set of controller middleware.
     *
     * @param  mixed  $instance
     * @param  string  $method
     * @param  array  $routeInfo
     * @param  array  $middleware
     * @return mixed
     */
    protected function callLumenControllerWithMiddleware($instance, $method, $routeInfo, $middleware)
    {
        $middleware = $this->gatherMiddlewareClassNames($middleware);

        return $this->sendThroughPipeline($middleware, function () use ($instance, $method, $routeInfo) {
            return $this->callControllerCallable([$instance, $method], $routeInfo[2]);
        });
    }

    /**
     * Call a controller callable and return the response.
     *
     * @param  callable  $callable
     * @param  array  $parameters
     * @return \Illuminate\Http\Response
     */
    protected function callControllerCallable(callable $callable, array $parameters = [])
    {
        try {
            return $this->prepareResponse(
                $this->call($callable, $parameters)
            );
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Gather the full class names for the middleware short-cut string.
     *
     * @param  string  $middleware
     * @return array
     */
    protected function gatherMiddlewareClassNames($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;

        return array_map(function ($name) {
            list($name, $parameters) = array_pad(explode(':', $name, 2), 2, null);

            return array_get($this->routeMiddleware, $name, $name).($parameters ? ':'.$parameters : '');
        }, $middleware);
    }

    /**
     * Send the request through the pipeline with the given callback.
     * 把请求发送到pipeline，去pipeline里走一遭
     * @param  array  $middleware
     * @param  \Closure  $then
     * @return mixed
     */
    protected function sendThroughPipeline(array $middleware, Closure $then)
    {
        //有全局中间件，且中间件没有被禁用
        if (count($middleware) > 0 && ! $this->shouldSkipMiddleware()) {
            //$this这里是lumen应用。
            return (new Pipeline($this))
                //上一步解析请求的时候，已经把request对象放到了容器instances数组里了
                ->send($this->make('request'))
                ->through($middleware)
                ->then($then);//then方法就开始走中间件了
        }
        //没有中间件则直接走$then回调
        return $then();
    }

    /**
     * Prepare the response for sending.
     *
     * @param  mixed  $response
     * @return Response
     */
    public function prepareResponse($response)
    {
        if ($response instanceof Responsable) {
            $response = $response->toResponse(Request::capture());
        }

        if ($response instanceof PsrResponseInterface) {
            $response = (new HttpFoundationFactory)->createResponse($response);
        } elseif (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        } elseif ($response instanceof BinaryFileResponse) {
            $response = $response->prepare(Request::capture());
        }

        return $response;
    }

    /**
     * Determines whether middleware should be skipped during request.
     * 这里还能跳过中间件的后门吗？
     * @return bool
     */
    protected function shouldSkipMiddleware()
    {
        return $this->bound('middleware.disable') && $this->make('middleware.disable') === true;
    }
}
