<?php

namespace Middlewares\Tests;

use Middlewares\BasePathRouter;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class BasePathRouterTest extends TestCase
{
    public function testRoutingToPrefixesWorks()
    {
        $router = new BasePathRouter([
            '/foo' => 'something',
            '/bar' => 'something else',
        ]);

        $fooResponse = $router->process(
            Factory::createServerRequest([], 'GET', '/foo/foopath'),
            $this->returningRequestAttribute()
        );

        $barResponse = $router->process(
            Factory::createServerRequest([], 'GET', '/bar'),
            $this->returningRequestAttribute()
        );

        $this->assertSame('something', (string) $fooResponse->getBody());
        $this->assertSame('something else', (string) $barResponse->getBody());
    }

    public function testRoutingToPrefixesUsesMostSpecificPrefix()
    {
        $router = new BasePathRouter([
            '/foo' => 'shorter',
            '/foo/longer' => 'longer',
        ]);

        $response = $router->process(
            Factory::createServerRequest([], 'GET', '/foo/longer/path'),
            $this->returningRequestAttribute()
        );

        $this->assertSame('longer', (string) $response->getBody());
    }

    public function testUnknownPrefixResultsIn404()
    {
        $router = new BasePathRouter([
            '/foo' => 'something',
        ]);

        $response = $router->process(
            Factory::createServerRequest([], 'GET', '/unknown'),
            $this->returningRequestAttribute()
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUnknownPrefixWithCustomDefaultHandler()
    {
        $router = (new BasePathRouter(['/foo' => 'something']))
            ->defaultHandler($this->returningRequestPath());

        $response = $router->process(
            Factory::createServerRequest([], 'GET', '/unknown'),
            $this->returningRequestAttribute()
        );

        $this->assertSame('/unknown', (string) $response->getBody());
    }

    public function testNextMiddlewareReceivesRequestWithoutPrefix()
    {
        $router = new BasePathRouter([
            '/foo' => 'foo.middleware',
        ]);

        $response = $router->process(
            Factory::createServerRequest([], 'GET', '/foo/mypath'),
            $this->returningRequestPath() // as handler
        );

        $this->assertSame('/mypath', (string) $response->getBody());
    }

    public function testPrefixStrippingCanBeDisabled()
    {
        $router = (new BasePathRouter(['/foo' => 'foo.middleware']))
            ->stripPrefix(false);

        $response = $router->process(
            Factory::createServerRequest([], 'GET', '/foo/mypath'),
            $this->returningRequestPath() // as handler
        );

        $this->assertSame('/foo/mypath', (string) $response->getBody());
    }

    private function returningRequestAttribute()
    {
        return new CallableHandler(function ($req) {
            return $req->getAttribute('request-handler');
        });
    }

    private function returningRequestPath()
    {
        return new CallableHandler(function ($req) {
            return $req->getUri()->getPath();
        });
    }
}
