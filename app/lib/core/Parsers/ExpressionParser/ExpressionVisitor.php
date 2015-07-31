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

use Hoa\Math;
use Hoa\Visitor;

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
	 * @param null $o_handle
	 * @param null $o_eldnah
	 * @return mixed
	 */
	public function visit(Visitor\Element $po_element, &$o_handle = null, $o_eldnah  = null) {

	}

}
