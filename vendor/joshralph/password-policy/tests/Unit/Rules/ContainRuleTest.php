<?php namespace PasswordPolicy\Tests\Unit\Rules;

use PasswordPolicy\Rules\ContainRule;

class ContainRuleTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_tests_a_string_contains_a_string()
    {
        $rule = new ContainRule();
        $rule->phrase('foo');

        $this->assertFalse($rule->test('bar'));
        $this->assertTrue($rule->test('foobar'));
    }

    /** @test */
    function it_tests_a_string_contains_a_string_insensitive()
    {
        $rule = new ContainRule(false);
        $rule->phrase('föo');

        $this->assertFalse($rule->test('bar'));
        $this->assertTrue($rule->test('föobar'));
        $this->assertFalse($rule->test('BAR'));
        $this->assertTrue($rule->test('FÖOBAR'));
    }

    /** @test */
    function it_tests_a_string_contains_multiple_strings()
    {
        $rule = new ContainRule();
        $rule->phrase(['foo', 'bar'])
            ->phrase('bax', 'baz');

        $this->assertFalse($rule->test('hello'));
        $this->assertTrue($rule->test('foo'));
        $this->assertTrue($rule->test('bax'));
        $this->assertTrue($rule->test('baz'));
    }

    /** @test */
    function it_tests_a_string_contains_multiple_strings_insensitive()
    {
        $rule = new ContainRule(false);
        $rule->phrase(['foo', 'bar'])
            ->phrase('bax', 'băz');

        $this->assertFalse($rule->test('hello'));
        $this->assertFalse($rule->test('HELLO'));
        $this->assertTrue($rule->test('foo'));
        $this->assertTrue($rule->test('bax'));
        $this->assertTrue($rule->test('băz'));
        $this->assertTrue($rule->test('FOO'));
        $this->assertTrue($rule->test('BaX'));
        $this->assertTrue($rule->test('bĂz'));
    }

    /** @test */
    function it_tests_a_string_does_not_contain_a_string()
    {
        $rule = new ContainRule();
        $rule->phrase('foo')->doesnt();

        $this->assertTrue($rule->test('bar'));
        $this->assertFalse($rule->test('foobar'));
        $this->assertTrue($rule->test('FOObar'));
    }

    /** @test */
    function it_tests_a_string_does_not_contain_a_string_insensitive()
    {
        $rule = new ContainRule(false);
        $rule->phrase('föo')->doesnt();

        $this->assertTrue($rule->test('bar'));
        $this->assertTrue($rule->test('BAR'));
        $this->assertFalse($rule->test('föobar'));
        $this->assertFalse($rule->test('FÖObar'));
    }

    /** @test */
    function it_tests_a_string_does_not_contain_multiple_strings()
    {
        $rule = new ContainRule();
        $rule->phrase(['foo', 'bar'])
            ->phrase('bax', 'baz')
            ->doesnt();

        $this->assertTrue($rule->test('hello'));
        $this->assertTrue($rule->test('HELLO'));
        $this->assertFalse($rule->test('foo'));
        $this->assertFalse($rule->test('bax'));
        $this->assertFalse($rule->test('baz'));
        $this->assertTrue($rule->test('Foo'));
        $this->assertTrue($rule->test('BAX'));
    }

    /** @test */
    function it_tests_a_string_does_not_contain_multiple_strings_insensitive()
    {
        $rule = new ContainRule(false);
        $rule->phrase(['foo', 'bar'])
            ->phrase('bax', 'bâz')
            ->doesnt();

        $this->assertTrue($rule->test('hello'));
        $this->assertTrue($rule->test('HELLO'));
        $this->assertFalse($rule->test('foo'));
        $this->assertFalse($rule->test('bax'));
        $this->assertFalse($rule->test('bâz'));
        $this->assertFalse($rule->test('FOO'));
        $this->assertFalse($rule->test('Bax'));
        $this->assertFalse($rule->test('bÂz'));
    }
}
