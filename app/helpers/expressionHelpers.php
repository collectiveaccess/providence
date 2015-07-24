<?php
/** ---------------------------------------------------------------------
 * app/helpers/expressionHelpers.php : expression parser helper functions
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */


# ---------------------------------------
require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
# ---------------------------------------
/**
* Returns the number of parameters passed
* @return int
*/
function caGetFunctionParamCount() {
	return func_num_args();
}
# ---------------------------------------
/**
 * Age calculation
 * @param string $ps_dob valid TimeExpressionParser expression for date of birth, or total life date range
 * @param string|null $ps_dod valid TimeExpressionParser expression for date of death (optional)
 * @return int
 */
function caCalculateAge($ps_dob, $ps_dod=null) {
	$o_tep = new TimeExpressionParser();

	if(!$o_tep->parse($ps_dob)) {
		return false;
	}
	$va_start_timestamps = $o_tep->getHistoricTimestamps();
	$va_start_parts = $o_tep->getHistoricDateParts($va_start_timestamps['start']);
	$vs_start = $o_tep->getISODateTime($va_start_parts);

	$va_start_parts_end = $o_tep->getHistoricDateParts($va_start_timestamps['end']);

	// if the first parameter ($ps_dob) is a long date range, treat it as start and end of the life span
	if($va_start_parts['year'] != $va_start_parts_end['year']) {
		$ps_dod = $o_tep->getISODateTime($va_start_parts_end);
	}

	if($ps_dod) {
		if(!$o_tep->parse($ps_dod)) {
			return false;
		}
		$va_end_timestamps = $o_tep->getHistoricTimestamps();
		$va_end_parts = $o_tep->getHistoricDateParts($va_end_timestamps['end']);
		$vs_end = $o_tep->getISODateTime($va_end_parts);
	} else { // fallback: use now
		$vs_end = date('c');
	}

	$o_start = new DateTime($vs_start);
	$o_end = new DateTime($vs_end);
	$o_diff = $o_start->diff($o_end);

	return $o_diff->y;
}
# ---------------------------------------
