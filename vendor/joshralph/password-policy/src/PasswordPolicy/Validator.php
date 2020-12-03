<?php namespace PasswordPolicy;

class Validator
{
    /**
     * @var Policy
     */
    protected $policy;

    public function __construct(Policy $policy)
    {
        $this->policy = $policy;
    }

    /**
     * @param Policy $policy
     *
     * @return $this
     */
    public function setPolicy(Policy $policy)
    {
        $this->policy = $policy;

        return $this;
    }

    public function getPolicy()
    {
        return $this->policy;
    }

    public function attempt($subject)
    {
        /** @var Rule $rule */
        foreach ($this->policy->rules() as $rule) {
            if (!$rule->test($subject)) {
                return false;
            }
        }

        return true;
    }
}
