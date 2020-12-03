[![Build Status](https://api.travis-ci.org/joshralph93/password-policy.svg?branch=master)](https://travis-ci.org/joshralph93/password-policy)

A fluent password policy builder library. The package can be used stand-alone or easily added to Laravel. 

# Table of Contents
- [Install](#install)
- [Stand Alone Usage](#policy-builder)
- [Laravel](#larave)
    - [Install](#install-package)
    - [Define Policies](#define-policies)
    - [Setup Validation](#setup-validation)

## Install
```
$ composer require joshralph/password-policy
```

## Usage

### Policy Builder

```php
$builder = new \PasswordPolicy\PolicyBuilder(new \PasswordPolicy\Policy);
$builder->minLength(6)
    ->upperCase();
```

Any of the following methods may be chained on the builder class to build your password policy.

#### minLength(length)

##### length
Type: int

Minimum number of characters the password must contain.

#### maxLength(length)

##### length
Type: int

Maximum number of characters the password must contain.

#### upperCase([min])

##### min
Type: int

Minimum number of upper case characters the password must contain.

#### lowerCase([min])

##### min
Type: int

Minimum number of lower case characters the password must contain.

#### digits([min])

##### min
Type: int

Minimum number of numeric characters the password must contain.

#### specialCharacters([min])

##### min
Type: int

Minimum number of special characters the password must contain.

#### doesNotContain(phrases [,phrases])

##### phrases
Type: string|array

Phrases that the password should not contain

*Example*

```php
->doesNotContain('password', $firstName, $lastName)
```

#### minPassingRules(passesRequired, ruleSet)

##### passesRequired
Type: int

The minimum number of rules in the *ruleSet* that need to pass, in order for this rule to pass

##### ruleSet
Type: \Closure

A closure which is given a new PolicyBuilder instance.

*Example*

```php
// One of these rules must pass
->minPassingRules(1, function (PolicyBuilder $builder) {
    $builder->doesNotContain('password')
        ->minLength(10);
})
```

### Laravel

If you are a Laravel user, this package can seamlessly integrate with your validators.

#### Install Package

Begin by adding the below service provider.
```php
// config/app.php

'providers' => [
    // ...
    \PasswordPolicy\Providers\Laravel\PasswordPolicyServiceProvider::class,
],
```

#### Define Policies

Within an app service provider (e.g. AppServiceProvider.php) you can start defining password policies.

```php
// App/Providers/AppServiceProvider.php

// use PasswordPolicy\PolicyBuilder;
 

/**
 * Bootstrap any application services.
 *
 * @return void
 */
public function boot()
{
    \PasswordPolicy::define('default', function (PolicyBuilder $builder) {
        $builder->minLength(8)
            ->upperCase(3);
            // ...
    });
}
```

You can define as many policies as you require, however it's recommended to stick with 'default' when possible.

#### Setup Validation

Once you're policies have been defined, you're ready to start using the policies. A new 'password' validation rule is now available to use.

```php
// Request class

/**
 * Declare validation rules
 * 
 * @return array
 */
public function rules()
{
    return [
        // ...
        'password' => 'required|password'
    ];
}

```

The validator will use the 'default' policy by default. To use an alternative policy, add an additional parameter:
```php

'password' => 'required|password:admin'

```
