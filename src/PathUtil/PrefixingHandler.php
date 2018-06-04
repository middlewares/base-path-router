<?php
declare(strict_types = 1);

namespace Middlewares\PathUtil;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PrefixingHandler implements RequestHandlerInterface
{
    /** @var RequestHandlerInterface */
    private $wrapped;

    /** @var string */
    private $prefix;

    public function __construct(RequestHandlerInterface $wrapped, string $prefix)
    {
        $this->wrapped = $wrapped;
        $this->prefix = $prefix;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        return $this->wrapped->handle(
            $request->withUri(
                $uri->withPath(
                    $this->prefix . $uri->getPath()
                )
            )
        );
    }
}
