<?php namespace PasswordPolicy\Tests\Unit\Rules;

use PasswordPolicy\Rules\SpecialCharacterRule;

class SpecialCharacterRuleTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_tests_a_string_includes_special_characters()
    {
        $rule = new SpecialCharacterRule;

        $this->assertFalse($rule->test('foobar'));
        $this->assertTrue($rule->test('foob@r'));
    }

    /** @test */
    public function spaces_are_not_counted_as_special_characters()
    {
        $rule = new SpecialCharacterRule;

        $this->assertFalse($rule->test('foo bar'));
    }

    /** @test */
    public function a_minimum_can_be_applied()
    {
        $rule = new SpecialCharacterRule;
        $rule->min(3);

        $this->assertFalse($rule->test('foob@r'));
        $this->assertFalse($rule->test('foo£@r'));
        $this->assertTrue($rule->test('(oo£@r'));
    }
}