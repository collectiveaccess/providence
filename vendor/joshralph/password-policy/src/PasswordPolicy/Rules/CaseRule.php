<?php namespace PasswordPolicy\Rules;

use PasswordPolicy\Rule;

/**
 * Class CaseRule
 *
 * @package PasswordPolicy\Rules
 */
class CaseRule implements Rule
{
    /**
     * Min number of lower case characters
     *
     * @var int
     */
    private $lower = 0;

    /**
     * Min number of upper case characters
     *
     * @var int
     */
    private $upper = 0;

    /**
     * Set the minimum number of lower case characters
     *
     * @param int $min
     *
     * @return $this
     */
    public function lower($min = 1)
    {
        $this->lower = $min;

        return $this;
    }

    /**
     * Set the minimum number of upper case characters
     *
     * @param int $min
     *
     * @return $this
     */
    public function upper($min = 1)
    {
        $this->upper = $min;

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
        // Lower case
        if (!$this->check($subject, '/[^a-z]/', $this->lower)) {
            return false;
        }

        // Upper case
        if (!$this->check($subject, '/[^A-Z]/', $this->upper)) {
            return false;
        }

        return true;
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
