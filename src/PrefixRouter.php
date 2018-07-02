<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class PrefixRouter implements Middleware
{
    /** @var array */
    private $middlewares;

    /** @var bool */
    private $stripPrefix = true;

    /** @var Handler */
    private $defaultHandler;

    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;

        // Make sure the longest path prefixes are matched first
        // (otherwise, a path /foo would always match, even when /foo/bar
        // should match).
        krsort($this->middlewares);
    }

    /**
     * Should the matched prefix be stripped from the request?
     *
     * This method allows disabling the stripping of matching request prefixes.
     * By default, the router strips matching prefixes from the URI path before
     * passing on the request to subsequent middleware / request handlers.
     *
     * When this method is called without parameters, the default (enable prefix
     * stripping) will be used.
     *
     * @param bool $strip
     * @return $this
     */
    public function stripPrefix($strip = true)
    {
        $this->stripPrefix = $strip;

        return $this;
    }

    /**
     * Provide a default request handler
     *
     * This request handler will be called with the current request whenever no
     * prefix matches. By default, an empty 404 response will be returned.
     *
     * @param Handler $handler
     * @return $this
     */
    public function defaultHandler(Handler $handler)
    {
        $this->defaultHandler = $handler;

        return $this;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $requestPath = $this->getNormalizedPath($request);

        foreach ($this->middlewares as $pathPrefix => $middleware) {
            if (strpos($requestPath, $pathPrefix) === 0) {
                return $handler->handle(
                    $this->unprefixedRequest($request, $pathPrefix)
                        ->withAttribute('request-handler', $middleware)
                );
            }
        }

        return $this->defaultResponse($request);
    }

    private function unprefixedRequest(Request $request, string $prefix): Request
    {
        if (! $this->stripPrefix) {
            return $request;
        }

        $uri = $request->getUri();
        return $request->withUri(
            $uri->withPath(
                substr($uri->getPath(), strlen($prefix))
            )
        );
    }

    private function getNormalizedPath(Request $request): string
    {
        $path = $request->getUri()->getPath();
        if (empty($path)) {
            $path = '/';
        }

        return $path;
    }

    private function defaultResponse(Request $request)
    {
        if ($this->defaultHandler) {
            return $this->defaultHandler->handle($request);
        }

        return Factory::createResponse(404);
    }
}
