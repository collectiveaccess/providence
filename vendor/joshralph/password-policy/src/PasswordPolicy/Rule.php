<?php namespace PasswordPolicy;

interface Rule
{
    /**
     * Test a rule
     *
     * @param $subject
     *
     * @return bool
     */
    public function test($subject);
}
