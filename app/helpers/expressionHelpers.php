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
require_once(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser.php');
# ---------------------------------------
/**
 * DateTime diff calculation with arbitrary number of parameters.
 * Calculates the time between the earliest and the latest date in the argument list
 * and returns it as DateInterval. An arbitrary number of parameters is allowed.
 * If there's only one parameter, the current date is added to the list to calculate a valid diff.
 * @return bool|DateInterval
 */
function caGetDateTimeDiff($va_dates) {
	if(!is_array($va_dates)) { return false; }

	$vn_min_timestamp = $vn_max_timestamp = null;
	$o_tep = new TimeExpressionParser();

	foreach($va_dates as $vs_date) {
		if(!$o_tep->parse($vs_date)) {
			return false;
		}

		$va_arg_historic_stamps = $o_tep->getHistoricTimestamps();

		if(!$vn_min_timestamp) { $vn_min_timestamp = (float) $va_arg_historic_stamps['start']; }
		if(!$vn_max_timestamp) { $vn_max_timestamp = (float) $va_arg_historic_stamps['end']; }

		if(((float) $va_arg_historic_stamps['end']) > $vn_max_timestamp) {
			$vn_max_timestamp = (float) $va_arg_historic_stamps['end'];
		}

		if(((float) $va_arg_historic_stamps['start']) < $vn_min_timestamp) {
			$vn_min_timestamp = (float) $va_arg_historic_stamps['start'];
		}
	}

	$va_start_parts = $o_tep->getHistoricDateParts((string) $vn_min_timestamp);
	$va_end_parts = $o_tep->getHistoricDateParts((string) $vn_max_timestamp);

	$vs_start = $o_tep->getISODateTime($va_start_parts);
	$vs_end = $o_tep->getISODateTime($va_end_parts);

	$o_start = new DateTime($vs_start);
	$o_end = new DateTime($vs_end);
	return $o_start->diff($o_end);

}
# ---------------------------------------
/**
 * Age calculation (in years) with arbitrary number of params. @see caGetDateTimeDiff
 * @return int
 */
function caCalculateAgeInYears() {
	$va_args = func_get_args();
	$o_diff = caGetDateTimeDiff($va_args);
	if(!($o_diff instanceof DateInterval)) { return false; }
	if(!($vn_potential_return = $o_diff->y)) { // retry with 'now' added
		array_push($va_args, 'now');
		$o_diff = caGetDateTimeDiff($va_args);
		if(!($o_diff instanceof DateInterval)) { return false; }
	}
	return $o_diff->y;
}
# ---------------------------------------
/**
 * Age calculation (in days) with arbitrary number of params. @see caGetDateTimeDiff
 * @return int
 */
function caCalculateAgeInDays() {
	$va_args = func_get_args();
	$o_diff = caGetDateTimeDiff($va_args);
	if(!($o_diff instanceof DateInterval)) { return false; }
	if(!($vn_potential_return = $o_diff->days)) { // retry with 'now' added
		array_push($va_args, 'now');
		$o_diff = caGetDateTimeDiff($va_args);
		if(!($o_diff instanceof DateInterval)) { return false; }
	}
	return $o_diff->days;
}
# ---------------------------------------
function caCalculateDateRangeAvgInDays() {
	$va_date_ranges = func_get_args();

	$o_tep = new TimeExpressionParser();
	$va_days = array();

	foreach($va_date_ranges as $vs_date_range) {
		if(!$o_tep->parse($vs_date_range)) {
			print "no parse $vs_date_range";
			return false;
		}

		$va_arg_historic_stamps = $o_tep->getHistoricTimestamps();

		$va_start_parts = $o_tep->getHistoricDateParts($va_arg_historic_stamps['start']);
		$va_end_parts = $o_tep->getHistoricDateParts($va_arg_historic_stamps['end']);

		$vs_start = $o_tep->getISODateTime($va_start_parts);
		$vs_end = $o_tep->getISODateTime($va_end_parts);

		$o_start = new DateTime($vs_start);
		$o_end = new DateTime($vs_end);
		$va_days[] = $o_start->diff($o_end)->days;
	}

	if(sizeof($va_days)) {
		return array_sum($va_days) / sizeof($va_days);
	} else {
		return false;
	}
}
# ---------------------------------------