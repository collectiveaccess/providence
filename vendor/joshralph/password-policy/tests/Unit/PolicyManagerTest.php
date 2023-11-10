<?php namespace PasswordPolicy\Tests\Unit;

use PasswordPolicy\Policy;
use PasswordPolicy\PolicyBuilder;
use PasswordPolicy\PolicyManager;
use PasswordPolicy\Validator;

class PolicyManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_sets_the_default_policy_name()
    {
        $manager = new PolicyManager;
        $manager->setDefaultName('foobar');
        $this->assertEquals('foobar', $manager->getDefaultName());
    }

    /** @test */
    public function it_throws_an_exception_when_an_invalid_policy_is_requested()
    {
        $name = 'foobar';
        $manager = new PolicyManager;

        $this->setExpectedException('InvalidArgumentException', "Password policy [{$name}] does not exist.");
        $manager->getPolicy($name);
    }

    /** @test */
    public function it_successfully_registers_a_new_policy()
    {
        $manager = new PolicyManager;

        $this->assertSame($manager, $manager->define('admin', $policy = new Policy));
        $this->assertSame($policy, $manager->getPolicy('admin'));
    }

    /** @test */
    public function it_successfully_registers_a_new_policy_using_a_builder_instance()
    {
        $manager = new PolicyManager;
        $builder = new PolicyBuilder($policy = new Policy);

        $this->assertSame($manager, $manager->define('admin', $builder));
        $this->assertSame($policy, $manager->getPolicy('admin'));
    }

    /** @test */
    public function it_successfully_registers_a_new_policy_using_a_closure()
    {
        $manager = new PolicyManager;

        $this->assertSame($manager, $manager->define('admin', function ($builder) {
            $_SERVER['__test.policy'] = $builder->getPolicy();
        }));

        $this->assertSame($_SERVER['__test.policy'], $manager->getPolicy('admin'));
    }

    /** @test */
    public function it_throws_an_exception_when_parsing_an_invalid_policy_definition()
    {
        $manager = new PolicyManager;

        $this->setExpectedException(\InvalidArgumentException::class);
        $manager->define('foobar', null);
    }

    /** @test */
    public function it_returns_a_new_builder_instance()
    {
        $manager = new PolicyManager;
        $builder = $manager->newBuilder();
        $builder2 = $manager->newBuilder();

        $this->assertInstanceOf(PolicyBuilder::class, $builder);
        $this->assertNotSame($builder, $builder2);
    }

    /** @test */
    public function it_returns_a_validator_instance_for_a_valid_named_policy()
    {
        $manager = new PolicyManager;
        $manager->define('foo', $policy = new Policy);

        $validator = $manager->validator('foo');

        $this->assertInstanceOf(Validator::class, $validator);
        $this->assertSame($policy, $validator->getPolicy());
    }

    /** @test */
    public function it_throws_an_exception_when_a_validator_is_requested_for_an_invalid_policy_name()
    {
        $manager = new PolicyManager;

        $this->setExpectedException('InvalidArgumentException', "Password policy [bar] does not exist.");

        $manager->validator('bar');
    }

    /** @test */
    public function it_correctly_checks_if_a_policy_exists()
    {
        $manager = new PolicyManager;
        $manager->define('foo', $policy = new Policy);

        $this->assertTrue($manager->policyExists('foo'));
        $this->assertFalse($manager->policyExists('bar'));
    }
}