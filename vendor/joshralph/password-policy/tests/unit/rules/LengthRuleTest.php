<?php namespace PasswordPolicy\Tests\Unit\Rules;


use PasswordPolicy\Rules\LengthRule;

class LengthRuleTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_tests_min_length()
    {
        $rule = new LengthRule();
        $rule->min(10);

        $this->assertFalse($rule->test(''));
        $this->assertFalse($rule->test('hello'));
        $this->assertTrue($rule->test('helloworld'));
        $this->assertTrue($rule->test('hello world'));
    }

    /** @test */
    function it_tests_max_length()
    {
        $rule = new LengthRule();
        $rule->max(10);

        $this->assertTrue($rule->test('hello'));
        $this->assertTrue($rule->test('helloworld'));
        $this->assertFalse($rule->test('hello world'));
    }

    /** @test */
    function it_tests_min_and_max_length()
    {
        $rule = new LengthRule();
        $rule->min(3)->max(6);

        $this->assertFalse($rule->test('ab'));
        $this->assertTrue($rule->test('abc'));
        $this->assertTrue($rule->test('abcde'));
        $this->assertTrue($rule->test('abcdef'));
        $this->assertFalse($rule->test('abcdefg'));
    }
}
