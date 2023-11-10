<?php namespace PasswordPolicy\Rules;

use PasswordPolicy\Rule;

class LengthRule implements Rule
{
    /**
     * Minimum character length
     *
     * @var int
     */
    protected $min = null;

    /**
     * Maximum character length
     *
     * @var int
     */
    protected $max = null;


    public function min($min)
    {
        $this->min = $min;

        return $this;
    }

    public function max($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Test a rule
     *
     * @param $subject
     *
     * @return bool
     */
    public function test($subject)
    {
        $length = strlen($subject);

        if ($this->min !== null && $length < $this->min) {
            return false;
        }

        if ($this->max !== null && $length > $this->max) {
            return false;
        }

        return true;
    }
}
