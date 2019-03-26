<?php namespace PasswordPolicy\Providers\Laravel;

use PasswordPolicy\PolicyManager;

/**
 * Class PasswordValidator
 *
 * @package PasswordPolicy\Providers\Laravel
 */
class PasswordValidator
{
    /**
     * Policy manager instance
     *
     * @var PolicyManager
     */
    private $manager;


    /**
     * PasswordValidator constructor.
     *
     * @param PolicyManager $manager
     */
    public function __construct(PolicyManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Validate the given value
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param $validator
     *
     * @return bool
     */
    public function validate($attribute, $value, $parameters, $validator)
    {
        // Use the default policy if the user has not specified one.
        $policy = isset($parameters[0]) ? $parameters[0] : $this->manager->getDefaultName();

        return $this->manager
            ->validator($policy)
            ->attempt($value);
    }
}