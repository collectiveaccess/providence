<?php namespace PasswordPolicy\Tests\Unit;

use Mockery as m;
use PasswordPolicy\Policy;
use PasswordPolicy\Rule;
use PasswordPolicy\Rules\LengthRule;
use PasswordPolicy\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function it_correctly_sets_the_policy_instance()
    {
        $validator = new Validator($policy1 = new Policy);
        $this->assertSame($policy1, $validator->getPolicy());

        $validator->setPolicy($policy2 = new Policy);
        $this->assertSame($policy2, $validator->getPolicy());
    }

    /** @test */
    function it_calls_the_test_method_on_rules()
    {
        $subject = 'foo bar';

        $mock = m::mock(Rule::class)
            ->shouldReceive('test')
            ->with($subject)
            ->andReturnValues([true]);

        $policy = new Policy;
        $policy->addRule($mock->getMock());

        $validator = new Validator($policy);

        $this->assertTrue($validator->attempt($subject));
    }
}
