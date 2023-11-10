Guzzle OAuth2 Middleware
=====

[![Latest Version](https://img.shields.io/github/release/softonic/guzzle-oauth2-middleware.svg?style=flat-square)](https://github.com/softonic/guzzle-oauth2-middleware/releases)
[![Software License](https://img.shields.io/badge/license-Apache%202.0-blue.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/softonic/guzzle-oauth2-middleware/master.svg?style=flat-square)](https://travis-ci.org/softonic/guzzle-oauth2-middleware)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/softonic/guzzle-oauth2-middleware.svg?style=flat-square)](https://scrutinizer-ci.com/g/softonic/guzzle-oauth2-middleware/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/softonic/guzzle-oauth2-middleware.svg?style=flat-square)](https://scrutinizer-ci.com/g/softonic/guzzle-oauth2-middleware)
[![Total Downloads](https://img.shields.io/packagist/dt/softonic/guzzle-oauth2-middleware.svg?style=flat-square)](https://packagist.org/packages/softonic/guzzle-oauth2-middleware)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/softonic/guzzle-oauth2-middleware.svg?style=flat-square)](http://isitmaintained.com/project/softonic/guzzle-oauth2-middleware "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/softonic/guzzle-oauth2-middleware.svg?style=flat-square)](http://isitmaintained.com/project/softonic/guzzle-oauth2-middleware "Percentage of issues still open")

This package provides middleware for [guzzle](https://github.com/guzzle/guzzle/) for handling OAuth2 token negotiation and renewal on expiry transparently. It accecpts PHP League's [OAuth 2.0 Clients](https://github.com/thephpleague/oauth2-client).

Installation
-------

To install, use composer:

```
composer require softonic/guzzle-oauth2-middleware
```

Usage
-------

``` php
<?php
$options = [
    'clientId' => 'myclient',
    'clientSecret' => 'mysecret'
];

// Any provider extending League\OAuth2\Client\Provider\AbstractProvider will do
$provider = new Softonic\OAuth2\Client\Provider\Softonic($options);

// Send OAuth2 parameters and use token_options for any other parameters your OAuth2 provider needs
$config = ['grant_type' => 'client_credentials', 'scope' => 'myscope', 'token_options' => ['audience' => 'test_audience']];

// Any implementation of PSR-6 Cache will do
$cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter();

$client = \Softonic\OAuth2\Guzzle\Middleware\ClientBuilder::build(
    $provider,
    $config,
    $cache,
    ['base_uri' => 'https://foo.bar/']
);
$response = $client->request('POST', 'qux);


```


Testing
-------

`softonic/guzzle-oauth2-middleware` has a [PHPUnit](https://phpunit.de) test suite and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).

To run the tests, run the following command from the project folder.

``` bash
$ docker-compose run test
```

To run interactively using [PsySH](http://psysh.org/):
``` bash
$ docker-compose run psysh
```

License
-------

The Apache 2.0 license. Please see [LICENSE](LICENSE) for more information.

[PSR-2]: http://www.php-fig.org/psr/psr-2/
[PSR-4]: http://www.php-fig.org/psr/psr-4/
