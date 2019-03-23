<?php namespace PasswordPolicy\Rules;

use PasswordPolicy\Rule;

/**
 * Class SpecialCharacterRule
 * @package PasswordPolicy\Rules
 */
class SpecialCharacterRule implements Rule
{
    /**
     * Min number of special characters
     *
     * @var int
     */
    private $min = 1;

    /**
     * Set the minimum number of special characters
     *
     * @param int $min
     *
     * @return $this
     */
    public function min($min)
    {
        $this->min = $min;

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
        return $this->check($subject, '/[\w\d\s]/', $this->min);
    }

    /**
     * Check the given subject against a pattern for minimum occurrence
     *
     * @param $subject
     * @param $pattern
     * @param $min
     *
     * @return bool
     */
    private function check($subject, $pattern, $min)
    {
        $matchingCharacters = preg_replace($pattern, '', $subject);

        if (mb_strlen($matchingCharacters) < $min) {
            return false;
        }

        return true;
    }
}
