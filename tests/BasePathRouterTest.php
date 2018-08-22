<?php

namespace Middlewares\Tests;

use Middlewares\BasePathRouter;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class BasePathRouterTest extends TestCase
{
    public function routerDataProvider()
    {
        return [
            ['/foo/foopath', 'something --- /foopath'],
            ['/bar', 'something else --- /'],
            ['/not-found', 'unknown --- /not-found'],
        ];
    }

    /**
     * @dataProvider routerDataProvider
     */
    public function testRoutingToPrefixesWorks(string $path, string $body)
    {
        $response = Dispatcher::run(
            [
                (new BasePathRouter([
                    '/foo' => 'something',
                    '/bar' => 'something else',
                ]))->defaultHandler('unknown'),

                function ($request) {
                    echo $request->getAttribute('request-handler');
                    echo ' --- ';
                    echo $request->getUri()->getPath();
                },
            ],
            Factory::createServerRequest('GET', $path)
        );

        $this->assertSame($body, (string) $response->getBody());
    }

    public function slashRouterDataProvider()
    {
        return [
            ['/', 'base route --- /'],
            ['/baz', 'base route --- /baz'],
        ];
    }

    /**
     * @dataProvider slashRouterDataProvider
     */
    public function testRoutingToSlashPrefixWorks(string $path, string $body)
    {
        $response = Dispatcher::run(
            [
                (new BasePathRouter([
                    '/foo' => 'something',
                    '/' => 'base route',
                ]))->defaultHandler('unknown'),

                function ($request) {
                    echo $request->getAttribute('request-handler');
                    echo ' --- ';
                    echo $request->getUri()->getPath();
                },
            ],
            Factory::createServerRequest('GET', $path)
        );

        $this->assertSame($body, (string) $response->getBody());
    }

    public function testRoutingToPrefixesUsesMostSpecificPrefix()
    {
        $router = new BasePathRouter([
            '/foo' => 'shorter',
            '/foo/longer' => 'longer',
        ]);

        $response = $router->process(
            Factory::createServerRequest('GET', '/foo/longer/path'),
            self::returningRequestAttribute()
        );

        $this->assertSame('longer', (string) $response->getBody());
    }

    public function testUnknownPrefixResultsIn404()
    {
        $router = new BasePathRouter([
            '/foo' => 'something',
        ]);

        $response = $router->process(
            Factory::createServerRequest('GET', '/unknown'),
            self::returningRequestAttribute()
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testNextMiddlewareReceivesRequestWithoutPrefix()
    {
        $router = new BasePathRouter([
            '/foo' => 'foo.middleware',
        ]);

        $response = $router->process(
            Factory::createServerRequest('GET', '/foo/mypath'),
            self::returningRequestPath()
        );

        $this->assertSame('/mypath', (string) $response->getBody());
    }

    public function testPrefixStrippingCanBeDisabled()
    {
        $router = (new BasePathRouter(['/foo' => 'foo.middleware']))
            ->stripPrefix(false);

        $response = $router->process(
            Factory::createServerRequest('GET', '/foo/mypath'),
            self::returningRequestPath()
        );

        $this->assertSame('/foo/mypath', (string) $response->getBody());
    }

    private static function returningRequestAttribute()
    {
        return new CallableHandler(function ($req) {
            return $req->getAttribute('request-handler');
        });
    }

    private static function returningRequestPath()
    {
        return new CallableHandler(function ($req) {
            return $req->getUri()->getPath();
        });
    }
}
