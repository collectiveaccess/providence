PHP GraphQL Client
=====

[![Latest Version](https://img.shields.io/github/release/softonic/graphql-client.svg?style=flat-square)](https://github.com/softonic/graphql-client/releases)
[![Software License](https://img.shields.io/badge/license-Apache%202.0-blue.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/softonic/graphql-client/master.svg?style=flat-square)](https://travis-ci.org/softonic/graphql-client)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/softonic/graphql-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/softonic/graphql-client/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/softonic/graphql-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/softonic/graphql-client)
[![Total Downloads](https://img.shields.io/packagist/dt/softonic/graphql-client.svg?style=flat-square)](https://packagist.org/packages/softonic/graphql-client)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/softonic/graphql-client.svg?style=flat-square)](http://isitmaintained.com/project/softonic/graphql-client "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/softonic/graphql-client.svg?style=flat-square)](http://isitmaintained.com/project/softonic/graphql-client "Percentage of issues still open")

PHP Client for [GraphQL](http://graphql.org/)

Installation
-------

Via composer:
```
composer require softonic/graphql-client
```

Documentation
-------

To instantiate a client with an OAuth2 provider:

``` php
<?php

$options = [
    'clientId' => 'myclient',
    'clientSecret' => 'mysecret',
];

$provider = new Softonic\OAuth2\Client\Provider\Softonic($options);

$config = ['grant_type' => 'client_credentials', 'scope' => 'myscope'];

$cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter();

$client = \Softonic\GraphQL\ClientBuilder::buildWithOAuth2Provider(
    'https://catalog.swarm.pub.softonic.one/graphql',
    $provider,
    $config,
    $cache
);

// Query Example
$query = <<<'QUERY'
query GetFooBar($idFoo: String, $idBar: String) {
  foo(id: $idFoo) {
    id_foo
    bar (id: $idBar) {
      id_bar
    }
  }
}
QUERY;
$variables = ['idFoo' => 'foo', 'idBar' => 'bar'];
$response = $client->query($query, $variables);

// Mutation Example
$mutation = <<<'MUTATION'
mutation ($foo: ObjectInput!){
  CreateObjectMutation (object: $foo) {
    status
  }
}
MUTATION;
$variables = [
    'foo' => [
        'id_foo' => 'foo', 
        'bar' => [
            'id_bar' => 'bar'
        ]
    ]
];
$response = $client->query($mutation, $variables);
```

To instantiate a client without OAuth2:

``` php
<?php
$client = \Softonic\GraphQL\ClientBuilder::build('https://catalog.swarm.pub.softonic.one/graphql');

$query = <<<'QUERY'
query GetFooBar($idFoo: String, $idBar: String) {
  foo(id: $idFoo) {
    id_foo
    bar (id: $idBar) {
      id_bar
    }
  }
}
QUERY;

$variables = [
    'idFoo' => 'foo',
    'idBar' => 'bar',
];
$response = $client->query($query, $variables);
```

Testing
-------

`softonic/graphql-client` has a [PHPUnit](https://phpunit.de) test suite and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).

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
