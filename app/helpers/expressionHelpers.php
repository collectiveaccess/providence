<?php
/** ---------------------------------------------------------------------
 * app/helpers/expressionHelpers.php : expression parser helper functions
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
/**
 *
 */
function caCalculateDateRangeAvgInDays() {
	$va_date_ranges = func_get_args();

	$o_tep = new TimeExpressionParser();
	$va_days = array();

	foreach($va_date_ranges as $vs_date_range) {
		if(!$o_tep->parse($vs_date_range)) {
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
/**
 * Return earliest date in list of date intervals passed as arguments. Any number of arguments may be passed. 
 * If an argument is a string with dates separated by semicolons, each date will be considered as if it were a separate argument.
 * This makes it possible to pass in repeating data fields using metadata elenent placeholders.
 *
 * Parameters controlling the format of the returned date may be passed as a string formatted as URL query parameters. The parameters
 * are those supported by TimeExpressionParser::getText(), including dateFormat and timeOmit. Unless set explicitly timeOmit is assumed to be
 * true. An example parameter string: "dateFormat=iso8601&timeOmit=0" (returns earliest date in ISO format with time included)
 *
 * @return string
 */
function caGetEarliestDate() : ?string {
	[$acc, $params] = caGetDateListAndParams(func_get_args());
	
	$earliest = $earliest_ts = null;
	foreach($acc as $d) {
		if(is_array($ts = caDateToHistoricTimestamps($d))) {
			if(is_null($earliest) || ($ts['start'] < $earliest)) { 
				$earliest = $ts['start'];
				$earliest_ts = $d;
			}
		}
	}
	return $earliest ? caGetLocalizedHistoricDate($earliest, $params) : null;
}
# ---------------------------------------
/**
 * Return latest date in list of date intervals passed as arguments. Any number of arguments may be passed. 
 * If an argument is a string with dates separated by semicolons, each date will be considered as if it were a separate argument.
 * This makes it possible to pass in repeating data fields using metadata elenent placeholders.
 *
 * Parameters controlling the format of the returned date may be passed as a string formatted as URL query parameters. The parameters
 * are those supported by TimeExpressionParser::getText(), including dateFormat and timeOmit. Unless set explicitly timeOmit is assumed to be
 * true. An example parameter string: "dateFormat=iso8601&timeOmit=0" (returns latest date in ISO format with time included)
 *
 * @return string
 */
function caGetLatestDate() : ?string {
	[$acc, $params] = caGetDateListAndParams(func_get_args());
	
	$latest = $latest_ts = null;
	foreach($acc as $d) {
		if(is_array($ts = caDateToHistoricTimestamps($d))) {
			if(is_null($latest) || ($ts['end'] > $latest)) { 
				$latest = $ts['end'];
				$latest_ts = $d;
			}
		}
	}
	return $latest ? caGetLocalizedHistoricDate($latest, $params) : null;
}
# ---------------------------------------
/**
 * Return earliest date in list of date intervals passed as arguments. Any number of arguments may be passed. 
 * If an argument is a string with dates separated by semicolons, each date will be considered as if it were a separate argument.
 * This makes it possible to pass in repeating data fields using metadata elenent placeholders.
 *
 * Parameters controlling the format of the returned date may be passed as a string formatted as URL query parameters. The parameters
 * are those supported by TimeExpressionParser::getText(), including dateFormat and timeOmit. Unless set explicitly timeOmit is assumed to be
 * true. An example parameter string: "dateFormat=iso8601&timeOmit=0" (returns earliest date in ISO format with time included)
 *
 * @return array
 */
function caGetDateListAndParams($args) : array {
	$poss_params = array_pop($args);
	if(!caIsValidDate($poss_params)) {
		parse_str($poss_params, $params);
	} else {
		$args[] = $poss_params;
		$params = [];
	}
	
	$acc = [];
	foreach($args as $arg) {
		if(strpos($arg, ';') !== false) {
			$acc = array_merge($acc, explode(';', $arg));
		} else {
			$acc[] = $arg;
		}
	}
	if(!isset($params['timeOmit'])) { $params['timeOmit'] = true; }
	return [$acc, $params];
}
# ---------------------------------------
/**
 * Return true if date expression is a range rather than a single date
 *
 * @return bool
 */
function caDateIsRange($date_expression) {
	$o_tep = new TimeExpressionParser();
	if($o_tep->parse($date_expression)) {
		$va_arg_historic_stamps = $o_tep->getHistoricTimestamps();
		$v = $o_tep->getText(['dateFormat' => 'iso8601']);
		return (strpos($v, '/') !== false);
	}
	return false;
}
# ----------------------------------------
/**
 * 
 */
function caExpressionFunctionSumValues(...$args) : mixed {
	$context = array_shift($args);

	$acc = 0;
	
	$first_param = $context['params'][array_key_first($context['params'])];
	$type = $first_param['type'] ?? null;
	
	$return_as = null;
	
	foreach($context['params'] as $p) {
		if(is_int($p['type'])) {		// is attribute
			$tag = substr($p['value'], 1);
			$value_list = $context['variables']["{$tag}:array"] ?? $context['variables']["{$tag}"] ?? null;
			if($value_list && !is_array($value_list)) { $value_list = [$value_list]; }
			if($value_list) {
				foreach($value_list as $vv) {
					$ret = caParseQuantityForExpression($p['type'], $vv);
					
					switch($p['type']) {
						case __CA_ATTRIBUTE_VALUE_CURRENCY__:
							if(!$return_as) { 
								$return_as = $ret['currency']; 
							} elseif($return_as !== $ret['currency']) {
								$acc += caConvertCurrencyValue($vv, $return_as, ['numericValue' => true]);
							} else {
								$acc += $ret['value'];
							}
							break;
						case __CA_ATTRIBUTE_VALUE_LENGTH__:
							if(!$return_as) { $return_as = $ret['units']; }
							$acc += $ret['value'];	// always meters
							break;
						case __CA_ATTRIBUTE_VALUE_WEIGHT__:
							if(!$return_as) { $return_as = $ret['units']; }
							$acc += $ret['value'];	// always kilograms
							break;
						default:
							$acc += $ret['value'];
							break;
					}
				}
			}
			
		} else { // is literal
			$type = __CA_ATTRIBUTE_VALUE_NUMERIC__;
			if(is_numeric($p['value'])) {
				$acc += $p['value'];
			}
		}
	}
	$ret =  caGetQuantityForExpressionDisplay($type, $acc, ['returnAs' => $return_as]);
	return $ret['value'] ?? null;
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionAverageValues(...$args) : mixed {
	$context = array_shift($args);

	$acc = 0;
	
	$first_param = $context['params'][array_key_first($context['params'])];
	$type = $first_param['type'] ?? null;
	
	$return_as = null;
	
	$c = 0;
	foreach($context['params'] as $p) {
		if(is_int($p['type'])) {		// is attribute
			$tag = substr($p['value'], 1);
			$value_list = $context['variables']["{$tag}:array"] ?? $context['variables']["{$tag}"] ?? null;
			if($value_list && !is_array($value_list)) { $value_list = [$value_list]; }
			if($value_list) {
				foreach($value_list as $vv) {
					$ret = caParseQuantityForExpression($p['type'], $vv);
					
					switch($p['type']) {
						case __CA_ATTRIBUTE_VALUE_CURRENCY__:
							if(!$return_as) { 
								$return_as = $ret['currency']; 
							} elseif($return_as !== $ret['currency']) {
								$acc += caConvertCurrencyValue($vv, $return_as, ['numericValue' => true]);
							} else {
								$acc += $ret['value'];
							}
							break;
						case __CA_ATTRIBUTE_VALUE_LENGTH__:
							if(!$return_as) { $return_as = $ret['units']; }
							$acc += $ret['value'];	// always meters
							break;
						case __CA_ATTRIBUTE_VALUE_WEIGHT__:
							if(!$return_as) { $return_as = $ret['units']; }
							$acc += $ret['value'];	// always kilograms
							break;
						default:
							$acc += $ret['value'];
							break;
					}
					$c++;
				}
			}
			
		} else { // is literal
			$type = __CA_ATTRIBUTE_VALUE_NUMERIC__;
			if(is_numeric($p['value'])) {
				$acc += $p['value'];
				$c++;
			}
		}
	}
	$ret =  caGetQuantityForExpressionDisplay($type, $acc/$c, ['returnAs' => $return_as]);
	return $ret['value'] ?? null;
}
# ---------------------------------------
/**
 *
 */
$g_expression_quantity_parsers = [];
function caGetQuantityForExpressionDisplay(int $type, mixed $value, ?array $options=null) : ?array {
	global $g_expression_quantity_parsers;
	
	$return_as = caGetOption('returnAs', $options, null);
	
	switch($type) {
		case __CA_ATTRIBUTE_VALUE_INTEGER__:
			return ['value' => (int)$value];
			break;
		case __CA_ATTRIBUTE_VALUE_NUMERIC__:
			return ['value' => (float)$value];
			break;
		case __CA_ATTRIBUTE_VALUE_TIMECODE__:
			$tcp = $g_expression_quantity_parsers['TimecodeParser'] ?? ($g_expression_quantity_parsers['TimecodeParser'] = new TimecodeParser());
			if($tcp->parse($value)) {
				return ['value' => $tcp->getText()];
			}
			return null;
			break;
		case __CA_ATTRIBUTE_VALUE_CURRENCY__:
			return ['value' => $return_as.' '.$value];
			break;
		case __CA_ATTRIBUTE_VALUE_FILESIZE__:
			return ['value' => caHumanFilesize($value)];
			break;
		case __CA_ATTRIBUTE_VALUE_LENGTH__:
			$m = caParseLengthDimension($value);
			return ['value' => $m->convertTo($return_as, 6)];
			break;
		case __CA_ATTRIBUTE_VALUE_WEIGHT__:
			$m = caParseWeightDimension($value);
			return ['value' => $m->convertTo($return_as, 6)];
			break;
	}
	return ['value' => $value];
}
# ---------------------------------------
/**
 *
 */
function caParseQuantityForExpression(int $type, mixed $value, ?array $options=null) : ?array {
	global $g_expression_quantity_parsers;
	switch($type) {
		case __CA_ATTRIBUTE_VALUE_INTEGER__:
			return ['value' => (int)$value];
			break;
		case __CA_ATTRIBUTE_VALUE_NUMERIC__:
			return ['value' => (float)$value];
			break;
		case __CA_ATTRIBUTE_VALUE_TIMECODE__:
			$tcp = $g_expression_quantity_parsers['TimecodeParser'] ?? ($g_expression_quantity_parsers['TimecodeParser'] = new TimecodeParser());
			if($tcp->parse($value)) {
				return ['value' => (float)$tcp->getSeconds()];
			}
			return null;
			break;
		case __CA_ATTRIBUTE_VALUE_CURRENCY__:
			return caParseCurrencyValue($value);
			break;
		case __CA_ATTRIBUTE_VALUE_FILESIZE__:
			return ['value' => caParseHumanFilesize($value)];
			break;
		case __CA_ATTRIBUTE_VALUE_LENGTH__:
			$m = caParseLengthDimension($value);
			return ['value' => $m->convertTo('METER',6, 'en_US'), 'units' => $m->getType()];
			break;
		case __CA_ATTRIBUTE_VALUE_WEIGHT__:
			$m = caParseWeightDimension($value);
			return ['value' => $m->convertTo('KILOGRAM', 6, 'en_US'), 'units' => $m->getType()];
			break;
	}
	return null;
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionValueCount(...$args) : mixed {
	$context = array_shift($args);
	
	$c = 0;
	foreach($context['params'] as $p) {
		$tag = substr($p['value'], 1);
		$value_list = $context['variables']["{$tag}:array"] ?? $context['variables']["{$tag}"] ?? null;
		$c += (is_array($value_list)) ? sizeof($value_list) : 1;
	}
	return $c;
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionUniqueValueCount(...$args) : mixed {
	$context = array_shift($args);
	
	$acc = [];
	foreach($context['params'] as $p) {
		$tag = substr($p['value'], 1);
		$value_list = $context['variables']["{$tag}:array"] ?? [$context['variables']["{$tag}"]] ?? null;
		$acc = array_merge($acc, $value_list);
	}
	$acc = array_unique($acc);
	return sizeof($acc);;
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionMaxValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('max', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionMinValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('min', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionAbsValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('abs', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionCeilValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('ceil', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionFloorValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('floor', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionIntValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('int', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionRoundValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('round', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionRandomValue(...$args) : mixed {
	return caExpressionFunctionNumericValueTransform('random', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionNumericValueTransform(string $function, array $args) : mixed {
	$context = array_shift($args);
	
	$mv = $mv_display = null;
	foreach($context['params'] as $i => $p) {
		$tag = ($p['value'][0] === '^') ? substr($p['value'], 1) : null;
		$value_list = ($tag) ? ($context['variables']["{$tag}:array"] ?? [$context['variables']["{$tag}"]] ?? null) : [$p['value']];
		foreach($value_list as $v) {
			$t = $p['type'];
			
			if(is_numeric($p['type'])) {
				$pq = caParseQuantityForExpression($p['type'], $v);
				$vv = $pq['value'];
			} else {
				$vv = $v;
			}
			$trigger = false;
			switch($function) {
				case 'min':
					$trigger = (is_null($mv) || ($vv < $mv));
					break;
				case 'max':
					$trigger = (is_null($mv) || ($vv > $mv));
					break;
				case 'abs':
					return abs($vv);
					break;
				case 'ceil':
					return ceil($vv);
					break;
				case 'floor':
					return floor($vv);
					break;
				case 'int':
					return intval($vv);
					break;
				case 'round':
					return round($vv);
					break;
				case 'random':
					return rand($args[0], $args[1]);
					break;
			}
			
			if($trigger) { 
				$mv = $vv; 
				$mv_display = $v; 
			}
		}
	}
	
	return $mv_display;
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionStringWordCount(...$args) : mixed {
	return caExpressionFunctionStringValueTransform('wc', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionStringLength(...$args) : mixed {
	return caExpressionFunctionStringValueTransform('length', $args);
}
# ---------------------------------------
/**
 * 
 */
function caExpressionFunctionStringValueTransform(string $function, array $args) : mixed {
	$context = array_shift($args);
	
	$mv = null;
	foreach($context['params'] as $i => $p) {
		$tag = ($p['value'][0] === '^') ? substr($p['value'], 1) : null;
		$value_list = ($tag) ? ($context['variables']["{$tag}:array"] ?? [$context['variables']["{$tag}"]] ?? null) : [$p['value']];
		foreach($value_list as $v) {
			$t = $p['type'];
			
			switch($function) {
				case 'wc':
					if(is_null($mv)) { $mv = 0; }
					$words = preg_split('![ \t\n\r]+!u', $v);
					$mv += sizeof($words);
					break;
				case 'length':
					if(is_null($mv)) { $mv = 0; }
					$mv += mb_strlen($v);
					break;
			}
		}
	}
	
	return $mv;
}
# ---------------------------------------
/**
 *
 */
// 'abs'			=> xcallable('abs'),
// 'ceil'			=> xcallable('ceil'),
// 'floor'			=> xcallable('floor'),
// 'int'			=> xcallable('intval'),
// 'max'			=> xcallable('max'),
// 'min'			=> xcallable('min'),
// 'rand'			=> xcallable('rand'),
// 'round'			=> xcallable('round'),
// 'random'		=> xcallable('rand'),
// 'current'		=> xcallable('caIsCurrentDate'),
// 'isvaliddate'	=> xcallable('caIsValidDate'),
// 'future'		=> xcallable('caDateEndsInFuture'),
// 'past'		=> xcallable('caDateStartsInPast'),
// 'wc'			=> xcallable('str_word_count'),
// 'length'		=> xcallable('strlen'),
// 'date'			=> xcallable('caDateToHistoricTimestamp'),
// 'formatdate'	=> xcallable('caFormatDate'),
// 'formatgmdate'	=> xcallable('caFormatGMDate'),
	// 'sizeof'		=> xcallable('caExpressionCount'),
	// 'count'			=> xcallable('caExpressionCount'),
	// 'valueCount'	=> xcallable('caValueCount'),
	// 'uniqueValueCount'	=> xcallable('caUniqueValueCount'),
// 'age'			=> xcallable('caCalculateAgeInYears'),
// 'ageyears'		=> xcallable('caCalculateAgeInYears'),
// 'agedays'		=> xcallable('caCalculateAgeInDays'),
// 'avgdays'		=> xcallable('caCalculateDateRangeAvgInDays'),
// 'earliestDate'	=> xcallable('caGetEarliestDate'),
// 'latestDate'	=> xcallable('caGetLatestDate'),
	// 'average' 		=> xcallable($average),
	// 'avg'     		=> xcallable($average),
	// 'sum'			=> xcallable('caSumExpressionValues'),
// 'replace'		=> xcallable('preg_replace'),
// 'join'			=> xcallable($implode),
// 'implode'		=> xcallable($implode),
// 'trim'			=> xcallable('trim'),
// 'idnoUseCount'	=> xcallable('caIdnoUseCount'),
// 'dateIsRange'	=> xcallable('caDateIsRange'),
// 'fromUnixtime'	=> xcallable(function($arg) { return date('c', $arg); }),
// 'existsInList'	=> xcallable(function($arg1, $arg2) { return caItemExists($arg1, $arg2); })
