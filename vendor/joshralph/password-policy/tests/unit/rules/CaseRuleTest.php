<?php namespace PasswordPolicy\Tests\Unit\Rules;

use PasswordPolicy\Rules\CaseRule;

class CaseRuleTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_tests_a_string_contains_a_given_number_of_lower_case_characters()
    {
        $rule = new CaseRule;
        $rule->lower(3);

        $this->assertFalse($rule->test('BAR'));
        $this->assertFalse($rule->test('BaR'));
        $this->assertFalse($rule->test('ba'));
        $this->assertTrue($rule->test('bar'));
        $this->assertTrue($rule->test('fOoBaR'));
    }

    /** @test */
    function it_tests_a_string_contains_a_given_number_of_upper_case_characters()
    {
        $rule = new CaseRule;
        $rule->upper(3);

        $this->assertFalse($rule->test('bar'));
        $this->assertFalse($rule->test('bAr'));
        $this->assertFalse($rule->test('BA'));
        $this->assertTrue($rule->test('BAR'));
        $this->assertTrue($rule->test('fOoBaR'));
    }

    /** @test */
    function it_tests_a_string_contains_a_given_number_of_upper_and_lower_case_characters()
    {
        $rule = new CaseRule;
        $rule->lower(3)->upper(3);

        $this->assertFalse($rule->test('bar'));
        $this->assertFalse($rule->test('BAR'));
        $this->assertFalse($rule->test('foBA'));
        $this->assertTrue($rule->test('fOoBaR'));
    }
}
