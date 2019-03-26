<?php namespace PasswordPolicy\Rules;

use PasswordPolicy\Rule;

/**
 * Class ContainRule
 *
 * @package PasswordPolicy\Rules
 */
class ContainRule implements Rule
{
    /**
     * Check it does contain
     *
     * @var bool
     */
    protected $does = true;

    /**
     * Array of phrases to test against
     *
     * @var array
     */
    protected $phrases = [];


    /**
     * Set phrase(s)
     *
     * @param $phrases
     *
     * @return $this
     */
    public function phrase($phrases)
    {
        $phrases = is_array($phrases) ? $phrases : func_get_args();

        $this->phrases = array_merge($this->phrases, $phrases);

        return $this;
    }

    /**
     * Toggle doesn't contain
     *
     * @return $this
     */
    public function doesnt()
    {
        $this->does = false;

        return $this;
    }

    /**
     * Toggle does contain
     *
     * @return $this
     */
    public function does()
    {
        $this->does = true;

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
        if ($this->does) {
            foreach ($this->phrases as $phrase) {
                if ($this->containsPhrase($subject, $phrase)) {
                    return true;
                }
            }

            return false;
        } else {
            foreach ($this->phrases as $phrase) {
                if ($this->containsPhrase($subject, $phrase)) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
     * Check if a subject contains a phrase
     *
     * @param $subject
     * @param $phrase
     *
     * @return bool
     */
    private function containsPhrase($subject, $phrase)
    {
        return strpos($subject, $phrase) !== false;
    }
}
