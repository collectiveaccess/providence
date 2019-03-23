<?php namespace PasswordPolicy\Tests\Providers\Laravel;

use PasswordPolicy\Policy;
use PasswordPolicy\PolicyBuilder;
use PasswordPolicy\PolicyManager;
use PasswordPolicy\Providers\Laravel\PasswordValidator;
use PasswordPolicy\Validator;
use Mockery as m;

class PasswordValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_calls_the_validator_using_the_default_policy()
    {
//        $managerMock = m::mock(PolicyManager::class)
//            ->shouldReceive('getDefaultName')
//            ->shouldReceive('validator')
//            ->once()
//            ->andReturn('default');
//
//        $manager = $managerMock->getMock();
//        $manager->define('default', new Policy);
//
//        $validatorMock = m::mock(PasswordValidator::class, [$manager])
//            ->shouldReceive('validate')
//            ->once()
//            ->with('password', 'password', [], null)
//            ->andReturn(true);
//
//        $validator = $validatorMock->getMock();
//        $this->assertTrue(($validator->validate('password', 'password', [], null)));
    }
}