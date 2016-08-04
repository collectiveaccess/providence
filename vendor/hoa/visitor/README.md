![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Visitor ![state](http://central.hoa-project.net/State/Visitor)

This library provides interfaces to apply the visitor pattern.

## Installation

With [Composer](http://getcomposer.org/), to include this library into your
dependencies, you need to require
[`hoa/visitor`](https://packagist.org/packages/hoa/visitor):

```json
{
    "require": {
        "hoa/visitor": "~1.0"
    }
}
```

Please, read the website to [get more informations about how to
install](http://hoa-project.net/Source.html).

## Quick usage

We propose to explain the basis of this library. We have two entities: an
element to visit and a visitor, for example a node of a tree and a dumper. The
element to visit will implement the `Hoa\Visitor\Element` interface and the
visitor will implement the `Hoa\Visitor\Visit` interface. The first one will ask
to implement the `accept` method in order to define what data it holds will be
visited. The second one will ask to implement the `visit` method which will
contain the visitor computations. We will find several examples in Hoa
libraries.

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
