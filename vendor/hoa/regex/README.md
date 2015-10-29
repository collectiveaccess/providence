![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Regex ![state](http://central.hoa-project.net/State/Regex)

This library provides tools to analyze regular expressions and generate strings
based on regular expressions ([Perl Compatible Regular
Expressions](http://pcre.org)).

## Installation

With [Composer](http://getcomposer.org/), to include this library into your
dependencies, you need to require
[`hoa/regex`](https://packagist.org/packages/hoa/regex):

```json
{
    "require": {
        "hoa/regex": "~0.0"
    }
}
```

Please, read the website to [get more informations about how to
install](http://hoa-project.net/Source.html).

## Quick usage

As a quick overview, we propose to see two examples. First, analyze a regular
expression, i.e. lex, parse and produce an AST. Second, generate strings based
on a regular expression by visiting its AST with an isotropic random approach.

### Analyze regular expressions

We need the [`Hoa\Compiler`
library](http://central.hoa-project.net/Resource/Library/Compiler) to lex, parse
and produce an AST of the following regular expression: `ab(c|d){2,4}e?`. Thus:

```php
// 1. Read the grammar.
$grammar  = new Hoa\File\Read('hoa://Library/Regex/Grammar.pp');

// 2. Load the compiler.
$compiler = Hoa\Compiler\Llk\Llk::load($grammar);

// 3. Lex, parse and produce the AST.
$ast      = $compiler->parse('ab(c|d){2,4}e?');

// 4. Dump the result.
$dump     = new Hoa\Compiler\Visitor\Dump();
echo $dump->visit($ast);

/**
 * Will output:
 *     >  #expression
 *     >  >  #concatenation
 *     >  >  >  token(literal, a)
 *     >  >  >  token(literal, b)
 *     >  >  >  #quantification
 *     >  >  >  >  #alternation
 *     >  >  >  >  >  token(literal, c)
 *     >  >  >  >  >  token(literal, d)
 *     >  >  >  >  token(n_to_m, {2,4})
 *     >  >  >  #quantification
 *     >  >  >  >  token(literal, e)
 *     >  >  >  >  token(zero_or_one, ?)
 */
```

We read that the whole expression is composed of a single concatenation of two
tokens: `a` and `b`, followed by a quantification, followed by another
quantification. The first quantification is an alternation of (a choice betwen)
two tokens: `c` and `d`, between 2 to 4 times. The second quantification is the
`e` token that can appear zero or one time.

We can visit the tree with the help of the [`Hoa\Visitor`
library](http://central.hoa-project.net/Resource/Library/Visitor).

### Generate strings based on regular expressions

To generate strings based on the AST of a regular expressions, we will use the
`Hoa\Regex\Visitor\Isotropic` visitor:

```php
$generator = new Hoa\Regex\Visitor\Isotropic(new Hoa\Math\Sampler\Random());
echo $generator->visit($ast);

/**
 * Could output:
 *     abdcde
 */
```

Strings are generated at random and match the given regular expression.

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
