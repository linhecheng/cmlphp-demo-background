<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Psr15 RequestHandlerInterface实现
 * *********************************************************** */

namespace Cml\Http\Message;

use Cml\Exception\ResponseNotFoundException;
use Cml\Interfaces\Middleware;
use Cml\Interfaces\RequestHandler as RequestHandlerInterface;
use SplQueue;

/**
 * Psr15 RequestHandlerInterface实现
 *
 * @package Cml\Http\Message
 */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var SplQueue
     */
    protected $middlewareList;

    /**
     * RequestHandler constructor.
     *
     * @param Middleware[] $middlewareList
     */
    public function __construct($middlewareList = [])
    {
        $this->middlewareList = new SplQueue();
        foreach ((array)$middlewareList as $middleware) {
            $this->add($middleware);
        }
    }

    /**
     * 添加一个中间件
     *
     * @param Middleware|callable $middleware
     */
    public function add($middleware)
    {
        if (is_callable($middleware)) {
            $middleware = static::decorateCallableMiddleware($middleware);
        }
        $this->middlewareList->enqueue($middleware);
    }

    /**
     * 获取所有中间件
     *
     * @return Middleware[]
     */
    public function all()
    {
        $middlewareList = [];
        foreach ($this->middlewareList as $middleware) {
            $middlewareList[] = $middleware;
        }

        return $middlewareList;
    }

    /**
     * 分发请求给中间件并获得 psr7 响应.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws ResponseNotFoundException
     *
     */
    public function handle(Request $request)
    {
        $response = $this->getNextHandler()->handle($request);
        if (!$response instanceof Response) {
            throw new ResponseNotFoundException('Last middleware executed did not return a response.');
        }

        return $response;
    }

    /**
     * 获取执行器
     *
     * @return RequestHandlerInterface
     */
    protected function getNextHandler()
    {
        return new class (function ($request) {
            if ($this->middlewareList->isEmpty()) {
                throw new ResponseNotFoundException('The queue was exhausted, with no response returned');
            }
            $middleware = $this->middlewareList->dequeue();
            $response = $middleware->process($request, $this->getNextHandler());
            if (!$response instanceof Response) {
                throw new ResponseNotFoundException(sprintf('Unexpected middleware result: %s', gettype($response)));
            }
            return $response;
        }) implements RequestHandlerInterface
        {
            /**
             * @var callable
             */
            protected $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            /**
             * {@inheritdoc}
             */
            public function handle(Request $request)
            {
                return call_user_func($this->callback, $request);
            }
        };

    }

    /**
     * 装饰闭包类型的中间件
     *
     * @param callable $middleware
     *
     * @return Middleware
     */
    protected static function decorateCallableMiddleware(callable $middleware)
    {
        return new class ($middleware) implements Middleware
        {
            /**
             *闭包类型的中间件
             * @var callable
             */
            protected $callable;

            public function __construct(callable $callback)
            {
                $this->callable = $callback;
            }

            /**
             * {@inheritdoc}
             */
            public function process(Request $request, RequestHandlerInterface $handler)
            {
                return call_user_func($this->callable, $request, $handler);
            }
        };
    }

}

