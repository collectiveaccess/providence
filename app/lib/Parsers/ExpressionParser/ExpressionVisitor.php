<?php
/** ---------------------------------------------------------------------
 * app/lib/Parsers/ExpressionParser/ExpressionVisitor.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2026 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_APP_DIR__.'/helpers/expressionHelpers.php');

use Hoa\Visitor;

/**
 * Class ExpressionVisitor
 *
 * Most of the artithmetic function parsing code was taken from Hoa\Math\Visitor\Arithmetic
 */
class ExpressionVisitor implements Visitor\Visit {

	protected $opa_functions = array();
	protected $opa_variables = array();
	protected $opo_app_plugin_manager = null;

	public function __construct() {
		$this->initializeFunctions();
	}

	/**
	 * Initialize functions mapping.
	 *
	 * @return void
	 */
	protected function initializeFunctions() {

		if(sizeof($this->opa_functions) > 0) { return; }

		$this->opa_functions  = array(
			'abs'			=> xcallable('caExpressionFunctionAbsValue'),
			'ceil'			=> xcallable('caExpressionFunctionCeilValue'),
			'floor'			=> xcallable('caExpressionFunctionFloorValue'),
			'int'			=> xcallable('caExpressionFunctionIntValue'),
			'max'			=> xcallable('caExpressionFunctionMaxValue'),
			'min'			=> xcallable('caExpressionFunctionMinValue'),
			'rand'			=> xcallable('caExpressionFunctionRandomValue'),
			'round'			=> xcallable('caExpressionFunctionRoundValue'),
			'random'		=> xcallable('caExpressionFunctionRandomValue'),
			
			'wc'			=> xcallable('caExpressionFunctionStringWordCount'),
			'length'		=> xcallable('caExpressionFunctionStringLength'),
			'replace'		=> xcallable('caExpressionFunctionStringReplace'),
			'trim'			=> xcallable('caExpressionFunctionStringTrim'),
			
			'date'			=> xcallable('caExpressionDate'),
			'formatdate'	=> xcallable('caExpressionFormatDate'),
			'formatDate'	=> xcallable('caExpressionFormatDate'),
			'formatgmdate'	=> xcallable('caExpressionFormatGMDate'),
			'formatGMDate'	=> xcallable('caExpressionFormatGMDate'),
			'current'		=> xcallable('caExpressionIsCurrentDate'),
			'isCurrentDate'	=> xcallable('caExpressionIsCurrentDate'),
			'isvaliddate'	=> xcallable('caExpressionIsValidDate'),
			'isValidDate'	=> xcallable('caExpressionIsValidDate'),
			'future'		=> xcallable('caExpressionDateEndsInFuture'),
			'past'			=> xcallable('caExpressionDateStartsInPast'),
			'age'			=> xcallable('caExpressionDateAgeInYears'),
			'ageyears'		=> xcallable('caExpressionDateAgeInYears'),
			'ageInYears'	=> xcallable('caExpressionDateAgeInYears'),
			'agedays'		=> xcallable('caExpressionDateAgeInDays'),
			'ageInDays'		=> xcallable('caExpressionDateAgeInDays'),
			'avgdays'		=> xcallable('caExpressionDateAverageInDays'),
			'avgInDays'		=> xcallable('caExpressionDateAverageInDays'),
			'earliestDate'	=> xcallable('caExpressionEarliestDate'),
			'latestDate'	=> xcallable('caExpressionLatestDate'),
			'dateIsRange'	=> xcallable('caExpressionDateIsRange'),
			'fromUnixtime'	=> xcallable('caExpressionFromUnixTime'),
			'fromUnixTime'	=> xcallable('caExpressionFromUnixTime'),
			
			'sizeof'		=> xcallable('caExpressionFunctionValueCount'),
			'count'			=> xcallable('caExpressionFunctionValueCount'),
			'valueCount'	=> xcallable('caExpressionFunctionValueCount'),
			'uniqueValueCount'	=> xcallable('caExpressionFunctionUniqueValueCount'),
			'join'			=> xcallable('caExpressionFunctionJoinList'),
			'implode'		=> xcallable('caExpressionFunctionJoinList'),
			'existsInList'	=> xcallable('caExpressionFunctionExistsInList'),
			
			'average' 		=> xcallable('caExpressionFunctionAverageValues'),
			'avg'     		=> xcallable('caExpressionFunctionAverageValues'),
			'sum'			=> xcallable('caExpressionFunctionSumValues'),
			
			'idnoUseCount'	=> xcallable('caExpressionFunctionIdnoUseCount'),
		);

		// Let application plugins add their own expression functions
		$this->opo_app_plugin_manager = new ApplicationPluginManager();
		$va_tmp = $this->opo_app_plugin_manager->hookRegisterExpressions(['expressions' => $this->opa_functions, 'instance' => $this]);
		$this->opa_functions = $va_tmp['expressions'];
		return;
	}

