<?php

namespace Middlewares\Tests;

use Middlewares\PrefixRouter;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class PrefixRouterTest extends TestCase
{
    public function testRoutingToPrefixesWorks()
    {
        $middleware = new PrefixRouter([
            '/foo' => 'something',
            '/bar' => 'something else',
        ]);

        $fooResponse = $middleware->process(
            Factory::createServerRequest([], 'GET', '/foo/foopath'),
            $this->returningRequestAttribute()
        );

        $barResponse = $middleware->process(
            Factory::createServerRequest([], 'GET', '/bar'),
            $this->returningRequestAttribute()
        );

        $this->assertSame('something', (string) $fooResponse->getBody());
        $this->assertSame('something else', (string) $barResponse->getBody());
    }

    public function testRoutingToPrefixesUsesMostSpecificPrefix()
    {
        $middleware = new PrefixRouter([
            '/foo' => 'shorter',
            '/foo/longer' => 'longer',
        ]);

        $response = $middleware->process(
            Factory::createServerRequest([], 'GET', '/foo/longer/path'),
            $this->returningRequestAttribute()
        );

        $this->assertSame('longer', (string) $response->getBody());
    }

    public function testUnknownPrefixResultsIn404()
    {
        $middleware = new PrefixRouter([
            '/foo' => 'something',
        ]);

        $response = $middleware->process(
            Factory::createServerRequest([], 'GET', '/unknown'),
            $this->returningRequestAttribute()
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUnknownPrefixWithCustomDefaultHandler()
    {
        $middleware = (new PrefixRouter(['/foo' => 'something']))
            ->defaultHandler($this->returningRequestPath());

        $response = $middleware->process(
            Factory::createServerRequest([], 'GET', '/unknown'),
            $this->returningRequestAttribute()
        );

        $this->assertSame('/unknown', (string) $response->getBody());
    }

    public function testNextMiddlewareReceivesRequestWithoutPrefix()
    {
        $middleware = new PrefixRouter([
            '/foo' => 'foo.middleware',
        ]);

        $response = $middleware->process(
            Factory::createServerRequest([], 'GET', '/foo/mypath'),
            $this->returningRequestPath() // as handler
        );

        $this->assertSame('/mypath', (string) $response->getBody());
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
