<?php namespace PasswordPolicy\Tests;

use PasswordPolicy\Policy;
use PasswordPolicy\PolicyBuilder;
use PasswordPolicy\Rule;
use PasswordPolicy\Rules\CaseRule;
use PasswordPolicy\Rules\MinPassingRulesRule;

class MinPassingRulesRuleTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    function passing_rules_pass()
    {
        $passing = $this->mockRule(true);

        $rule = (new MinPassingRulesRule(1))->using(function (PolicyBuilder $builder) use ($passing) {
            $builder->getPolicy()->addRule($passing);
        });

        $this->assertTrue($rule->test('password'));
    }

    /** @test */
    function failing_rules_fail()
    {
        $failing = $this->mockRule(false);

        $rule = (new MinPassingRulesRule(1))->using(function (PolicyBuilder $builder) use ($failing) {
            $builder->getPolicy()->addRule($failing);
        });

        $this->assertFalse($rule->test('password'));
    }

    /** @test */
    function if_more_than_the_min_required_passing_rules_pass_the_whole_policy_passes()
    {
        $passing = $this->mockRule(true);
        $failing = $this->mockRule(false);

        $rule = (new MinPassingRulesRule(1))->using(function (PolicyBuilder $builder) use ($passing, $failing) {
            $builder->getPolicy()->addRule($passing);
            $builder->getPolicy()->addRule($failing);
        });

        $this->assertTrue($rule->test('password'));
    }

    /** @test */
    function if_less_than_the_min_required_passing_rules_pass_the_whole_policy_fails()
    {
        $failing = $this->mockRule('false');

        $rule = (new MinPassingRulesRule(1))->using(function (PolicyBuilder $builder) use ($failing) {
            $builder->getPolicy()->addRule($failing);
        });

        $this->assertFalse($rule->test('password'));
    }

    /**
     * Create a mock rule, which passes based on the given result
     *
     * @param $result
     *
     * @return \Mockery\MockInterface
     */
    protected function mockRule($result)
    {
        return \Mockery::mock(Rule::class)
            ->shouldReceive('test')
            ->andReturn($result)
            ->getMock();
    }
}