	/**
	 * Reset variables
	 */
	public function resetVariables() {
		$this->opa_variables = array();
	}

	/**
	 * Set variables, overwriting any previously set variables
	 * @param array $pa_vars
	 */
	public function setVariables($pa_vars) {
		$this->opa_variables = $pa_vars;
	}

	/**
	 * Set one variable, leaving all others in place
	 * @param string $ps_var
	 * @param mixed $pm_val
	 */
	public function setVariable($ps_var, $pm_val) {
		$this->opa_variables[$ps_var] = $pm_val;
	}

	/**
	 * Get variable value
	 * @param string $ps_var
	 * @return bool|mixed
	 */
	public function getVariable($ps_var) {
		return $this->opa_variables[$ps_var.':array'] ?? $this->opa_variables[$ps_var] ?? false;
	}

	/**
	 * Evaluate given AST as CollectiveAccess expression
	 *
	 * @param Visitor\Element $po_element
	 * @param Hoa\Core\Consistency\Xcallable $f_handle
	 * @param Hoa\Core\Consistency\Xcallable $f_eldnah
	 * @return mixed
	 * @throws Exception
	 */
	public function visit(Visitor\Element $po_element, &$f_handle = null, $f_eldnah  = null) {
		$_ = null;
		$a = null;
		$in = null;
		$vs_type = $po_element->getId();
		$children = $po_element->getChildren();

		// if no handle passed, use identity
		if ($f_handle === null) {
			$f_handle = function ($x) {
				return $x;
			};
		}

		$f_acc = &$f_handle;

		switch ($vs_type) {
			case '#function':
				$name = array_shift($children)->accept($this, $_, $f_eldnah);
				$f_function = $this->getFunction($name);
				
				// First argument to function handler is array of context information
				$args = [[
					'variables' => $this->opa_variables
				]];
				
				$fparams = [];
				foreach ($children as $o_child) {
					$token = $o_child->getValue();
					switch($token['token']) {
						case 'string_single_quote':
						case 'string':
							$token['value'] = trim(trim($token['value'], '"'), "'");
							$vtags = caGetTemplateTags($token['value']);
							$etypes = (is_array($vtags) && sizeof($vtags) && ('^'.$vtags[0] == trim($token['value']))) ? caGetTagDatatypes([$token['value'] => true], []) : null;
							$fparams[] = [
								'type' => $etypes[$token['value']] ?? '_LITERAL_',
								'value' => $token['value']
							];
							break;
						case 'variable':
							$etypes = caGetTagDatatypes([$token['value'] => true], []);
							$fparams[] = [
								'type' => $etypes[$token['value']] ?? '_UNKNOWN_',
								'value' => $token['value']
							];
							break;
						case 'true':
						case 'false':
							$fparams[] = [
								'type' => '_LITERAL_',
								'value' => (bool)$token['value']
							];
							break;
						case 'number':
							$fparams[] = [
								'type' => '_LITERAL_',
								'value' => (float)$token['value']
							];
							break;
						default:
							$fparams[] = [
								'type' => '_LITERAL_',
								'value' => $token['value']
							];
							break;
					}
					$o_child->accept($this, $_, $f_eldnah);
					$args[] = $_();
					unset($_);
				}
				
				$args[0]['params'] = $fparams;

				$f_acc = function () use ($f_function, $args, $f_acc) {
					return $f_acc($f_function->distributeArguments($args));
				};

				break;

			case '#bool_and':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v && $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#bool_or':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v || $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#regex_match':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc((bool)@preg_match($b, $v));
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#regex_nomatch':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc(!((bool) @preg_match($b, $v)));
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#comp_gt':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v > $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#comp_gte':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v >= $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#comp_lt':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v < $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#comp_lte':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v <= $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#comp_neq':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v != $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#comp_eq':
				$children[0]->accept($this, $a, $f_eldnah);
				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v == $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#in_op':
				$vs_needle = array_shift($children)->accept($this, $in, $f_eldnah);
				$haystack = array();

				$in = $f_handle;

				$o_op = array_shift($children);
				if ($o_op->getValueToken() !== 'in_op') {
					throw new Exception('invalid syntax');
				}

				foreach ($children as $o_child) {
					$o_child->accept($this, $in, $f_eldnah);
					
					$v = $in(); if(is_array($v)) { $v = array_shift($v); }
					$haystack[] = $v;
					unset($in);
				}

				$f_acc = function () use ($vs_needle, $haystack, $f_acc) {
					return $f_acc(in_array($vs_needle, $haystack));
				};

				break;

			case '#notin_op':
				$vs_needle = array_shift($children)->accept($this, $in, $f_eldnah);
				$haystack = array();

				$in = $f_handle;

				$o_op = array_shift($children);
				if ($o_op->getValueToken() !== 'notin_op') {
					throw new Exception('invalid syntax');
				}

				foreach ($children as $o_child) {
					$o_child->accept($this, $in, $f_eldnah);
					$v = $in(); if(is_array($v)) { $v = array_shift($v); }
					$haystack[] = $v;
					unset($in);
				}

				$f_acc = function () use ($vs_needle, $haystack, $f_acc) {
					return $f_acc(!in_array($vs_needle, $haystack));
				};

				break;


			case '#stradd':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v . $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#negative':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function () use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc(-$v);
				};

				break;

			case '#addition':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v + $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#substraction':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v - $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#multiplication':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					return $f_acc($v * $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#division':
				$children[0]->accept($this, $a, $f_eldnah);
				$parent = $po_element->getParent();

				if (null === $parent || (null === $parent->getId())) {
					$f_acc = function ($b) use ($a, $f_acc) {
						$v = $a(); if(is_array($v)) { $v = array_shift($v); }
						if (0 === $b) {
							throw new \RuntimeException(
								'Division by zero is not possible.'
							);
						}

						return $f_acc($v) / $b;
					};
				} else {
					if ('#fakegroup' !== $parent->getId()) {
						$classname = get_class($po_element);
						$group = new $classname(
							'#fakegroup',
							null,
							[$po_element],
							$parent
						);
						$po_element->setParent($group);

						$this->visit($group, $f_acc, $f_eldnah);

						break;
					} else {
						$f_acc = function ($b) use ($a, $f_acc) {
							$v = $a(); if(is_array($v)) { $v = array_shift($v); }
							if (0 === $b) {
								throw new \RuntimeException(
									'Division by zero is not possible.'
								);
							}

							return $f_acc($v / $b);
						};
					}
				}

				$children[1]->accept($this, $f_acc, $f_eldnah);

				break;
			case '#modulus':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v % $b);
				};

				$children[1]->accept($this, $f_acc, $f_eldnah);

				break;
			case '#fakegroup':
			case '#group':
				$children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function () use ($a, $f_acc) {
					$v = $a(); if(is_array($v)) { $v = array_shift($v); }
					return $f_acc($v);
				};

				break;

			case 'token':
				$value = $po_element->getValueValue();
				$token = $po_element->getValueToken();
				$out = null;
				
				switch($token) {
					case 'id':
						return $value;
					case 'string':
						$out = $this->_processVarsInString(preg_replace('/(^"|"$)/', '', $value));
						break;
					case 'string_single_quote':
						$out = $this->_processVarsInString(preg_replace("/(^'|'$)/", '', $value));
						break;
					case 'regex':
						// @todo maybe mangle regex?
						$out = (string) $value;
						break;
					case 'variable':
						$var = mb_substr((string) $value, 1); // get rid of caret ^
						$out = $this->getVariable($var);
						//if(is_array($out)) { $out = join("", $out); }	// we need to join multiple values
						break;
					case 'true':
						$out = true;
						break;
					case 'false':
						$out = false;
						break;
					default:
						$out = (float) $value;
						break;
				}

				$f_acc = function () use ($out, $f_acc) {
					return $f_acc($out);
				};

				break;
		}

		return $f_acc();
	}
	
	/**
	 *
	 */
	private function _processVarsInString(string $str) {
		$str = DisplayTemplateParser::processSimpleTemplate($str, $this->opa_variables);
		return $str;
	}

	/**
	 * Get callable function by name
	 * @param string $ps_name
	 * @return Hoa\Core\Consistency\Xcallable
	 * @throws Exception
	 */
	public function getFunction($ps_name) {
		if(!isset($this->opa_functions[$ps_name])) {
			throw new Exception('Invalid function name');
		}
		return $this->opa_functions[$ps_name];
	}

}
