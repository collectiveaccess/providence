![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Math ![state](http://central.hoa-project.net/State/Math)

This library provides tools around mathemetical operations.

## Installation

With [Composer](http://getcomposer.org/), to include this library into your
dependencies, you need to require
[`hoa/math`](https://packagist.org/packages/hoa/math):

```json
{
    "require": {
        "hoa/math": "~0.0"
    }
}
```

Please, read the website to [get more informations about how to
install](http://hoa-project.net/Source.html).

## Quick usage

We propose a quick overview of one feature: evaluation of arithmetical
expressions.

### Evaluation of arithmetical expressions

The `hoa://Library/Math/Arithmetic.pp` describes the form of an arithmetical
expression. Therefore, we will use the classical workflow when manipulating a
grammar, that involves the [`Hoa\Compiler`
library](http://central.hoa-project.net/Resource/Library/Compiler) and the
`Hoa\Math\Visitor\Arithmetic` class.

```php
// 1. Load the compiler.
$compiler = Hoa\Compiler\Llk::load(
    new Hoa\File\Read('hoa://Library/Math/Arithmetic.pp')
);

// 2. Load the visitor, aka the “evaluator”.
$visitor    = new Hoa\Math\Visitor\Arithmetic();

// 3. Declare the expression.
$expression = '1 / 2 / 3 + 4 * (5 * 2 - 6) * PI / avg(7, 8, 9)';

// 4. Parse the expression.
$ast        = $compiler->parse($expression);

// 5. Evaluate.
var_dump(
    $visitor->visit($ast)
);

/**
 * Will output:
 *     float(6.4498519738463)
 */

// Bonus. Print the AST of the expression.
$dump = new Hoa\Compiler\Visitor\Dump();
echo $dump->visit($ast);

/**
 * Will output:
 *     >  #addition
 *     >  >  #division
 *     >  >  >  token(number, 1)
 *     >  >  >  #division
 *     >  >  >  >  token(number, 2)
 *     >  >  >  >  token(number, 3)
 *     >  >  #multiplication
 *     >  >  >  token(number, 4)
 *     >  >  >  #multiplication
 *     >  >  >  >  #group
 *     >  >  >  >  >  #substraction
 *     >  >  >  >  >  >  #multiplication
 *     >  >  >  >  >  >  >  token(number, 5)
 *     >  >  >  >  >  >  >  token(number, 2)
 *     >  >  >  >  >  >  token(number, 6)
 *     >  >  >  >  #division
 *     >  >  >  >  >  token(constant, PI)
 *     >  >  >  >  >  #function
 *     >  >  >  >  >  >  token(id, avg)
 *     >  >  >  >  >  >  token(number, 7)
 *     >  >  >  >  >  >  token(number, 8)
 *     >  >  >  >  >  >  token(number, 9)
 */
```

We can add functions and constants on the visitor, thanks to the `addFunction`
and `addConstant` methods. Thus, we will add the `rand` function (with 2
parameters) and the `ANSWER` constant, set to 42:

```php
$visitor->addFunction('rand', function ($min, $max) {
    return mt_rand($min, $max);
});
$visitor->addConstant('ANSWER', 42);

$expression = 'rand(ANSWER / 2, ANSWER * 2)'
var_dump(
    $visitor->visit($compiler->parse($expression))
);

/**
 * Could output:
 *     int(53)
 */
```

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
