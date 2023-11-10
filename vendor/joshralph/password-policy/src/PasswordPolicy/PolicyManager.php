<?php namespace PasswordPolicy;

use Closure;
use InvalidArgumentException;

/**
 * Class PolicyManager
 *
 * @package PasswordPolicy
 */
class PolicyManager
{
    /**
     * Array of defined policies
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Default policy name
     *
     * @var string
     */
    protected $defaultPolicy = 'default';


    /**
     * Set the name of the default policy
     *
     * @param $name string
     *
     * @return $this
     */
    public function setDefaultName($name)
    {
        $this->defaultPolicy = $name;

        return $this;
    }

    /**
     * Get the name of the default policy
     *
     * @return string
     */
    public function getDefaultName()
    {
        return $this->defaultPolicy;
    }

    /**
     * Define a new policy by name
     *
     * @param $name string
     * @param $policy Policy|PolicyBuilder|Closure
     *
     * @return $this
     */
    public function define($name, $policy)
    {
        $this->policies[$name] = $this->parsePolicy($policy);

        return $this;
    }

    /**
     * Parse policy
     *
     * @param $policy
     *
     * @return Policy
     * @throws InvalidArgumentException
     */
    protected function parsePolicy($policy)
    {
        if ($policy instanceof Policy) {
            return $policy;
        }

        if ($policy instanceof PolicyBuilder) {
            return $policy->getPolicy();
        }

        if ($policy instanceof Closure) {
            return $this->parseClosure($policy);
        }

        throw new InvalidArgumentException("Invalid policy declaration.");
    }

    /**
     * Parse closure definition
     *
     * @param Closure $closure
     *
     * @return Policy
     */
    protected function parseClosure(Closure $closure)
    {
        call_user_func($closure, $builder = $this->newBuilder());

        return $builder->getPolicy();
    }

    /**
     * Get a new builder instance
     *
     * @return PolicyBuilder
     */
    public function newBuilder()
    {
        return new PolicyBuilder(new Policy);
    }

    /**
     * Get a new validator instance for the given policy name
     *
     * @param $policy string
     *
     * @return Validator
     */
    public function validator($policy)
    {
        return new Validator($this->resolve($policy));
    }

    /**
     * Resolve a policy
     *
     * @param $policy Policy|string
     *
     * @throws InvalidArgumentException
     * @return Policy
     */
    protected function resolve($policy)
    {
        if ($policy instanceof Policy) {
            return $policy;
        }

        return $this->getPolicy($policy);
    }

    /**
     * Check whether a given policy exists
     *
     * @param $name string
     *
     * @return bool
     */
    public function policyExists($name)
    {
        return isset($this->policies[$name]);
    }

    /**
     * Get a policy by name. Throws an exception if policy is not found.
     *
     * @param $name string
     *
     * @throws InvalidArgumentException
     * @return Policy
     */
    public function getPolicy($name)
    {
        if ($this->policyExists($name)) {
            return $this->policies[$name];
        }

        throw new InvalidArgumentException("Password policy [{$name}] does not exist.");
    }
}