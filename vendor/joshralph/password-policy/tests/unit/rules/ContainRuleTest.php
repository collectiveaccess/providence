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
    function it_tests_a_string_does_not_contain_a_string()
    {
        $rule = new ContainRule();
        $rule->phrase('foo')->doesnt();

        $this->assertTrue($rule->test('bar'));
        $this->assertFalse($rule->test('foobar'));
    }

    /** @test */
    function it_tests_a_string_does_not_contain_multiple_strings()
    {
        $rule = new ContainRule();
        $rule->phrase(['foo', 'bar'])
            ->phrase('bax', 'baz')
            ->doesnt();

        $this->assertTrue($rule->test('hello'));
        $this->assertFalse($rule->test('foo'));
        $this->assertFalse($rule->test('bax'));
        $this->assertFalse($rule->test('baz'));
    }
}
