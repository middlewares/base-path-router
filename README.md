# middlewares/prefix-router

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]

A middleware dispatching to other middleware stacks, based on different path prefixes.

## Requirements

* PHP >= 7.0
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http message implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/prefix-router](https://packagist.org/packages/middlewares/prefix-router).

```sh
composer require middlewares/prefix-router
```

## Example

```php
$dispatcher = new Dispatcher([
	new Middlewares\PrefixRouter([
	    '/prefix1' => $middleware1,
	    '/prefix2' => $middleware2,
	    '/prefix3' => $middleware3,
	])
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/prefix-router.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/prefix-router/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/prefix-router.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/prefix-router.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/prefix-router
[link-travis]: https://travis-ci.org/middlewares/prefix-router
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/prefix-router
[link-downloads]: https://packagist.org/packages/middlewares/prefix-router
