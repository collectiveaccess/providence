<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIGetopt.php : command line option parser
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Thrown on invalid option rules or command line options
 */
class CLIGetoptException extends Exception {}

/**
 * Command line option parser. Replaces Zend_Console_Getopt, accepting rules in the same format:
 *
 *		"long|s" => "Help text"			flag with no parameter, as --long or -s
 *		"long|s=s" => "Help text"		required string parameter (=i for integer, =w for word)
 *		"long|s-s" => "Help text"		optional string parameter (-i for integer, -w for word)
 *
 * Parameters may be passed as --long=value, --long value or -s value. Short flags may be clustered (-abc).
 * Arguments that are not options are returned by getRemainingArgs(). Parsing stops at "--".
 */
class CLIGetopt {
	# -------------------------------------------------------
	/**
	 * Rules keyed on main flag
	 */
	private $rules = [];

	/**
	 * Map of all flags and aliases to main flag
	 */
	private $rule_map = [];

	/**
	 * Arguments to parse
	 */
	private $argv = [];

	/**
	 * Parsed option values keyed on main flag
	 */
	private $options = [];

	/**
	 * Non-option arguments
	 */
	private $remaining_args = [];

	/**
	 *
	 */
	private $parsed = false;
	# -------------------------------------------------------
	/**
	 * @param array $rules Option rules
	 * @param array $argv Arguments to parse [Default is command line arguments, without the script name]
	 * @throws CLIGetoptException
	 */
	public function __construct(array $rules, ?array $argv=null) {
		if (!is_array($argv)) {
			if (!isset($_SERVER['argv'])) {
				throw new CLIGetoptException(_t('Command line arguments are not available. Is register_argc_argv set?'));
			}
			$argv = array_slice($_SERVER['argv'], 1);
		}
		$this->addRules($rules);
		$this->argv = array_values($argv);
	}
	# -------------------------------------------------------
	/**
	 * Add option rules
	 *
	 * @param array $rules
	 * @throws CLIGetoptException
	 */
	public function addRules(array $rules) {
		foreach($rules as $rule_code => $help) {
			$param = 'none';
			$param_type = null;
			$flag_list = $rule_code;
			if (in_array($delimiter = substr($rule_code, -2, 1), ['=', '-'], true)) {
				$flag_list = substr($rule_code, 0, -2);
				$param = ($delimiter === '=') ? 'required' : 'optional';
				switch(substr($rule_code, -1)) {
					case 'i':
						$param_type = 'integer';
						break;
					case 'w':
						$param_type = 'word';
						break;
					default:
						$param_type = 'string';
						break;
				}
			}
			$flags = explode('|', $flag_list);
			$main_flag = $flags[0];
			foreach($flags as $flag) {
				if (!strlen($flag)) {
					throw new CLIGetoptException(_t('Blank flag not allowed in rule "%1"', $rule_code));
				}
				if (isset($this->rule_map[$flag])) {
					throw new CLIGetoptException(_t('Option "%1" is being defined more than once', ((strlen($flag) == 1) ? '-' : '--').$flag));
				}
				$this->rule_map[$flag] = $main_flag;
			}
			$this->rules[$main_flag] = ['aliases' => $flags, 'param' => $param, 'paramType' => $param_type, 'help' => $help];
		}
		$this->parsed = false;
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Parse arguments
	 *
	 * @throws CLIGetoptException
	 */
	public function parse() {
		if ($this->parsed) { return $this; }

		$argv = $this->argv;
		$this->options = $this->remaining_args = [];
		while(sizeof($argv) > 0) {
			$arg = $argv[0];
			if ($arg === '--') {
				array_shift($argv);
				$this->remaining_args = array_merge($this->remaining_args, $argv);
				break;
			}
			if (substr($arg, 0, 2) === '--') {
				array_shift($argv);
				$tmp = explode('=', substr($arg, 2), 2);
				if (isset($tmp[1])) { array_unshift($argv, $tmp[1]); }
				$this->parseOption($tmp[0], $argv);
			} elseif ((substr($arg, 0, 1) === '-') && (($arg !== '-') || (sizeof($argv) > 1))) {
				array_shift($argv);
				foreach(str_split(ltrim($arg, '-')) as $flag) {
					$this->parseOption($flag, $argv);
				}
			} else {
				$this->remaining_args[] = array_shift($argv);
			}
		}
		$this->parsed = true;
		return $this;
	}
	# -------------------------------------------------------
	/**
	 * Parse a single option, consuming its parameter from $argv if required
	 *
	 * @param string $flag
	 * @param array $argv
	 * @throws CLIGetoptException
	 */
	private function parseOption(string $flag, array &$argv) {
		if (!isset($this->rule_map[$flag])) {
			throw new CLIGetoptException(_t('Option "%1" is not recognized.', $flag));
		}
		$main_flag = $this->rule_map[$flag];
		$rule = $this->rules[$main_flag];

		$value = true;
		if ($rule['param'] !== 'none') {
			if ((sizeof($argv) > 0) && (substr($argv[0], 0, 1) !== '-')) {
				$value = array_shift($argv);
				$this->checkParameterType($flag, $rule['paramType'], $value);
			} elseif ($rule['param'] === 'required') {
				throw new CLIGetoptException(_t('Option "%1" requires a parameter.', $flag));
			}
		}
		$this->options[$main_flag] = $value;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function checkParameterType(string $flag, ?string $type, string $value) {
		switch($type) {
			case 'word':
				if (preg_match('/\W/', $value)) {
					throw new CLIGetoptException(_t('Option "%1" requires a single-word parameter, but was given "%2".', $flag, $value));
				}
				break;
			case 'integer':
				if (preg_match('/\D/', $value)) {
					throw new CLIGetoptException(_t('Option "%1" requires an integer parameter, but was given "%2".', $flag, $value));
				}
				break;
		}
	}
	# -------------------------------------------------------
	/**
	 * Return value of option. Options without parameters, or with optional parameters that were omitted, return true.
	 *
	 * @param string $flag Option name or alias
	 * @return mixed Option value, or null if the option was not set
	 */
	public function getOption($flag) {
		$this->parse();
		if (is_string($flag) && isset($this->rule_map[$flag]) && isset($this->options[$this->rule_map[$flag]])) {
			return $this->options[$this->rule_map[$flag]];
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Return values of all set options, keyed on option name
	 *
	 * @return array
	 */
	public function getOptions() : array {
		$this->parse();
		return $this->options;
	}
	# -------------------------------------------------------
	/**
	 * Return arguments that are not options
	 *
	 * @return array
	 */
	public function getRemainingArgs() : array {
		$this->parse();
		return $this->remaining_args;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __get($flag) {
		return $this->getOption($flag);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __isset($flag) {
		return ($this->getOption($flag) !== null);
	}
	# -------------------------------------------------------
}
