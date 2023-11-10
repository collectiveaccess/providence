<?php namespace PasswordPolicy\Tests\Unit\Rules;

use PasswordPolicy\Rules\DigitRule;

class DigitRuleTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_tests_a_string_contains_a_digit()
    {
        $rule = new DigitRule();

        $this->assertFalse($rule->test('foobar'));
        $this->assertTrue($rule->test('foobar1'));
    }

    /** @test */
    function it_tests_a_string_contains_a_given_number_of_digits()
    {
        $rule = new DigitRule();
        $rule->min(3);

        $this->assertFalse($rule->test('foobar'));
        $this->assertFalse($rule->test('foobar1'));
        $this->assertFalse($rule->test('12'));
        $this->assertTrue($rule->test('123'));
        $this->assertTrue($rule->test('1foo2bar3'));
    }
}
