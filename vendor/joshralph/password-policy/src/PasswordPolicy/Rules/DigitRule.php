<?php namespace PasswordPolicy\Rules;

use PasswordPolicy\Rule;

/**
 * Class CaseRule
 *
 * @package PasswordPolicy\Rules
 */
class DigitRule implements Rule
{
    /**
     * Min number of digit characters
     *
     * @var int
     */
    private $min = 1;

    /**
     * Set the minimum number of digit characters
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
        return $this->check($subject, '/[^0-9]/', $this->min);
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

        if (strlen($matchingCharacters) < $min) {
            return false;
        }

        return true;
    }
}
