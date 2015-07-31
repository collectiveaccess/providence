<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/ExpressionParser/ExpressionVisitor.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

		$average = function () {
			$arguments = func_get_args();

			return array_sum($arguments) / count($arguments);
		};

		$this->opa_functions  = array(
			'abs'           => xcallable('abs'),
			'ceil'          => xcallable('ceil'),
			'floor'         => xcallable('floor'),
			'int'           => xcallable('intval'),
			'max'           => xcallable('max'),
			'min'           => xcallable('min'),
			'rand'          => xcallable('rand'),
			'round'         => xcallable('round'),
			'random'		=> xcallable('rand'),
			'current'		=> xcallable('caIsCurrentDate'),
			'future'		=> xcallable('caDateEndsInFuture'),
			'wc'			=> xcallable('str_word_count'),
			'length'		=> xcallable('strlen'),
			'date'			=> xcallable('caDateToHistoricTimestamp'),
			'sizeof'		=> xcallable(function () { return count(func_get_args()); }),
			'count'			=> xcallable(function () { return count(func_get_args()); }),
			'age'			=> xcallable('caCalculateAgeInYears'),
			'ageyears'		=> xcallable('caCalculateAgeInYears'),
			'agedays'		=> xcallable('caCalculateAgeInDays'),
			'avgdays'		=> xcallable('caCalculateDateRangeAvgInDays'),
			'average' 		=> xcallable($average),
			'avg'     		=> xcallable($average),
			'sum'			=> xcallable(function () { return array_sum(func_get_args()); }),
		);

		return;
	}

	/**
	 * Evaluate given AST as CollectiveAccess expression
	 *
	 * @param Visitor\Element $po_element
	 * @param Hoa\Core\Consistency\Xcallable $f_handle
	 * @param Hoa\Core\Consistency\Xcallable $f_eldnah
	 * @return mixed
	 */
	public function visit(Visitor\Element $po_element, &$f_handle = null, $f_eldnah  = null) {
		$vs_type = $po_element->getId();
		$va_children = $po_element->getChildren();

		// if no handle passed, use identity
		if ($f_handle === null) {
			$f_handle = function ($x) {
				return $x;
			};
		}

		$f_acc = &$f_handle;

		switch ($vs_type) {

			case '#function':
				$vs_name = array_shift($va_children)->accept($this, $_, $f_eldnah);
				$f_function = $this->getFunction($vs_name);
				$va_args = array();

				foreach ($va_children as $o_child) {
					$o_child->accept($this, $_, $f_eldnah);
					$va_args[] = $_();
					unset($_);
				}

				$f_acc = function () use ($f_function, $va_args, $f_acc) {
					return $f_acc($f_function->distributeArguments($va_args));
				};

				break;

			case '#stradd':
				$va_children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					return $f_acc($a() . $b);
				};

				$va_children[1]->accept($this, $f_acc, $f_eldnah);
				break;

			case '#negative':
				$va_children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function () use ($a, $f_acc) {
					return $f_acc(-$a());
				};

				break;

			case '#addition':
				$va_children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					return $f_acc($a() + $b);
				};

				$va_children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#substraction':
				$va_children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					return $f_acc($a()) - $b;
				};

				$va_children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#multiplication':
				$va_children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function ($b) use ($a, $f_acc) {
					return $f_acc($a() * $b);
				};

				$va_children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#division':
				$va_children[0]->accept($this, $a, $f_eldnah);
				$parent = $po_element->getParent();

				if (null  === $parent ||
					$type === $parent->getId()) {
					$f_acc = function ($b) use ($a, $f_acc) {
						if (0 === $b) {
							throw new \RuntimeException(
								'Division by zero is not possible.'
							);
						}

						return $f_acc($a()) / $b;
					};
				} else {
					if ('#fakegroup' !== $parent->getId()) {
						$classname = get_class($po_element);
						$group     = new $classname(
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
							if (0 === $b) {
								throw new \RuntimeException(
									'Division by zero is not possible.'
								);
							}

							return $f_acc($a() / $b);
						};
					}
				}

				$va_children[1]->accept($this, $f_acc, $f_eldnah);

				break;

			case '#fakegroup':
			case '#group':
				$va_children[0]->accept($this, $a, $f_eldnah);

				$f_acc = function () use ($a, $f_acc) {
					return $f_acc($a());
				};

				break;

			case 'token':
				$value = $po_element->getValueValue();
				$token = $po_element->getValueToken();
				$out = null;

				if ($token === 'id') {
					return $value;
				} elseif($token === 'string') {
					$out = preg_replace('/(^"|"$)/', '', $value);
				} else {
					$out = (float) $value;
				}

				$f_acc = function () use ($out, $f_acc) {
					return $f_acc($out);
				};

				break;
		}

		if ($po_element->getParent() === null) {
			return $f_acc();
		}

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
