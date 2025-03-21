<?php

declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\BasePathRouter;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class BasePathRouterTest extends TestCase
{
    /**
     * @return array<array<string|int>>
     */
    public function routerDataProvider(): array
    {
        return [
            ['/foo/foopath', 'something --- /foopath', 200],
            ['/bar', 'something else --- /', 200],
            ['/not-found', '', 404],
        ];
    }

    /**
     * @dataProvider routerDataProvider
     */
    public function testRoutingToPrefixesWorks(string $path, string $body, int $statusCode): void
    {
        $response = Dispatcher::run(
            [
                new BasePathRouter([
                    '/foo' => 'something',
                    '/bar' => 'something else',
                ]),

                function ($request) {
                    echo $request->getAttribute('request-handler');
                    echo ' --- ';
                    echo $request->getUri()->getPath();
                },
            ],
            Factory::createServerRequest('GET', $path)
        );

        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame($statusCode, $response->getStatusCode());
    }

    /**
     * @return array<array<string>>
     */
    public function slashRouterDataProvider(): array
    {
        return [
            ['/', 'base route --- /'],
            ['/baz', 'base route --- /baz'],
        ];
    }

    /**
     * @dataProvider slashRouterDataProvider
     */
    public function testRoutingToSlashPrefixWorks(string $path, string $body): void
    {
        $response = Dispatcher::run(
            [
                new BasePathRouter([
                    '/foo' => 'something',
                    '/' => 'base route',
                ]),

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

    public function testContinueOnError(): void
    {
        $response = Dispatcher::run(
            [
                (new BasePathRouter([
                    '/foo' => 'something',
                    '/' => 'base route',
                ]))->continueOnError(),

                function ($request) {
                    echo 'Fallback';
                },
            ],
            Factory::createServerRequest('GET', '/not-found')
        );

        $this->assertSame('Fallback', (string) $response->getBody());
    }

    public function testRoutingToPrefixesUsesMostSpecificPrefix(): void
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

    public function testNextMiddlewareReceivesRequestWithoutPrefix(): void
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

    public function testPrefixStrippingCanBeDisabled(): void
    {
        $router = (new BasePathRouter(['/foo' => 'foo.middleware']))
            ->stripPrefix(false);

        $response = $router->process(
            Factory::createServerRequest('GET', '/foo/mypath'),
            self::returningRequestPath()
        );

        $this->assertSame('/foo/mypath', (string) $response->getBody());
    }

    private static function returningRequestAttribute(): CallableHandler
    {
        return new CallableHandler(function ($req) {
            return $req->getAttribute('request-handler');
        });
    }

    private static function returningRequestPath(): CallableHandler
    {
        return new CallableHandler(function ($req) {
            return $req->getUri()->getPath();
        });
    }
}
