![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Core ![state](http://central.hoa-project.net/State/Core)

This library is the foundation —the core— of all libraries of Hoa. It provides
fundamental algorithms, paradigms and mechanisms, organized as follows:

  * Core: core of the core;
  * Consistency: adds consistency to PHP (`from`/`import`, `xcallable`, `dnew`,
    `curry` etc.);
  * Exception: homogenises exceptions and errors; 
  * Protocol: abstracts resources —and more— accesses (e.g. `hoa://Library` or
    `hoa://Application`);
  * Parameter: manages parameters of libraries;
  * Event: adds support of events and listeners;
  * Data: adds support of polymorphic data with high performance.

## Installation

The core can be placed where you want. We recommand `/usr/local/lib/Hoa` for
Unix-like systems and `C:\Program Files\Hoa` for Windows systems.

Then, you have to require `Core.php` and it is enough to use all libraries of
Hoa; thus:

```php
<?php

require '/usr/local/lib/Hoa/Core/Core.php';
var_dump(HOA); // bool(true)
```

With [Composer](https://getcomposer.org/), you do not need to require
`Core.php`, only `vendor/autoload.php` as usual.

## Quick usage

We propose a quick overview of some layers of the core.

### Exceptions and errors

Errors are unified to exceptions. Exceptions are of 3 kinds: idle, normal and
error. They support formatted messages, auto-catch, dedicated event channel etc.

### Protocol

`hoa://` defines an abstraction for application ressources (with roots `Data`
and `Application`) and another abstraction for libraries resources (with root
`Library`). For example:

```php
$conf = require 'hoa://Data/Etc/Configuration/Foo.php';
```

We can attach more than resources on this protocol. Example with the
[`Hoa\Registry`
library](http://central.hoa-project.net/Resource/Library/Registry):

```php
Hoa\Registry\Registry::set('foo', 'bar');
echo resolve('hoa://Library/Registry#foo'); // bar
```

### Events and listeners

Libraries can use events and listeners (which have some similarities). For
example, if we attach a function to the channel of exceptions:

```php
event('hoa://Event/Exception')->attach(function ($bucket) {
    $exception = $bucket->getData();
    echo 'Exception: ', $exception->getMessage(), "\n";
})

throw new Hoa\Core\Exception('Hello %s!', 0, 'Gordon');
// Exception: Hello Gordon!
```

Some libraries define their own channels, such as the [`Hoa\Stream`
library](http://central.hoa-project.net/Resource/Library/Stream) with, for
example, `hoa://Event/Stream/<stream-name>:close-before`, or
`hoa://Event/Log/<channel>` etc.

For listeners, we are closer to the emitter:

```php
$websocket = new Hoa\Websocket\Server(
    new Hoa\Socket\Server('tcp://127.0.0.1:8889')
);
$websocket->on('message', $callable);
$websocket->run();
```

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
