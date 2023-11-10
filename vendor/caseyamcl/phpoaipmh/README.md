PHPOAIPMH
=========

## A PHP OAI-PMH harvester client library

[![Latest Version](https://img.shields.io/github/release/caseyamcl/phpoaipmh.svg)](https://github.com/caseyamcl/phpoaipmh/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/caseyamcl/Phpoaipmh.svg)](https://packagist.org/packages/caseyamcl/Phpoaipmh)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Github Build](https://github.com/caseyamcl/phpoaipmh/workflows/Github%20Build/badge.svg)](https://github.com/caseyamcl/phpoaipmh/actions?query=workflow%3A%22Github+Build%22)
[![Code coverage](https://github.com/caseyamcl/toc/blob/master/coverage.svg)](coverage.svg)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/caseyamcl/phpoaipmh.svg)](https://scrutinizer-ci.com/g/caseyamcl/phpoaipmh/)

This library provides an interface to harvest OAI-PMH metadata
from any [OAI 2.0 compliant endpoint](http://www.openarchives.org/OAI/openarchivesprotocol.html#ListMetadataFormats).

Features:
* PSR-12 Compliant
* Composer-compatible
* Unit-tested
* Prefers Guzzle (v6, v7, or v5) for HTTP transport layer, but can fall back to cURL, or implement your own
* Easy-to-use iterator that hides all the HTTP junk necessary to get paginated records

## Installation Options

Install via [Composer](http://getcomposer.org/) by including the following in your composer.json file: 
 
    {
        "require": {
            "caseyamcl/phpoaipmh": "^3.0",
            "guzzlehttp/guzzle":   "^7.0"
        }
    }

Or, drop the `src` folder into your application and use a PSR-4 autoloader to include the files.

*Note:* Guzzle v6.0 or v7.0 is recommended, but if you do not wish to use Guzzle v6 for whatever reason, you can
use any one of the following:

* Guzzle 5.0 - You can use Guzzle v5 instead of v6.
* cURL - This library will fall back to using cURL if Guzzle is not installed.
* Build your own - You can use a different HTTP client library by passing your own
  implementation of the `Phpoaipmh\HttpAdapter\HttpAdapterInterface` to the `Phpoaipmh\Client` constructor.

## Upgrading

There are several backwards-incompatible API improvements in major version changes.  See <UPGRADE.md> for
information about how to upgrade your code to use the new version.

## Usage

Setup a new endpoint client:

```php
// Quick and easy 'build' method 
$myEndpoint = \Phpoaipmh\Endpoint::build('http://some.service.com/oai');

// Or, create your own client instance and pass it to `Endpoint::__construct()` 
$client = new \Phpoaipmh\Client('http://some.service.com/oai');
$myEndpoint = new \Phpoaipmh\Endpoint($client);
```

Get basic information:

```php
// Result will be a SimpleXMLElement object
$result = $myEndpoint->identify();
var_dump($result);

// Results will be iterator of SimpleXMLElement objects
$results = $myEndpoint->listMetadataFormats();
foreach($results as $item) {
    var_dump($item);
}
```

### Retrieving records

```php
// Recs will be an iterator of SimpleXMLElement objects
$recs = $myEndpoint->listRecords('someMetaDataFormat');

// The iterator will continue retrieving items across multiple HTTP requests.
// You can keep running this loop through the *entire* collection you
// are harvesting.  All OAI-PMH and HTTP pagination logic is hidden neatly
// behind the iterator API.
foreach($recs as $rec) {
    var_dump($rec);
}
```

### Limiting record retrieval by date/time

Simply pass instances of `DateTimeInterface` to `Endpoint::listRecords()` or `Endpoint::listIdentifiers()` as
arguments two and three, respectively.

If you want one and not another, you can pass `null` for either argument.

```php

// Retrieve records from Jan 1, 2018 through October 1, 2018
$recs = $myEndpoint->listRecords('someMetaDataFormat', new \DateTime('2018-01-01'), new \DateTime('2018-10-01'));

foreach($recs as $rec) {
    var_dump($rec);
}
```

### Setting date/time granularity

This library will attempt to retrieve granularity automatically from the OAI-PMH
`Identify` endpoint, but in case you want to set it your self manually, you can pass
an instance of `Granularity` to the `Endpoint` constructor:

```php
use Phpoaipmh\Client,
    Phpoaipmh\Endpoint,
    Phpoaipmh\Granularity;

$client = new Client('http://some.service.com/oai');
$myEndpoint = new Endpoint($client, Granularity::DATE_AND_TIME);
```

### Record sets

Some OAI-PMH endpoints sub-divide records into [sets](https://www.openarchives.org/OAI/openarchivesprotocol.html#Set).

You can list the record sets available for a given endpoint by calling `Endpoint::listSets()`:

```php
foreach ($myEndpoint->listSets() as $set) {
    var_dump($set);
}
```

You can specify the set you wish to retrieve by passing the set name as the fourth argument to 
`Endpoint::listIdentifiers()` or `Endpoint::listRecords()`:

```php
foreach ($myEndpoint->listRecords('someMetadataFormat', null, null 'someSetName') as $record) {
    var_dump($record);
}
```

### Getting total record count
 
Some endpoints provide a total record count for your query.  If the endpoint 
provides this, you can access this value by calling: `RecordIterator::getTotalRecordCount()`.

If the endpoint does not provide this count, then `RecordIterator::getTotalRecordCount()`
returns `null`.

```php
$iterator = $myEndpoint->listRecords('someMetaDataFormat');
echo "Total count is " . ($iterator->getTotalRecordCount() ?: 'unknown');
```

## Handling Results

Depending on the verb you use, the library will send back either a `SimpleXMLELement`
or an iterator containing `SimpleXMLElement` objects.

* For `identify` and `getRecord`, a `SimpleXMLElement` object is returned
* For `listMetadataFormats`, `listSets`, `listIdentifiers`, and `listRecords` a `Phpoaipmh\ResponseIterator` is returned

The `Phpoaipmh\ResponseIterator` object encapsulates the logic to iterate through paginated sets of records.


## Handling Errors

This library will throw different exceptions under different circumstances:

* HTTP request errors will generate a `Phpoaipmh\Exception\HttpException`
* Response body parsing issues (e.g. invalid XML) will generate a `Phpoaipmh\Exception\MalformedResponseException`
* OAI-PMH protocol errors (e.g. invalid verb or missing params) will generate a `Phpoaipmh\Exception\OaipmhException`

All exceptions extend the `Phpoaipmh\Exception\BaseoaipmhException` class.


## Customizing Default Request Options

You can customize the default request options (for example, request timeout) for both cURL and Guzzle 
clients by building the adapter objects manually.

If you're using **Guzzle v6**, you can set default options by building your own
Guzzle client and [setting parameters in the constructor](http://docs.guzzlephp.org/en/stable/quickstart.html):

```php

use GuzzleHttp\Client as GuzzleClient;
use Phpoaipmh\Client;
use Phpoaipmh\Endpoint;
use Phpoaipmh\HttpAdapter\GuzzleAdapter;

$guzzle = new GuzzleAdapter(new GuzzleClient([
    'connect_timeout' => 2.0,
    'timeout'         => 10.0
]));

$myEndpoint = new Endpoint(new Client('http://some.service.com/oai', $guzzle));

```

If you're using **cURL**, you can set request options by passing them in as an 
array of key/value items to `CurlAdapter::setCurlOpts()`:

```php
use Phpoaipmh\Client,
    Phpoaipmh\HttpAdapter\CurlAdapter;

$adapter = new CurlAdapter();
$adapter->setCurlOpts([CURLOPT_TIMEOUT => 120]);
$client = new Client('http://some.service.com/oai', $adapter);

$myEndpoint = new Endpoint($client);
```

If you're using **Guzzle v5**, you can set default options by building your own
Guzzle client, 

```php
use Phpoaipmh\Client,
    Phpoaipmh\HttpAdapter\GuzzleAdapter;

$adapter = new GuzzleAdapter();
$adapter->getGuzzleClient()->setDefaultOption('timeout', 120);
$client = new Client('http://some.service.com/oai', $adapter);

$myEndpoint = new Endpoint($client);
```

## Dealing with XML Namespaces

Many OAI-PMH XML documents make use of XML Namespaces.  For non-XML experts, it can be confusing to implement
these in PHP.  SitePoint has a brief but excellent [overview of how to use Namespaces in SimpleXML](http://www.sitepoint.com/simplexml-and-namespaces/).


## Iterator Metadata

The `Phpoaipmh\RecordIterator` iterator contains some helper methods:

* `getNumRequests()` - Returns the number of HTTP requests made thus far
* `getNumRetrieved()` - Returns the number of individual records retrieved
* `reset()` - Resets the iterator, which will restart the record retrieval from scratch.


## Handling 503 `Retry-After` Responses

Some OAI-PMH endpoints employ rate-limiting so that you can only make X number
of requests in a given time period.  These endpoints will return a `503 Retry-AFter`
HTTP status code if your code generates too many HTTP requests too quickly.

### Guzzle v6

If you have installed [Guzzle v6](http://guzzlephp.org), then you can use the 
[Guzzle-Retry-Middleware](https://github.com/caseyamcl/guzzle_retry_middleware) library
to automatically handle OAI-PMH endpoint rate limiting rules.

First, include the middleware as a dependency in your app:

```bash
$ composer require caseyamcl/guzzle_retry_middleware
```

Then, when loading the Phpoaipmh libraries, build a Guzzle client manually, and add
the middleware to the stack.  Example:

```php

use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;

// Setup the the Guzzle client with the retry middleware
$stack = HandlerStack::create();
$stack->push(GuzzleRetryMiddleware::factory());
$guzzleClient = new GuzzleClient(['handler' => $stack]);

// Setup the Guzzle adpater and PHP OAI-PMH client
$guzzleAdapter = new \Phpoaipmh\HttpAdapter\GuzzleAdapter($guzzleClient);
$client  = new \Phpoaipmh\Client('http://some.service.com/oai', $guzzleAdapter);

```
This will create a client that automatically retries requests when OAI-PMH endpoints send
`503` rate-limiting responses.

The Retry middleware contains a number of options.  Refer to the [README for that package](https://github.com/caseyamcl/guzzle_retry_middleware)
for details.

### Guzzle v5

If you have installed [Guzzle v5](http://docs.guzzlephp.org/en/5.3/overview.html), then you can use the
[Retry-Subscriber](https://github.com/guzzle/retry-subscriber) to automatically
handle OAI-PMH endpoint rate-limiting rules.

First, include the retry-subscriber as a dependency in your `composer.json`:

    require: {
        /* ... */
       "guzzlehttp/retry-subscriber": "~2.0"
    }
    
Then, when loading the Phpoaipmh libraries, instantiate the Guzzle adapter
manually, and add the subscriber as indicated in the code below:

```php
// Create a Retry Guzzle Subscriber
$retrySubscriber = new \GuzzleHttp\Subscriber\Retry\RetrySubscriber([
    'delay' => function($numRetries, \GuzzleHttp\Event\AbstractTransferEvent $event) {
        $waitSecs = $event->getResponse()->getHeader('Retry-After') ?: '5';
        return ($waitSecs * 1000) + 1000; // wait one second longer than the server said to
    },
    'filter' => \GuzzleHttp\Subscriber\Retry\RetrySubscriber::createStatusFilter(),
]);

// Manually create a Guzzle HTTP adapter
$guzzleAdapter = new \Phpoaipmh\HttpAdapter\GuzzleAdapter();
$guzzleAdapter->getGuzzleClient()->getEmitter()->attach($retrySubscriber);

$client  = new \Phpoaipmh\Client('http://some.service.com/oai', $guzzleAdapter);
```

This will create a client that automatically retries requests when OAI-PMH endpoints send
`503` rate-limiting responses. 


## Sending Arbitrary Query Parameters

If you wish to send arbitrary HTTP query parameters with your requests, you can
send them via the `\Phpoaipmh\Client` class:

    $client = new \Phpoaipmh\Client('http://some.service.com/oai');
    $client->request('Identify', ['some' => 'extra-param']);

Alternatively, if you wish to send arbitrary parameters while taking advantage of the
convenience of the `\Phpoaipmh\Endpoint` class, you can use the [Guzzle Param Middleware](emarref/guzzle-param-middleware)
library:

First, include the middleware as a dependency in your app:

```bash
$ composer require emarref/guzzle-param-middleware
```

Then, when loading the Phpoaipmh libraries, build a Guzzle client manually, and add
the middleware to the stack.  Example:

```php

use Emarref\Guzzle\Middleware\ParamMiddleware
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

// Setup the the Guzzle stack
$stack = HandlerStack()::create();
$stack->push(new ParamMiddleware(['api_key' => 'xyz123']));

// Setup Guzzle client, adapter, and PHP OAI-PMH client
$guzzleClient = new GuzzleClient(['handler' => $stack])
$guzzleAdapter = new \Phpoaipmh\HttpAdapter\GuzzleAdapter($guzzleClient)
$client  = new \Phpoaipmh\Client('http://some.service.com/oai', $guzzleAdapter);
```

This will add the specified query parameters to all requests for the client.

### Sending arbitrary query parameters with Guzzle v5

If you are using Guzzle v5, you can use the Guzzle event system:

```php
// Create a function or class to add parameters to a request
$addParamsListener = function(\GuzzleHttp\Event\BeforeEvent $event) {
   $req = $event->getRequest();
   $req->getQuery()->add('api_key', 'xyz123');

   // You could do other things to the request here, too, like adding a header..
   $req->addHeader('Some-Header', 'some-header-value');
};

// Manually create a Guzzle HTTP adapter
$guzzleAdapter = new \Phpoaipmh\HttpAdapter\GuzzleAdapter();
$guzzleAdapter->getGuzzleClient()->getEmitter()->on('before', $addParamsListener);

$client  = new \Phpoaipmh\Client('http://some.service.com/oai', $guzzleAdapter);
```

## Implementation Tips

Harvesting data from a OAI-PMH endpoint can be a time-consuming task, especially when there are lots of records.
Typically, this kind of task is done via a CLI script or background process that can run for a long time.
It is not normally a good idea to make it part of a web request.

## Credits

* [Casey McLaughlin](http://github.com/caseyamcl)
* [Christian Scheb](https://github.com/scheb)
* [Matthias Vandermaesen](https://github.com/netsensei)
* [Sean Blommaert](https://github.com/sblommaert)
* [Valery Buchinsky](https://github.com/vbuc)
* [All Contributors](https://github.com/caseyamcl/phpoaipmh/contributors)

## License

MIT License; see [LICENSE](LICENSE.md) file for details
