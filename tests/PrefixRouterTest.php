<?php

namespace Middlewares\Tests;

use Middlewares\PrefixRouter;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class PrefixRouterTest extends TestCase
{
    public function testRoutingToPathsWorks()
    {
        $middleware = new PrefixRouter([
            '/foo' => $this->middlewareReturning('something'),
            '/bar' => $this->middlewareReturning('something else'),
        ]);

        $fooResponse = Dispatcher::run(
            [$middleware],
            Factory::createServerRequest([], 'GET', '/foo/foopath')
        );

        $barResponse = Dispatcher::run(
            [$middleware],
            Factory::createServerRequest([], 'GET', '/bar')
        );

        $this->assertInstanceOf(ResponseInterface::class, $fooResponse);
        $this->assertSame('something', (string) $fooResponse->getBody());

        $this->assertInstanceOf(ResponseInterface::class, $barResponse);
        $this->assertSame('something else', (string) $barResponse->getBody());
    }

    public function testRoutingToPathsUsesMostSpecificPrefix()
    {
        $middleware = new PrefixRouter([
            '/foo' => $this->middlewareReturning('shorter'),
            '/foo/longer' => $this->middlewareReturning('longer'),
        ]);

        $response = Dispatcher::run(
            [$middleware],
            Factory::createServerRequest([], 'GET', '/foo/longer/path')
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('longer', (string) $response->getBody());
    }

    public function testRoutingToPathsIsOptional()
    {
        $middleware = new PrefixRouter([
            '/foo' => $this->middlewareReturning('something'),
        ]);
        $fallback = $this->middlewareReturning('fallback');

        $response = Dispatcher::run(
            [$middleware, $fallback],
            Factory::createServerRequest([], 'GET', '/optional')
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('fallback', (string) $response->getBody());
    }

    public function testPathMiddlewareReceivesRequestWithoutPrefix()
    {
        $middleware = new PrefixRouter([
            '/foo' => $this->middlewareReturningRequestPath(),
        ]);

        $response = Dispatcher::run(
            [$middleware],
            Factory::createServerRequest([], 'GET', '/foo/mypath')
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('/mypath', (string) $response->getBody());
    }

    public function testPathMiddlewareWillCallHandlerWithPrefix()
    {
        $middleware = new PrefixRouter([
            '/foo' => $this->middlewareDispatchingToHandler(),
        ]);

        $response = Dispatcher::run(
            [$middleware, $this->middlewareReturningRequestPath()],
            Factory::createServerRequest([], 'GET', '/foo/mypath')
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('/foo/mypath', (string) $response->getBody());
    }

    private function middlewareReturning(string $val)
    {
        return new CallableHandler(function () use ($val) {
            return $val;
        });
    }

    private function middlewareReturningRequestPath()
    {
        return new CallableHandler(function ($req) {
            return $req->getUri()->getPath();
        });
    }

    private function middlewareDispatchingToHandler()
    {
        return new CallableHandler(function ($req, $h) {
            return $h->handle($req);
        });
    }
}
