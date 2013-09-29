<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/TimeExpressionParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2013 Whirl-i-Gig
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
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");

# --- Token types
define("TEP_TOKEN_INTEGER", 0);
define("TEP_TOKEN_ALPHA", 1);
define("TEP_TOKEN_ALPHA_MONTH", 2);
define("TEP_TOKEN_MERIDIAN", 3);
define("TEP_TOKEN_MYA", 4);
define("TEP_TOKEN_CIRCA", 5);
define("TEP_TOKEN_PUNCTUATION", 6);
define("TEP_TOKEN_ERA", 7);
define("TEP_TOKEN_DATE", 8);
define("TEP_TOKEN_TIME", 9);
define("TEP_TOKEN_BEFORE", 10);
define("TEP_TOKEN_AFTER", 11);
define("TEP_TOKEN_MARGIN_OF_ERROR", 12);
define("TEP_TOKEN_PRESENT", 13);
define("TEP_TOKEN_NOW", 14);
define("TEP_TOKEN_YESTERDAY", 15);
define("TEP_TOKEN_TODAY", 16);
define("TEP_TOKEN_TOMORROW", 17);
define("TEP_TOKEN_PRE_RANGE_CONJUNCTION", 18);
define("TEP_TOKEN_RANGE_CONJUNCTION", 19);
define("TEP_TOKEN_TIME_CONJUNCTION", 20);
define("TEP_TOKEN_QUESTION_MARK_UNCERTAINTY", 21);
define("TEP_TOKEN_SEASON_WINTER", 22);
define("TEP_TOKEN_SEASON_SPRING", 23);
define("TEP_TOKEN_SEASON_SUMMER", 24);
define("TEP_TOKEN_SEASON_AUTUMN", 25);
define("TEP_TOKEN_UNDATED", 26);

# --- Meridian types
define("TEP_MERIDIAN_AM", 0);
define("TEP_MERIDIAN_PM", 1);

# --- Era types
define("TEP_ERA_AD", 0);
define("TEP_ERA_BC", 1);

# --- Main parse loop states
define('TEP_STATE_BEGIN', 0);
define('TEP_STATE_CIRCA', 1);
define('TEP_STATE_DATE_RANGE_END_DATE', 2);
define('TEP_STATE_DATE_RANGE_CONJUNCTION', 3);
define('TEP_STATE_TIME_RANGE_END_TIME', 4);
define('TEP_STATE_TIME_RANGE_CONJUNCTION', 5);
define('TEP_STATE_BEFORE_GET_DATE', 6);
define('TEP_STATE_AFTER_GET_DATE', 7);
define('TEP_STATE_ACCEPT', 8);

# --- Date element parse states
define("TEP_STATE_BEGIN_DATE_ELEMENT",0);
define("TEP_STATE_DATE_ELEMENT_GET_MONTH_NEXT",1);
define("TEP_STATE_DATE_ELEMENT_GET_DAY_NEXT",2);
define("TEP_STATE_DATE_ELEMENT_GET_YEAR_NEXT",3);

# --- Time expression parse states
define("TEP_STATE_BEGIN_TIME_EXPRESSION",0);
define("TEP_STATE_TIME_GET_MERIDIAN",1);
define("TEP_STATE_TIME_GET_UNCERTAINTY",2);

# --- Date expression parse states
define("TEP_STATE_BEGIN_DATE_EXPRESSION",0);
define("TEP_STATE_DATE_GET_ERA",1);
define("TEP_STATE_DATE_GET_TIME",2);
define("TEP_STATE_DATE_GET_UNCERTAINTY",3);
define("TEP_STATE_DATE_SET_UNCERTAINTY",4);

# --- Numeric values for "start of universe" and "end of universe"
# --- used for ongoing partially unbounded date ranges (ie. "After 1950")
define("TEP_START_OF_UNIVERSE", -2000000000);
define("TEP_END_OF_UNIVERSE", 2000000000);
define("TEP_START_OF_UNIX_UNIVERSE", 0);
define("TEP_END_OF_UNIX_UNIVERSE", pow(2,32));

# --- Error codes
define("TEP_ERROR_RANGE_ERROR", 1);				# start date must be before end date...
define("TEP_ERROR_INVALID_DATE", 2);			# error while parsing date
define("TEP_ERROR_INVALID_TIME", 3);			# error while parsing time
define("TEP_ERROR_INVALID_UNCERTAINTY", 4);		# error while parsing uncertainty
define("TEP_ERROR_UNCERTAINTY_OVERFLOW", 5);
define("TEP_ERROR_INVALID_EXPRESSION", 6);		# general parse error (expression is just plain wrong)
define("TEP_ERROR_TRAILING_JUNK", 7);			# extra tokens after otherwise valid expression
define("TEP_ERROR_PARSER_ERROR", 8);			# internal error in parse - stuff that "shouldn't happen"


class TimeExpressionParser {
	# -------------------------------------------------------------------
	private $ops_language = null;				// iso-639-1 + country code for current language [default is US english]
	private $opo_language_settings = null;		// Configuration object
	private $opo_datetime_settings = null;			// Configuration object
	private $opa_tokens;
	
	private $ops_error = "";					// error message
	private $opn_error = 0;						// error code (one of the TEP_ERROR_* codes above; 0 indicates no error)
	
	private $opo_app_config;
	
	// Unixtime-format date/time values
	private $opn_start_unixtime;
	private $opn_end_unixtime;
	
	// Historic (floating point) date/time values
	private $opn_start_historic;
	private $opn_end_historic;
	
	// Time values (number of seconds since midnight)
	private $opn_start_time;
	private $opn_end_time;
	
	private $opa_error_messages; // error messages
	
	public $opb_debug = false;
	
	# -------------------------------------------------------------------
	# Constructor
	# -------------------------------------------------------------------
	public function __construct($ps_expression=null, $ps_iso_code=null, $pb_debug=false) {	
		global $g_ui_locale;
		
		$o_config = Configuration::load();
		$this->opo_datetime_settings = Configuration::load($o_config->get('datetime_config'));
		
		if (!$ps_iso_code) { $ps_iso_code = $g_ui_locale; }
		if (!$ps_iso_code) { $ps_iso_code = 'en_US'; }
		
		$this->opa_error_messages = array(
			_t("No error"), _t("Start must be before date in range"), _t("Invalid date"), _t("Invalid time"), 
			_t("Invalid uncertainty"), _t("Uncertainty must not exceed 9 digits"), _t("Invalid expression"), 
			_t("Trailing characters in otherwise valid expression"), _t("Parser error")
		);
		
		if (!$this->setLanguage($ps_iso_code)) {
			die("Could not load language '$ps_iso_code'");
		}
		$this->setDebug($pb_debug);
		
		$this->init();
		if ($ps_expression) { $this->parse($ps_expression); }
	}
	# -------------------------------------------------------------------
	# Parser
	# -------------------------------------------------------------------
	public function init() {
		$this->opn_start_unixtime = $this->opn_end_unixtime = null;
		$this->opn_start_historic = $this->opn_end_historic = null;
		$this->opn_start_time = $this->opn_end_time = null;
		
		$this->ops_error = "";
		$this->opn_error = 0;
	}
	# -------------------------------------------------------------------
	public function parseTime($ps_expression) {
		if ($this->parse($ps_expression, array('mode' => 'time'))) {
			return $this->getTimes();
		}
		
		return false;
	}
	# -------------------------------------------------------------------
	public function parseDate($ps_expression) {
		if ($this->parse($ps_expression, array('mode' => 'date'))) {
			return $this->getHistoricTimestamps();
		}
		
		return false;
	}
	# -------------------------------------------------------------------
	public function parseDateTime($ps_expression) {
		if ($this->parse($ps_expression, array('mode' => 'datetime'))) {
			return $this->getHistoricTimestamps();
		}
		
		return false;
	}
	# -------------------------------------------------------------------
	public function parse($ps_expression, $pa_options=null) {
	
		$ps_expression = caRemoveAccents($ps_expression);
		
		if (!$pa_options) { $pa_options = array(); }
		$this->init();
		
		if ($this->tokenize($this->preprocess($ps_expression)) == 0) {
			// nothing to parse
			return false;
		} 

		$va_dates = array();
		
		$vn_state = TEP_STATE_BEGIN;
		$vb_can_accept = false;
		
		$vb_circa_is_set = false;
		while($va_token = $this->peekToken()) {
			if ($this->getParseError()) { break; }
			switch($vn_state) {
				# -------------------------------------------------------
				case TEP_STATE_BEGIN:
					switch($va_token['type']) {
						# ----------------------
						case TEP_TOKEN_INTEGER:
							// is this a quarter century expression?
							if (((int)$va_token['value'] > 0) && ((int)$va_token['value'] <= 21)) {
								$va_peek = $this->peekToken(2);
								if ($va_peek['type'] == TEP_TOKEN_ALPHA) {
									if (preg_match('!^Q([\d]{1})$!i', $va_peek['value'], $va_matches)) {
										$vn_q = (int)$va_matches[1];
										if (($vn_q >= 1) && ($vn_q <= 4)) {
											$vn_start_year = (((int)$va_token['value'] -1) * 100) + (($vn_q - 1) * 25);
											$vn_end_year = (((int)$va_token['value'] -1) * 100) + (($vn_q) * 25);
											$va_dates['start'] = array(
												'month' => 1, 'day' => 1, 'year' => $vn_start_year, 'era' => TEP_ERA_AD,
												'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => false, 'dont_window' => true
											);
											$va_dates['end'] = array(
												'month' => 12, 'day' => 31, 'year' => $vn_end_year, 'era' => TEP_ERA_AD,
												'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => false, 'dont_window' => true
											);
											$this->skipToken();
											$this->skipToken();
											
											$vn_state = TEP_STATE_ACCEPT;
											$vb_can_accept = true;
											break(2);
										}
									}
								}
							}
							
							//
							// Look for MYA dates
							//
							$va_peek = $this->peekToken(2);
							if ($va_peek['type'] == TEP_TOKEN_MYA) {
								$va_dates['start'] = array(
									'month' => 1, 'day' => 1, 'year' => intval($va_token['value']) * -1000000,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => false, 'dont_window' => true
								);
								$va_dates['end'] = array(
									'month' => 12, 'day' => 31, 'year' => intval($va_token['value']) * -1000000,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => false, 'dont_window' => true
								);
								$this->skipToken();
								$this->skipToken();
							
								$vn_state = TEP_STATE_ACCEPT;
								$vb_can_accept = true;
								break(2);
							}
							break;
						# ----------------------
					}
					
					if ($va_date = $this->_parseDateExpression()) {
						$va_dates['start'] = $va_date;
						$vn_state = TEP_STATE_DATE_RANGE_CONJUNCTION;
						$vb_can_accept = true;
						break;
					} else {
						switch($va_token['type']) {
							# ----------------------
							case TEP_TOKEN_SEASON_WINTER:
							case TEP_TOKEN_SEASON_SPRING:
							case TEP_TOKEN_SEASON_SUMMER:
							case TEP_TOKEN_SEASON_AUTUMN:
								$this->skipToken();
								$va_peek = $this->peekToken();
								if ($va_peek['type'] == TEP_TOKEN_INTEGER) {
									$vn_start_year = $va_peek['value'];
								} else {
									$va_today = getdate();
									$vn_start_year = $va_today['year'];
								}
								$this->skipToken();
								
								$vn_year_offset = 0;
								switch($va_token['type']) {
									case TEP_TOKEN_SEASON_WINTER:
										$vn_start_month = 12; $vn_end_month = 3; $vn_year_offset = 1;
										break;
									case TEP_TOKEN_SEASON_SPRING:
										$vn_start_month = 3; $vn_end_month = 6; 
										break;
									case TEP_TOKEN_SEASON_SUMMER:
										$vn_start_month = 6; $vn_end_month = 9; 
										break;
									case TEP_TOKEN_SEASON_AUTUMN:
										$vn_start_month = 9; $vn_end_month = 12; 
										break;
								}
								
								$va_dates['start'] = array(
									'month' => $vn_start_month, 'day' => 21, 'year' => $vn_start_year,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => false
								);
								$va_dates['end'] = array(
									'month' => $vn_end_month, 'day' => 20, 'year' => $vn_start_year + $vn_year_offset,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => false
								);
								$vn_state = TEP_STATE_ACCEPT;
								$vb_can_accept = true;
								break;
							
							# ----------------------
							case TEP_TOKEN_UNDATED:
								$va_dates['start']  = array(
									'month' => null, 'day' => null, 'year' => nu,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0, 'is_undated' => true
								);
								$va_dates['end']  = array(
									'month' => null, 'day' => null, 'year' => null,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0, 'is_undated' => true
								);
								
								$this->skipToken();
								$vn_state = TEP_STATE_ACCEPT;
								$vb_can_accept = true;
								
								break;
							# ----------------------
							case TEP_TOKEN_ALPHA:
								#
								# is this a decade expression?
								#
								$va_decade_dates = $this->_parseDecade($va_token, $vb_circa_is_set);
								if (sizeof($va_decade_dates) > 0) { // found decade
									$va_next_token = $this->peekToken();
									if (is_array($va_next_token) && ($va_next_token['type'] == TEP_TOKEN_RANGE_CONJUNCTION)) { // decade is part of range
										$va_dates['start'] = $va_decade_dates['start'];
										$vn_state = TEP_STATE_DATE_RANGE_END_DATE;
										$this->skipToken();	// skip range conjunction
										break;
									} else {
										$va_dates = $va_decade_dates;
										$vn_state = TEP_STATE_ACCEPT;
										$vb_can_accept = true;
										break;
									}
								}
								
								#
								# is this a century expression?
								#
								$this->skipToken();
								$va_next_token = $this->getToken();
								
								$vs_next_token_lc = mb_strtolower($va_next_token['value']);
								$vn_use_romans = $this->opo_datetime_settings->get("useRomanNumeralsForCenturies");
																
								if (
									($vn_use_romans && in_array($vs_next_token_lc, $this->opo_language_settings->getList("centuryIndicator")) && preg_match("/^([MDCLXVI]+)(.*)$/", $va_token['value'], $va_roman_matches))
									||	
									((in_array($vs_next_token_lc, $this->opo_language_settings->getList("centuryIndicator"))) && (preg_match("/^([\d]+)(.*)$/", $va_token['value'], $va_matches)))
									||
									(preg_match("/^([\d]{2})[_]{2}$/", $va_token['value'], $va_matches))
								) {	

									$va_ordinals = $this->opo_language_settings->getList("ordinalSuffixes");
									$va_ordinals[] = $this->opo_language_settings->get("ordinalSuffixDefault");

									//if (in_array($va_matches[2], $va_ordinals)) {
										if ($vn_use_romans && caIsRomanNumerals($va_roman_matches[1])) {
											$vn_century = intval(caRomanArabic($va_roman_matches[1]));
										} else {
											$vn_century = intval($va_matches[1]);
										} 
										
										
										if (in_array($vs_next_token_lc, $this->opo_language_settings->getList("centuryIndicator"))) {
											$va_next_token = null;
										}
										
										$vn_is_circa = 0;
										while($va_modfier_token = (is_array($va_next_token) ? $va_next_token : $this->getToken())) {
											$va_next_token = null;
											switch($va_modfier_token['type']) {
												case TEP_TOKEN_ERA:
													if($va_modfier_token['era'] == TEP_ERA_BC) {
														$vn_century *= -1;
													}
													break;
												case TEP_TOKEN_QUESTION_MARK_UNCERTAINTY:
													$vn_is_circa = 1;
													break;
												default:
													$this->setParseError($va_modfier_token, TEP_ERROR_TRAILING_JUNK);
													break;
											}
										}
										
										if (preg_match("/^([\d]{2})[_]{2}$/", $va_token['value'])) {
											$vn_century += 1;
										}
										
										if (($vn_century > -100) && ($vn_century < 100)) {
											if ($vn_century < 0) { 
												$vn_start_year = $vn_century * 100;
												$vn_end_year = ($vn_century * 100) + 99;
											} else {
												$vn_start_year = ($vn_century - 1) * 100;
												$vn_end_year = (($vn_century - 1) * 100) + 99;
											}
											$va_dates['start'] = array(
												'month' => 1, 'day' => 1, 'year' => $vn_start_year,
												'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa
											);
											$va_dates['end'] = array(
												'month' => 12, 'day' => 31, 'year' => $vn_end_year,
												'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa
											);
											$vn_state = TEP_STATE_ACCEPT;
											$vb_can_accept = true;
											break;
										}
									//}
								}
								
								$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
								break;
							# ----------------------
							case TEP_TOKEN_INTEGER:
								if($va_time_element = $this->_parseTimeExpression()) {
									$va_dates['start'] = $va_time_element;
									$vn_state = TEP_STATE_TIME_RANGE_CONJUNCTION;
									$vb_can_accept = true;
								} else {
									$this->skipToken();
									if ($this->tokens() == 1) {
										if ($va_token_mya = $this->getToken()) {
											if ($va_token_mya['type'] == TEP_TOKEN_MYA) {
												$va_dates['start'] = array(
													'month' => null, 'day' => null, 'year' => intval($va_token['value']) * -1000000,
													'hours' => null, 'minutes' => null, 'seconds' => null,
													'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
												);
												$va_dates['end'] = array(
													'month' => 12, 'day' => 31, 'year' => intval($va_token['value']) * -1000000,
													'hours' => null, 'minutes' => null, 'seconds' => null,
													'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
												);
												$vb_can_accept = true;
												
												break;
											}
										}
										$vb_can_accept = false;
										$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
									}
								}
								break;
							# ----------------------
							case TEP_TOKEN_CIRCA:
								$vb_circa_is_set = true;
								$this->skipToken();
								if($va_date_element = $this->_parseDateElement()) {
									if ($va_peek = $this->peekToken()) {
										if ($va_peek['type'] == TEP_TOKEN_ERA) {
											$this->skipToken();
											if ($va_peek['era'] == TEP_ERA_BC) {
												$va_date_element['year'] *= -1;
											}
										}
									}
									$va_dates['start'] = array(
										'month' => $va_date_element['month'], 'day' => $va_date_element['day'], 
										'year' => $va_date_element['year'],
										'hours' => null, 'minutes' => null, 'seconds' => null,
										'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 1
									);
									$va_dates['end'] = array(
										'month' => $va_date_element['month'], 'day' => $va_date_element['day'], 
										'year' => $va_date_element['year'],
										'hours' => null, 'minutes' => null, 'seconds' => null,
										'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 1
									);
									
									$vn_state = TEP_STATE_DATE_RANGE_CONJUNCTION;
									$vb_can_accept = true;
								}
								break;
							# ----------------------
							case TEP_TOKEN_TIME:
								if($va_time_element = $this->_parseTimeExpression()) {
									$va_dates['start'] = $va_time_element;
									$vn_state = TEP_STATE_TIME_RANGE_CONJUNCTION;
									$vb_can_accept = true;
								}
								break;
							# ----------------------
							case TEP_TOKEN_PRE_RANGE_CONJUNCTION:
								$this->skipToken();
								break;
							# ----------------------
							case TEP_TOKEN_BEFORE:
								$this->skipToken();
								$vn_state = TEP_STATE_BEFORE_GET_DATE;
								break;
							# ----------------------
							case TEP_TOKEN_AFTER:
								$this->skipToken();
								$vn_state = TEP_STATE_AFTER_GET_DATE;
								break;
							# ----------------------
							default:
								$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
								$vb_can_accept = false;
								break;
							# ----------------------
						}
						break;
					# ----------------------
				}
				break;
			# -------------------------------------------------------
			case TEP_STATE_BEFORE_GET_DATE:
				if ($va_date = $this->_parseDateExpression()) {
					$va_dates['start'] = array(
						'month' => null, 'day' => null, 
						'year' => TEP_START_OF_UNIVERSE,
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
					);
					$va_dates['end'] = $va_date;
					$this->skipToken();
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_DATE);	
					$vb_can_accept = false;
				}
				break;
			# -------------------------------------------------------
			case TEP_STATE_AFTER_GET_DATE:
				if ($va_date = $this->_parseDateExpression()) {
					$va_dates['start'] = $va_date;
					$va_dates['end'] = array(
						'month' => null, 'day' => null, 
						'year' => TEP_END_OF_UNIVERSE,
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
					);
					$this->skipToken();
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_DATE);	
					$vb_can_accept = false;
				}
				break;
			# -------------------------------------------------------
			case TEP_STATE_DATE_RANGE_CONJUNCTION:
				$vb_circa_is_set = false;
				if ($va_token['type'] == TEP_TOKEN_RANGE_CONJUNCTION) {
					$this->skipToken();
					if (!$va_dates['start']['day']) { $va_dates['start']['day'] = 1; }
					if (!$va_dates['start']['month']) { $va_dates['start']['month'] = 1; }
					$vn_state = TEP_STATE_DATE_RANGE_END_DATE;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
				}
				$vb_can_accept = false;
				break;
			# -------------------------------------------------------
			case TEP_STATE_DATE_RANGE_END_DATE:
				$vb_circa_is_set = (bool)$va_dates['start']['is_circa'];	// carry over circa-ness from start
				
				#
				# is this a decade expression?
				#
				$va_decade_dates = $this->_parseDecade($va_token, $vb_circa_is_set);
			
				if (sizeof($va_decade_dates) > 0) { // found decade
					$va_dates['end'] = $va_decade_dates['end'];
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
					
					break;
				}
								
				if ($va_date = $this->_parseDateExpression(array('start' => $va_dates['start']))) {
					$va_dates['end'] = $va_date;
					if (isset($va_dates['start']['is_circa']) && $va_dates['start']['is_circa']) {
						$va_dates['end']['is_circa'] = true;
					}
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);	
					$vb_can_accept = false;
				}
				break;
			# -------------------------------------------------------
			case TEP_STATE_TIME_RANGE_CONJUNCTION:
				if ($va_token['type'] == TEP_TOKEN_RANGE_CONJUNCTION) {
					$this->skipToken();
					$vn_state = TEP_STATE_TIME_RANGE_END_TIME;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
				}
				$vb_can_accept = false;
				break;
			# -------------------------------------------------------
			case TEP_STATE_TIME_RANGE_END_TIME:
				if ($va_time = $this->_parseTimeExpression()) {
					$va_dates['end'] = $va_time;
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_TIME);	
					$vb_can_accept = false;
				}
				break;
			# -------------------------------------------------------
			case TEP_STATE_ACCEPT:
				$this->setParseError($va_token, TEP_ERROR_TRAILING_JUNK);
				$vb_can_accept = true;
				break;
			}
			# -------------------------------------------------------
		}
		
		
		if ($this->getParseError()) {
			return false;
		} else {
			if (!$vb_can_accept) {
				if ($vn_state == TEP_STATE_DATE_RANGE_END_DATE) {
					// Allow omission of end date of range to be taken as present/ongoing date
					// This lets a user enter dates like "1971 - " instead of "after 1971"
					$va_dates['end'] = array(
						'month' => null, 'day' => null, 
						'year' => TEP_END_OF_UNIVERSE,
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
					);
					return $this->_processParseResults($va_dates, $pa_options);
				}
				$this->setParseError(null, TEP_ERROR_TRAILING_JUNK);
				return false;
			} else {
				return $this->_processParseResults($va_dates, $pa_options);
			}
		}
	}
	# -------------------------------------------------------------------
	private function preprocess($ps_expression) {
		# convert
		$va_dict = $this->opo_datetime_settings->getAssoc("expressions");
		$vs_lc_expression = mb_strtolower($ps_expression);
		if (isset($va_dict[$vs_lc_expression])) {
			$ps_expression = $va_dict[$vs_lc_expression];
		}
	
		# remove commas
		$ps_expression = str_replace(',', ' ', $ps_expression);
		$ps_expression = preg_replace('![ ]+!', ' ', $ps_expression);
				
		# remove articles
		$definiteArticles = $this->opo_language_settings->getList("definiteArticles");
		if (sizeof($definiteArticles)) {
			$ps_expression=" ".$ps_expression." ";
			foreach($definiteArticles as $article) {
				$ps_expression = str_ireplace(" ".$article." ", " ", $ps_expression);
			}
		}
		$indefiniteArticles = $this->opo_language_settings->getList("indefiniteArticles");
		if (sizeof($indefiniteArticles)) {
			$ps_expression=" ".$ps_expression." ";
			foreach($indefiniteArticles as $article) {
				$ps_expression = str_ireplace(" ".$article." ", " ", $ps_expression);
			}
		}
		$ps_expression=trim($ps_expression);
		
		#replace time keywords containing spaces with conf defined replacement, allowing treatments for expression like "av. J.-C." in french
		$wordsWithSpaces = $this->opo_language_settings->getList("wordsWithSpaces");
		$wordsWithSpacesReplacements = $this->opo_language_settings->getList("wordsWithSpacesReplacements");
		if ((sizeof($wordsWithSpaces)) && (sizeof($wordsWithSpacesReplacements))) {
			$ps_expression=str_replace($wordsWithSpaces,$wordsWithSpacesReplacements,$ps_expression);
		}
		
		# separate '?' from words
		$ps_expression = preg_replace('!([^\?\/]+)\?{1}([^\?]+)!', '\1 ? \2', $ps_expression);
		$ps_expression = preg_replace('!([^\?\/]+)\?{1}$!', '\1 ?', $ps_expression);
		
		# Remove UTC offset if present
		$ps_expression = preg_replace("/(T[\d]{1,2}:[\d]{2}:[\d]{2})-[\d]{1,2}:[\d]{2}/i", "$1", $ps_expression);
		
		# distinguish w3cdtf dates since we already use '-' for ranges
		$ps_expression = preg_replace("/([\d]{4})-([\d]{2})-([\d]{2})/", "$1#$2#$3", $ps_expression);
		
		# distinguish w3cdtf dates since we already use '-' for ranges
		$ps_expression = preg_replace("/([\d]{4})-([\d]{2})([^\d]+)/", "$1#$2$3", $ps_expression);
		
		# distinguish dd-MMM-yy and dd-MMM-yyyy dates since we already use '-' for ranges (ex. 10-JUN-80 or 10-JUN-1980)
		$ps_expression = preg_replace("/([\d]{1,2})-([A-Za-z]{3,15})-([\d]{2,4})/", "$1#$2#$3", $ps_expression);
		
		# convert dd-mm-yyyy dates to dd/mm/yyyy to prevent our range conjunction code below doesn't mangle it
		$ps_expression = preg_replace("/([\d]{2})-([\d]{2})-([\d]{4})/", "$1/$2/$3", $ps_expression);
		
		if (preg_match("/([\d]{4})-([\d]{2})$/", $ps_expression, $va_matches)) {
			if (intval($va_matches[2]) > 12) {
				$ps_expression = preg_replace("/([\d]{4})-([\d]{2})$/", "$1-".substr($va_matches[1], 0, 2)."$2", $ps_expression);
			} else {
				$ps_expression = preg_replace("/([\d]{4})-([\d]{2})$/", "$1#$2", $ps_expression);
			}
		}
		
		# process 6-number year ranges
		
		# replace '-' used to express decades (eg. 192-) and centuries (eg. 19--) with underscores since we use '-' for ranges
		if (preg_match('![\d]{4}\-!', $ps_expression)) {
			$ps_expression = preg_replace("![\-]{1}!", " - ", $ps_expression);
		} else {
			$ps_expression = preg_replace('!([\d]{2})[\-]{2}!', '\1__', $ps_expression);
			$ps_expression = preg_replace('!([\d]{3})[\-]{1}$!', '\1_', $ps_expression);
			$ps_expression = preg_replace('!([\d]{3})[\-]{1}[\D]+!', '\1_', $ps_expression);
		}
		
		$ps_expression = preg_replace("![\-]{1}!", " - ", $ps_expression);
		
		$va_era_list = array_merge(array_keys($this->opo_language_settings->getAssoc("ADBCTable")), array($this->opo_language_settings->get("dateADIndicator"), $this->opo_language_settings->get("dateBCIndicator")));
		foreach($va_era_list as $vs_era) {
			$ps_expression = preg_replace("/([\d]+)".$vs_era."[ ]*/i", "$1 $vs_era ", $ps_expression); #str_replace($vs_era, " ".$vs_era, $ps_expression);
		}
		
		$va_meridian_list = array_merge(array_keys($this->opo_language_settings->getAssoc("meridianTable")), array($this->opo_language_settings->get("timeAMMeridian"), $this->opo_language_settings->get("timePMMeridian")));
		foreach($va_meridian_list as $vs_meridian) {
			$ps_expression = preg_replace("/([\d]+)".$vs_meridian."[ ]*/i", "$1 $vs_meridian ", $ps_expression); #str_replace($vs_meridian, " ".$vs_meridian, $ps_expression);
		}
		
		if (is_array($va_after = $this->opo_language_settings->getList("afterQualifier"))) {
			$vs_primary_after = array_shift($va_after);
			foreach($va_after as $vs_after) {
				$ps_expression = preg_replace("/^{$vs_after}[ ]+/i","{$vs_primary_after} ", $ps_expression);
			}
		}
		
		if (is_array($va_before = $this->opo_language_settings->getList("beforeQualifier"))) {
			$vs_primary_before = array_shift($va_before);
			foreach($va_before as $vs_before) {
				$ps_expression = preg_replace("/^{$vs_before}[ ]+/i","{$vs_primary_before} ", $ps_expression);
			}
		}
		
		if (is_array($va_born = $this->opo_language_settings->getList("bornQualifier"))) {
			$vs_primary_born = array_shift($va_born);
			foreach($va_born as $vs_born) {
				$ps_expression = preg_replace("/^{$vs_born}[ ]+/i","{$vs_primary_born} ", $ps_expression);
			}
		}
		
		if (is_array($va_died = $this->opo_language_settings->getList("diedQualifier"))) {
			$vs_primary_died = array_shift($va_died);
			foreach($va_died as $vs_died) {
				$ps_expression = preg_replace("/^{$vs_died}[ ]+/i","{$vs_primary_died} ", $ps_expression);
			}
		}
	
		$va_conjunction_list = $this->opo_language_settings->getList("rangeConjunctions");
		foreach($va_conjunction_list as $vs_conjunction) {
			if (!preg_match("/^[A-Za-z0-9]+$/", $vs_conjunction)) {		// only add spaces around non-alphanumeric conjunctions
				$ps_expression = str_replace($vs_conjunction, ' '.$vs_conjunction.' ', $ps_expression);
			}
		}
		
		// check for ISO 8601 date/times... if we find one split the time off into a separate token
		$va_datetime_conjunctions = $this->opo_language_settings->getList('dateTimeConjunctions');
		$ps_expression = preg_replace("/([\d]+)T([\d]+)/i", "$1 ".$va_datetime_conjunctions[0]." $2", $ps_expression);
		
		// support year ranges in the form yyyy/yyyy
		$ps_expression = preg_replace("!^([\d]{4})/([\d]{4})$!", "$1 - $2", trim($ps_expression));
		
		return trim($ps_expression);
	}
	# -------------------------------------------------------------------
	# Productions (kinda sorta)
	# -------------------------------------------------------------------
	private function &_parseDateElement($pa_options=null) {
		$vn_state = TEP_STATE_BEGIN_DATE_ELEMENT;
		
		$vn_day = $vn_month = $vn_year = null;
		
		$vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
		while($va_token = $this->peekToken()) {
			switch($vn_state) {
				# -------------------------------------------------------
				case TEP_STATE_BEGIN_DATE_ELEMENT:
					switch($va_token['type']) {
						# ----------------------
						case TEP_TOKEN_DATE:
							$this->skipToken();
							return array('month' => $va_token['month'], 'day' => $va_token['day'], 'year' => $va_token['year']);
							break;
						# ----------------------
						case TEP_TOKEN_TODAY:
						
							break;
						# ----------------------
						case TEP_TOKEN_INTEGER:
							$vn_int = intval($va_token['value']);
							if (($vn_int >= 1000) && ($vn_int <= 9999)) {
								$this->skipToken();
								return array('day' => null, 'month' => null, 'year' => $vn_int);
							} else {
								$va_peek = $this->peekToken(2);
								if ((($vn_int >= 1) && ($vn_int <=31)) && ($va_peek['type'] != TEP_TOKEN_ERA)) {
									$this->skipToken();
									if ($va_peek['type'] == TEP_TOKEN_MERIDIAN) {
										// support time format with single hour integer (eg. 10 am)
										array_unshift($this->opa_tokens, $va_token['value']);
										return false;
									} else {
										$vn_day = $vn_int;
										$vn_state = TEP_STATE_DATE_ELEMENT_GET_MONTH_NEXT;
									}
								} else {
									if ($vn_int == $va_token['value']) {
										$this->skipToken();
										return array('day' => null, 'month' => null, 'year' => $vn_int);
									} else {
										$this->setParseError($va_token, TEP_ERROR_INVALID_DATE);
										return false;
									}
								}
							}
							break;
						# ----------------------
						case TEP_TOKEN_ALPHA_MONTH:
							$vn_month = $va_token['month'];
							$this->skipToken();
							if ($va_peek = $this->peekToken()) {
								if ($va_peek['type'] == TEP_TOKEN_INTEGER) {
									$vn_int = intval($va_peek['value']);
									if (($vn_int >= 1000) && ($vn_int <= 9999)) {
										$vn_year = $vn_int;
										$this->skipToken();
									} else {
										if (($vn_int >= 1) && ($vn_int <=31)) {
											$vn_day = $vn_int;
											$this->skipToken();
											if ($va_peek = $this->peekToken()) {
												if ($va_peek['type'] == TEP_TOKEN_INTEGER) {
													$vn_int = intval($va_peek['value']);
													if (($vn_int >= 1000) && ($vn_int <= 9999)) {
														$vn_year = $vn_int;
														$this->skipToken();
													}
												}
											}
										} 
									}
								}
							}
							return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year);
							break;
						# ----------------------
						default:
							# is not a date expression; return false, but do not post error
							return false;
							break;
						# ----------------------
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_DATE_ELEMENT_GET_MONTH_NEXT:
					switch($va_token['type']) {
						# ----------------------
						case TEP_TOKEN_ALPHA_MONTH:
							$this->skipToken();
							$vn_month = $va_token['month'];
							
							// is a year defined? (it's optional)
							if ($va_peek = $this->peekToken()) {
								if (($va_peek['type'] == TEP_TOKEN_INTEGER) && (intval($va_peek['value']) >= 1000) && (intval($va_peek['value']) <= 9999)) {
									$vn_year = intval($va_peek['value']);
									$this->skipToken();
								}
							}
							
							return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year);
							
							break;
						# ----------------------
						default:
							if (isset($pa_options['start']) && isset($pa_options['start']['month']) && $pa_options['start']['month']) {
								$vn_month = $pa_options['start']['month'];
								$vn_year = intval($va_token['value']);
								$this->skipToken();
								return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year);
							}
							$this->setParseError($va_token, TEP_ERROR_INVALID_DATE);
							return false;
							break;
						# ----------------------
					}
					break;
				# -------------------------------------------------------
			}
		}
		
		if ($vn_day && isset($pa_options['start']) && isset($pa_options['start']['month']) && $pa_options['start']['month']) {
			$vn_month = $pa_options['start']['month'];
			$vn_year = $pa_options['start']['year'];
			$this->skipToken();
			return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year);
		}
		$this->setParseError(null, TEP_ERROR_INVALID_DATE);
		return false;
	}
	# -------------------------------------------------------------------
	private function &_parseDateExpression($pa_options=null) {
		$vn_state = TEP_STATE_BEGIN_DATE_EXPRESSION;
		
		$va_time = array();
		while($va_token = $this->peekToken()) {
			switch($vn_state) {
				# -------------------------------------------------------
				case TEP_STATE_BEGIN_DATE_EXPRESSION:
					if ($va_time = $this->_parseTimeExpression()) {
						$va_date = array(
							'month' => null, 'day' => null, 'year' => null,
							'hours' => $va_time['hours'], 'minutes' =>  $va_time['minutes'], 'seconds' => $va_time['seconds'],
							'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
						);
						return $va_date;
						break;
					} else {
						switch($va_token['type']) {
							# ----------------------
							case TEP_TOKEN_PRESENT:
								$va_date = array(
									'month' => null, 'day' => null, 'year' => TEP_END_OF_UNIVERSE,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_NOW:
								$va_now = getdate();
								$va_date = array(
									'month' => $va_now['mon'], 'day' => $va_now['mday'], 'year' => $va_now['year'],
									'hours' => $va_now['hours'], 'minutes' => $va_now['minutes'], 'seconds' => $va_now['seconds'],
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_YESTERDAY:
								$va_yesterday = getdate(time() - (24 * 60 * 60));
								$va_date = array(
									'month' => $va_yesterday['mon'], 'day' => $va_yesterday['mday'], 'year' => $va_yesterday['year'],
									'hours' => 0, 'minutes' => 0, 'seconds' => 0,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_TODAY:
								$va_today = getdate();
								$va_date = array(
									'month' => $va_today['mon'], 'day' => $va_today['mday'], 'year' => $va_today['year'],
									'hours' => 0, 'minutes' => 0, 'seconds' => 0,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_TOMORROW:
								$va_tomorrow = getdate(time() + (24 * 60 * 60));
								$va_date = array(
									'month' => $va_tomorrow['mon'], 'day' => $va_tomorrow['mday'], 'year' => $va_tomorrow['year'],
									'hours' => 0, 'minutes' => 0, 'seconds' => 0,
									'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							default:
								if ($va_date_element = $this->_parseDateElement($pa_options)) {
									$va_date = array(
										'month' => $va_date_element['month'], 'day' => $va_date_element['day'], 
										'year' => $va_date_element['year'],
										'hours' => null, 'minutes' => null, 'seconds' => null,
										'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => 0
									);
									
									$va_peek = $this->peekToken();
									switch($va_peek['type']) {
										# ----------------------
										case TEP_TOKEN_ERA:
											$vn_state = TEP_STATE_DATE_GET_ERA;
											$va_date['dont_window'] = true;
											break;
										# ----------------------
										case TEP_TOKEN_TIME_CONJUNCTION:
											$vn_state = TEP_STATE_DATE_GET_TIME;
											$this->skipToken();
											break;
										# ----------------------
										case TEP_TOKEN_MARGIN_OF_ERROR:
											$vn_state = TEP_STATE_DATE_GET_UNCERTAINTY;
											$this->skipToken();
											break;
										# ----------------------
										case TEP_TOKEN_TIME:
											$vn_state = TEP_STATE_DATE_GET_TIME;
											break;
										# ----------------------
										case TEP_TOKEN_QUESTION_MARK_UNCERTAINTY:
											$vn_state = TEP_STATE_DATE_SET_UNCERTAINTY;
											break;
										# ----------------------
										default:
											return $va_date;
											break;
										# ----------------------
									}
								} else {
									# is not a date expression; return false but do not post error
									return false; 
								}
								break;
								# ----------------------
						}
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_DATE_SET_UNCERTAINTY:
					$va_date['is_circa'] = 1;
					$this->skipToken();
					$va_peek = $this->peekToken();
						
					switch($va_peek['type']) {
						# ----------------------
						case TEP_TOKEN_ERA:
							$vn_state = TEP_STATE_DATE_GET_ERA;
							break;
						# ----------------------
						case TEP_TOKEN_MARGIN_OF_ERROR:
							$vn_state = TEP_STATE_DATE_GET_UNCERTAINTY;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_TIME_CONJUNCTION:
							$vn_state = TEP_STATE_DATE_GET_TIME;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_TIME:
							$vn_state = TEP_STATE_DATE_GET_TIME;
							break;
						# ----------------------
						default:
							return $va_date;
							break;
						# ----------------------
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_DATE_GET_ERA:
					if ($va_token['era'] == TEP_ERA_BC) {
						$va_date['year'] *= -1;
					}
					$va_date['era'] = $va_token['era'];
					$this->skipToken();
					$va_peek = $this->peekToken();
						
					switch($va_peek['type']) {
						# ----------------------
						case TEP_TOKEN_QUESTION_MARK_UNCERTAINTY:
							$vn_state = TEP_STATE_DATE_SET_UNCERTAINTY;
							break;
						# ----------------------
						case TEP_TOKEN_MARGIN_OF_ERROR:
							$vn_state = TEP_STATE_DATE_GET_UNCERTAINTY;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_TIME_CONJUNCTION:
							$vn_state = TEP_STATE_DATE_GET_TIME;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_TIME:
							$vn_state = TEP_STATE_DATE_GET_TIME;
							break;
						# ----------------------
						default:
							return $va_date;
							break;
						# ----------------------
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_DATE_GET_UNCERTAINTY:
					if ($va_token['type'] == TEP_TOKEN_ALPHA) {
						if (preg_match("/^([\d\.]+)([dmyDMY]{1})$/", $va_token['value'], $va_matches)) {
							if (!is_numeric($va_matches[1])) {
								$this->setParseError($va_token, TEP_ERROR_INVALID_UNCERTAINTY);
								return false;
							} 
							
							$va_date['uncertainty'] = $va_matches[1];
							$va_date['uncertainty_units'] = strtolower($va_matches[2]);
							
							$this->skipToken();
							
							$va_peek = $this->peekToken();
							switch ($va_peek['type']) {
								# ----------------------
								case TEP_TOKEN_TIME_CONJUNCTION:
									$vn_state = TEP_STATE_DATE_GET_TIME;
									$this->skipToken();
									break;
								# ----------------------
								case TEP_TOKEN_TIME:
									$vn_state = TEP_STATE_DATE_GET_TIME;
									break;
								# ----------------------
								default:
									return $va_date;
									break;
								# ----------------------
							}
							
						} else {
							$this->setParseError($va_peek, TEP_ERROR_INVALID_UNCERTAINTY);
							return false;
						}
					} else {
						$this->setParseError($va_peek, TEP_ERROR_INVALID_UNCERTAINTY);
						return false;
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_DATE_GET_TIME:
					if ($va_time = $this->_parseTimeExpression()) {
						$va_date['hours'] = $va_time['hours'];
						$va_date['minutes'] = $va_time['minutes'];
						$va_date['seconds'] = $va_time['seconds'];
						
						return $va_date;
					} else {
						$this->setParseError($va_token, TEP_ERROR_INVALID_TIME);
						return false;
					}
					break;
				# -------------------------------------------------------
				default:
					$this->setParseError($va_token, TEP_ERROR_PARSER_ERROR);
					return false;
					break;
				# -------------------------------------------------------
			}
		}
		
		$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
		return false;
	}
	# -------------------------------------------------------------------
	private function &_parseTimeExpression() {
		$vn_state = TEP_STATE_BEGIN_TIME_EXPRESSION;
		
		$va_time = array();
		while($va_token = $this->peekToken()) {
			switch($vn_state) {
				# -------------------------------------------------------
				case TEP_STATE_BEGIN_TIME_EXPRESSION:
					switch($va_token['type']) {
						# ----------------------
						case TEP_TOKEN_TIME:
							$va_time = array(
								'hours' => $va_token['hours'],
								'minutes' => $va_token['minutes'],
								'seconds' => $va_token['seconds'],
								'uncertainty' => 0, 'uncertainty_units' => ''
							);
							$this->skipToken();
							
							$va_peek = $this->peekToken();
							
							switch($va_peek['type']) {
								# ----------------------
								case TEP_TOKEN_MERIDIAN:
									$vn_state = TEP_STATE_TIME_GET_MERIDIAN;
									break;
								# ----------------------
								case TEP_TOKEN_MARGIN_OF_ERROR:
									$vn_state = TEP_STATE_TIME_GET_UNCERTAINTY;
									break;
								# ----------------------
								default:
									return $va_time;
									break;
								# ----------------------
							}
							break;
						# ----------------------
						case TEP_TOKEN_INTEGER:
							if (
								($va_token['value'] == intval($va_token['value'])) &&
								($va_token['value'] >= 0 && $va_token['value'] <= 23)
							) {
								$this->skipToken();
								$va_peek = $this->peekToken();
								if ($va_peek['type'] == TEP_TOKEN_MERIDIAN) {
									$this->skipToken();
									$vn_hours = intval($va_token['value']);
									if (($va_peek['meridian'] == TEP_MERIDIAN_PM) && ($vn_hours < 12)) {
										$vn_hours += 12;
									}
									$va_time = array(
										'hours' => $vn_hours,
										'minutes' => 0,
										'seconds' => 0,
										'uncertainty' => 0, 'uncertainty_units' => ''
									);
									
									return $va_time;
								} else {
									# is not a time expression; return false but do not set a parse error
									array_unshift($this->opa_tokens, $va_token['value']); # restore first token to token queue
									return false;
								}
							} else {
								# is not a time expression; return false but do not set a parse error
								return false;
							}
							break;
						# ----------------------
						default:
							# is not a time expression; return false but do not set a parse error
							return false;
							break;
						# ----------------------
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_TIME_GET_MERIDIAN:
					if (($va_token['meridian'] == TEP_MERIDIAN_PM) && ($va_time['hours'] < 12)) {
						$va_time['hours'] += 12;
					}
					$this->skipToken();
					
					$va_peek = $this->peekToken();
						
					switch($va_peek['type']) {
						# ----------------------
						case TEP_TOKEN_MARGIN_OF_ERROR:
							$vn_state = TEP_STATE_TIME_GET_UNCERTAINTY;
							break;
						# ----------------------
						default:
							return $va_time;
							break;
						# ----------------------
					}
					break;
				# -------------------------------------------------------
				case TEP_STATE_TIME_GET_UNCERTAINTY:
					$this->skipToken();
					$va_peek = $this->peekToken();
					if ($va_peek['type'] == TEP_TOKEN_ALPHA) {
						if (preg_match("/^([\d\.]+)([hmsHMS]{1})$/", $va_peek['value'], $va_matches)) {
							if (!is_numeric($va_matches[1])) {
								$this->setParseError($va_token, TEP_ERROR_INVALID_UNCERTAINTY);
								return false;
							} 
							
							$va_time['uncertainty'] = $va_matches[1];
							$va_time['uncertainty_units'] = strtolower($va_matches[2]);
							
							$this->skipToken();
							
							return $va_time;
						} else {
							$this->setParseError($va_peek, TEP_ERROR_INVALID_UNCERTAINTY);
							return false;
						}
					} else {
						$this->setParseError($va_peek, TEP_ERROR_INVALID_UNCERTAINTY);
						return false;
					}
					break;
				# -------------------------------------------------------
				default:
					$this->setParseError($va_token, TEP_ERROR_PARSER_ERROR);
					return false;
					break;
				# -------------------------------------------------------
			}
		}
		
		$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
		return false;
	}
	# -------------------------------------------------------------------
	/**
	 * Parses provided token and returns elements of decade expression if present
	 * Advances to just beyond end of decade expression 
	 */
	private function _parseDecade($va_token, $vb_circa_is_set=false) {
		#
		# is this a decade expression?
		#
		$va_decade_indicators = $this->opo_language_settings->getList("decadeIndicator");
	
		$vb_was_peeked = false;
		if ($va_token['type'] == TEP_TOKEN_CIRCA) {
			$vb_circa_is_set = true;
			$va_token = $this->peekToken(2);
			$vb_was_peeked = true;
		}
	
		$va_dates = array();
		if (sizeof($va_decade_indicators)) {
			if (
				(preg_match("/^([\d]{4})[\']{0,1}(".join("|", $va_decade_indicators)."){1}$/i", $va_token['value'], $va_matches))
				||
				(preg_match("/^([\d]{3})\_$/", $va_token['value'], $va_matches))
			) {
				$vn_is_circa = $vb_circa_is_set ? 1 : 0;
				
				if ($vb_was_peeked) { $this->skipToken(); }
				$this->skipToken();
			
				while($va_modfier_token = $this->peekToken()) {
					switch($va_modfier_token['type']) {
						case TEP_TOKEN_ERA:
							if($va_modfier_token['era'] == TEP_ERA_BC) {
								$vn_century *= -1;
							}
							$this->skipToken();
							break;
						case TEP_TOKEN_QUESTION_MARK_UNCERTAINTY:
							$vn_is_circa = 1;
							$this->skipToken();
							break;
						default:
							break(2);
					}
				}
				if (strlen($va_matches[1]) == 3) { $va_matches[1].='0'; }
			
				$vn_start_year = $va_matches[1] - ($va_matches[1] % 10);
				$va_dates['start'] = array(
					'month' => 1, 'day' => 1, 'year' => $vn_start_year,
					'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa
				);
				$va_dates['end'] = array(
					'month' => 12, 'day' => 31, 'year' => $vn_start_year + 9,
					'uncertainty' => 0, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa
				);
			}
		}
		
		return $va_dates;
	}
	# -------------------------------------------------------------------
	# Lexical analysis
	# -------------------------------------------------------------------
	private function tokenize($ps_expression) {
		$this->opa_tokens = preg_split("/[ ]+/", $ps_expression);
		return sizeof($this->opa_tokens);
	}
	# -------------------------------------------------------------------
	private function tokens() {
		return sizeof($this->opa_tokens);
	}
	# -------------------------------------------------------------------
	private function skipToken() {
		return array_shift($this->opa_tokens);
	}
	# -------------------------------------------------------------------
	private function &getToken() {
		if ($this->tokens() == 0) {
			// no more tokens
			return false;
		}
		
		$vs_token = trim(array_shift($this->opa_tokens));
		$vs_token_lc = mb_strtolower($vs_token, 'UTF-8');
		
		// undated
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("undatedDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_UNDATED);
		}
		// today
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("todayDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_TODAY);
		}
		// yesterday
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("yesterdayDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_YESTERDAY);
		}
		// tomorrow
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("tomorrowDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_TOMORROW);
		}
		// now
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("nowDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_NOW);
		}
		
		if ($vs_token_lc == '?') {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_QUESTION_MARK_UNCERTAINTY);
		}
		
		// present
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("presentDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PRESENT);
		}

		// seasons
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("winterSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_WINTER);
		}
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("springSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_SPRING);
		}
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("summerSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_SUMMER);
		}
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("autumnSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_AUTUMN);
		}

		
		
		// text month
		$va_month_table = $this->opo_language_settings->getAssoc("monthTable");
		if ($va_month_table[$vs_token_lc]) {
			$vs_token_lc = $va_month_table[$vs_token_lc];
		}
		$va_month_list = $this->opo_language_settings->getList("monthList");
		if (in_array($vs_token_lc, $va_month_list)) {
			$vn_month = array_search($vs_token_lc, $va_month_list) + 1;
			return array('value' => $vs_token, 'month' => $vn_month, 'type' => TEP_TOKEN_ALPHA_MONTH);
		}
			
		// range pre-conjunction
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("rangePreConjunctions"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PRE_RANGE_CONJUNCTION);
		}
			
		// range conjunction
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("rangeConjunctions"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_RANGE_CONJUNCTION);
		}
			
		// time conjunction
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("dateTimeConjunctions"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_TIME_CONJUNCTION);
		}
		
		// before
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("beforeQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_BEFORE);
		}
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("diedQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_BEFORE);
		}
		
		// after
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("afterQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_AFTER);
		}
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("bornQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_AFTER);
		}
		
		// margin of error indicator
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("dateUncertaintyIndicator"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_MARGIN_OF_ERROR);
		}
		
		// punctuation
		if (in_array($vs_token_lc, array('.',','))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PUNCTUATION);
		}

		// century with ordinalSuffix
		$va_ordinals = $this->opo_language_settings->getList("ordinalSuffixes");
		$va_ordinals[] = $this->opo_language_settings->get("ordinalSuffixDefault");
		foreach($va_ordinals as $vs_ordinal){
			if(substr($vs_token_lc, 0 - strlen($vs_ordinal)) == $vs_ordinal){
				$vn_cent = substr($vs_token_lc,0,strlen($vs_token_lc) - strlen($vs_ordinal));
				if(preg_match("/^\d+$/",$vn_cent)){ // could use is_numeric here but this seems safer
					$va_next_tok = $this->peekToken();
					if($va_next_tok['type'] == TEP_TOKEN_ALPHA){ // must not be TEP_TOKEN_ALPHA_MONTH, as in 28. Januar 1985
						return array('value' => $vs_token, 'type' => TEP_TOKEN_ALPHA);	
					}
				}
			}
		}
		
		// Meridians (AM/PM)
		$va_meridian_lookup = $this->opo_language_settings->getAssoc("meridianTable");
		if ($va_meridian_lookup[$vs_token_lc]) {
			$vs_token_lc = $va_meridian_lookup[$vs_token_lc];
		}
		if ($vs_token_lc == $this->opo_language_settings->get("timeAMMeridian")) {
			return array('value' => $vs_token, 'meridian' => TEP_MERIDIAN_AM, 'type' => TEP_TOKEN_MERIDIAN);
		}
		if ($vs_token_lc == $this->opo_language_settings->get("timePMMeridian")) {
			return array('value' => $vs_token, 'meridian' => TEP_MERIDIAN_PM, 'type' => TEP_TOKEN_MERIDIAN);
		}
		
		// Eras (AD/BC)
		$va_era_lookup = $this->opo_language_settings->getAssoc("ADBCTable");
		if ($va_era_lookup[$vs_token_lc]) {
			$vs_token_lc = $va_era_lookup[$vs_token_lc];
		}
		if ($vs_token_lc == $this->opo_language_settings->get("dateADIndicator")) {
			return array('value' => $vs_token, 'era' => TEP_ERA_AD, 'type' => TEP_TOKEN_ERA);
		}
		if ($vs_token_lc == $this->opo_language_settings->get("dateBCIndicator")) {
			return array('value' => $vs_token, 'era' => TEP_ERA_BC, 'type' => TEP_TOKEN_ERA);
		}
		
		// mya
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("dateMYA"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_MYA);
		}
		
		// circa
		if (in_array($vs_token_lc, $this->opo_language_settings->getList("dateCircaIndicator"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_CIRCA);
		}
		
		// W3C datetime (http://www.w3.org/TR/NOTE-datetime)
		
		if (preg_match("/^([\d]{4})#([\d]{2})$/", $vs_token, $va_matches)) {
			// year-month
			if ((($va_matches[1] >= 1000) && ($va_matches[1] <= 2999)) && (($va_matches[2] >= 1) && ($va_matches[2] <= 12))) {
				return(array('value' => $vs_token, 'month' => $va_matches[2], 'day' => null, 'year' => $va_matches[1], 'type' => TEP_TOKEN_DATE));
			}
		}
		if (preg_match("/^([\d]{4})#([\d]{2})#([\d]{2})$/", $vs_token, $va_matches)) {
			// year-month-day
			if ((($va_matches[1] >= 1000) && ($va_matches[1] <= 2999)) && (($va_matches[2] >= 1) && ($va_matches[2] <= 12)) && (($va_matches[3] >= 1) && ($va_matches[3] <= $this->daysInMonth($va_matches[2], $va_matches[1])))) {
				return(array('value' => $vs_token, 'month' => $va_matches[2], 'day' => $va_matches[3], 'year' => $va_matches[1], 'type' => TEP_TOKEN_DATE));
			}
		}
		
		if (preg_match("/^([\d]{1,2})#([A-Za-z]{3,15})#([\d]{2,4})$/", $vs_token, $va_matches)) {
			// year-month-day (eg 10-Jan-80)
			$vs_m = isset($va_month_table[strtolower($va_matches[2])]) ? $va_month_table[strtolower($va_matches[2])] : strtolower($va_matches[2]);
			$vn_month = array_search($vs_m, $this->opo_language_settings->getList('monthList')) + 1;
			if ((($va_matches[3] >= 0) && ($va_matches[3] <= 2999)) && ($vs_m) && (($va_matches[1] >= 1) && ($va_matches[1] <= $this->daysInMonth($vn_month, $va_matches[3])))) {
				return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $va_matches[1], 'year' => $va_matches[3], 'type' => TEP_TOKEN_DATE));
			}
		}
		
		// date
		$va_date_delimiters = $this->opo_language_settings->getList("dateDelimiters");
		
		$vs_pattern = "![".join('', $va_date_delimiters)."]!";
		$vs_pattern = preg_replace("/[\.]/", "\\.", $vs_pattern);
		$vs_pattern = preg_replace("/[\-]/", "\\-", $vs_pattern);
		$va_tmp = preg_split($vs_pattern, $vs_token);
		
		switch(sizeof($va_tmp)) {
			case 2:
				if(!(is_numeric($va_tmp[0]) && ($va_tmp[0] == intval($va_tmp[0])))) { break; }
				if (preg_match('!^[\?]+$!', $va_tmp[1])) {
					// is month with undefined year
					$vn_month = intval($va_tmp[0]);
					return(array('value' => $vs_token, 'month' => $vn_month, 'day' => null, 'year' => 0, 'type' => TEP_TOKEN_DATE));
				}
				
				if(($va_tmp[0] >= 1) && ($va_tmp[0] <= 12)) {
					$vn_month = intval($va_tmp[0]);
					$vn_year = $vn_day = null;
					
					$va_next_tok = $this->peekToken();
					if ($va_next_tok['type'] == TEP_TOKEN_MERIDIAN) { break; }		// is time
					
					if (is_numeric($va_tmp[1]) && ($va_tmp[1] > 0) && ($va_tmp[1] == intval($va_tmp[1]))) {
						if (($va_tmp[1] >= 1) && ($va_tmp[1] <= $this->daysInMonth($vn_month, 2004))) {		// since year is unspecified we use a leap year
							// got day
							$vn_day = $va_tmp[1];
						} else {
							$vn_year = $va_tmp[1];
						}
						
						return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => $vn_year, 'type' => TEP_TOKEN_DATE));
					}
				} else {
					if (($va_tmp[0] >= 1000) && ($va_tmp[0] <= 9999)) {
						$vn_year = $va_tmp[0];
						$vn_month = $vn_day = null;
						if (($va_tmp[1] >= 1) && ($va_tmp[1] <=12)) {
							$vn_month = $va_tmp[1];
							return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => $vn_year, 'type' => TEP_TOKEN_DATE));
						}
					}
				}
				break;
			case 3:
				if(!(is_numeric($va_tmp[0]) && ($va_tmp[0] == intval($va_tmp[0])))) { break; }
				if(!(is_numeric($va_tmp[1]) && ($va_tmp[1] == intval($va_tmp[1])))) { break; }
				
				if (preg_match('!^[\?]+$!', $va_tmp[2])) { $va_tmp[2] = 0; }
				if(!(is_numeric($va_tmp[2]) && ($va_tmp[2] == intval($va_tmp[2])))) { break; }
				
				$vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
				
				if ($vb_month_comes_first) {
					$vn_month = $va_tmp[0];
					$vn_day = $va_tmp[1];
				} else {
					$vn_month = $va_tmp[1];
					$vn_day = $va_tmp[0];
				}
				$vn_year = $va_tmp[2];
				
				if (($vn_month >= 1) && ($vn_month <= 12)) {
					if (($vn_day >= 1) && ($vn_day <= $this->daysInMonth($vn_month, $vn_year ? $vn_year : 2004))) {
						if ($vn_year > 0) {
							return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => $vn_year, 'type' => TEP_TOKEN_DATE));
						} else {
							if ((int)$vn_year === 0) {		// no year
								return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => 0, 'type' => TEP_TOKEN_DATE));
							}
						}
					}
				} else {
					// hmmm... maybe this is a year-month-day date
					if (($va_tmp[0] >= 1000) && ($va_tmp[0] <= 9999)) {
						$vn_year = (int)$va_tmp[0]; $vn_month = $va_tmp[1]; $vn_day = $va_tmp[2];
						if (($vn_day >= 1) && ($vn_day <= $this->daysInMonth($vn_month, $vn_year ? $vn_year : 2004))) {
							if (($vn_month >= 1) && ($vn_month <= 12)) {
								return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => $vn_year, 'type' => TEP_TOKEN_DATE));
							}
						}
					}
				}
				break;
		}
		
		// time
		$va_time_delimiters = $this->opo_language_settings->getList("timeDelimiters");
		
		$vs_pattern = "![".join('', $va_time_delimiters)."]!";
		$vs_pattern = preg_replace("/[\.]/", "\\.", $vs_pattern);
		$vs_pattern = preg_replace("/[\-]/", "\\-", $vs_pattern);
		
		// try to extract ISO 8601 time zone offset from token
		$vs_time_token = str_replace('Z', '', $vs_token);		// get rid of ISO 8601 UTC indicator
		$vs_iso_8601_offset = '';
		if (preg_match("/([\+\-]{1}[\d]{1,2})\:([\d]{1,2})$/", $vs_time_token, $va_matches)) {
			$vs_time_token = preg_replace("/([\+\-]{1}[\d]{1,2})\:([\d]{1,2})$/", "", $vs_time_token);
			$vs_iso_8601_offset = $va_matches[0];
		}
		$va_tmp = preg_split($vs_pattern, $vs_time_token);
		
		switch(sizeof($va_tmp)) {
			case 2:
				if(is_numeric($va_tmp[0]) && ($va_tmp[0] == intval($va_tmp[0])) && ($va_tmp[0] >= 0) && ($va_tmp[0] <= 23)) {
					$vn_hours = intval($va_tmp[0]);
					$vn_minutes = intval($va_tmp[1]);
					if (is_numeric($va_tmp[1]) && ($va_tmp[1] == intval($va_tmp[1])) && ($va_tmp[1] >=0) && ($va_tmp[1] <= 59)) {
						return(array('value' => $vs_token, 'hours' => $vn_hours, 'minutes' => $vn_minutes, 'seconds' => 0, 'iso8601_tz' => $vs_iso_8601_offset, 'type' => TEP_TOKEN_TIME));
					}
				} 
				break;
			case 3:
			case 4:
				$vn_hours = $va_tmp[0]; $vn_minutes = $va_tmp[1]; $vn_seconds = $va_tmp[2] + (intval($va_tmp[3]) / pow(10, strlen((intval($va_tmp[3])))));
				if (is_numeric($vn_hours) && ($vn_hours == intval($vn_hours)) && ($vn_hours >= 0) && ($vn_hours <= 23)) {
					if (is_numeric($vn_minutes) && ($vn_minutes == intval($vn_minutes)) && ($vn_minutes >= 0) && ($vn_minutes <= 59)) {
						if (is_numeric($vn_seconds) && ($vn_seconds >= 0) && ($vn_seconds <= 59)) {
							return(array('value' => $vs_token, 'hours' => $vn_hours, 'minutes' => $vn_minutes, 'seconds' => $vn_seconds, 'iso8601_tz' => $vs_iso_8601_offset, 'type' => TEP_TOKEN_TIME));
						}
					}
				}
				break;
		}
		
		// number
		if (is_numeric($vs_token)) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_INTEGER);
		}
		
		// string
		return array('value' => $vs_token, 'type' => TEP_TOKEN_ALPHA);
	}
	# -------------------------------------------------------------------
	function &peekToken($vn_n=1) {
		$vn_c = 0;
		
		$va_tokens = array();
		while($vn_c < $vn_n) {
			if ($va_token = $this->getToken()) {
				array_unshift($va_tokens, $va_token);
			}
			$vn_c++;
		}
		foreach($va_tokens as $va_t) {
			array_unshift($this->opa_tokens, $va_t['value']);
		}
		return $va_token;
	}
	# -------------------------------------------------------------------
	# Semantics
	# -------------------------------------------------------------------
	private function _processParseResults($pa_dates, $pa_options) {
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		if (!is_array($pa_dates['end'])) { 
			if (($pa_dates['start']['hours'] == 0) && ($pa_dates['start']['minutes'] == 0) && ($pa_dates['start']['seconds'] == 0)) {
				$pa_dates['end'] = $pa_dates['start'];
				$pa_dates['end']['hours'] = 23; $pa_dates['end']['minutes'] = 59; $pa_dates['end']['seconds'] = 59;
			} else {
				$pa_dates['end'] = $pa_dates['start']; 
			}
		}
		
		if ($pa_dates['start']['is_undated']) {
			$this->opn_start_unixtime = null;
			$this->opn_end_unixtime = null;
			
			$this->opn_start_historic = null;
			$this->opn_end_historic = null;
			
			return true;
		}
		if (!$pa_dates['start']['month'] && !$pa_dates['start']['year']) {
			# time-only expression
			
			if (($pa_options['mode']) && ($pa_options['mode'] != 'time')) {	// don't parse time expressions
				$this->setParseError(null, TEP_ERROR_INVALID_EXPRESSION);
				return false;
			}
			
			$this->opn_start_unixtime = null;
			$this->opn_end_unixtime = null;
			
			$this->opn_start_historic = null;
			$this->opn_end_historic = null;
			
			$vn_start = (intval($pa_dates['start']['hours']) * 3600) + (intval($pa_dates['start']['minutes']) * 60) + intval($pa_dates['start']['seconds']);
			$vn_end = (intval($pa_dates['end']['hours']) * 3600) + (intval($pa_dates['end']['minutes']) * 60) + intval($pa_dates['end']['seconds']);
			
			if ($vn_start > $vn_end) {
				$this->setParseError(null, TEP_ERROR_RANGE_ERROR);
				return false;
			}
			return $this->setTimes($vn_start, $vn_end);
		} else {
			if ($pa_options['mode'] == 'time') {	// only parse time expressions
				$this->setParseError(null, TEP_ERROR_INVALID_EXPRESSION);
				return false;
			}
			if (($pa_options['mode'] == 'date') && ((isset($pa_dates['start']['hours'])) || (isset($pa_dates['end']['hours'])))) {	// don't parse date expressions with time components
				//return $this->setParseError(null, TEP_ERROR_INVALID_EXPRESSION);
			}
		
			# date/time expression
			// Two-digit year windowing
			if (
				(!isset($pa_dates['start']['dont_window']) || !$pa_dates['start']['dont_window'])
				&&
				(!$pa_dates['start']['era'] && ($pa_dates['start']['year'] > 0) && ($pa_dates['start']['year'] <= 99))
			) {
				$va_tmp = getDate();
				$vn_current_year = intval(substr($va_tmp['year'], 2, 2));		// get last two digits of current year
				$vn_current_century = intval(substr($va_tmp['year'], 0, 2)) * 100;
				
				if ($pa_dates['start']['year'] <= $vn_current_year) {
					$pa_dates['start']['year'] += $vn_current_century;
				} else {
					$pa_dates['start']['year'] += ($vn_current_century - 100);
				}
			}
			
			if ((!isset($pa_dates['end']['era']) && ($pa_dates['end']['year'] > 0) && ($pa_dates['end']['year'] <= 99))) {
				$va_tmp = getDate();
				$vn_current_year = intval(substr($va_tmp['year'], 2, 2));		// get last two digits of current year
				$vn_current_century = intval(substr($va_tmp['year'], 0, 2)) * 100;
				if ($pa_dates['end']['year'] <= $vn_current_year) {
					$pa_dates['end']['year'] += $vn_current_century;
				} else {
					$pa_dates['end']['year'] += ($vn_current_century - 100);
				}
			}
			
			# correct for 'present' date in start position; parser always uses TEP_END_OF_UNIVERSE value, but we need TEP_START_OF_UNIVERSE in this case
			if ($pa_dates['start']['year'] == TEP_END_OF_UNIVERSE) {
				$pa_dates['start']['year'] = TEP_START_OF_UNIVERSE;
			}
			
			# if no year is specified on end date then use current year
			if (!$pa_dates['end']['year']) {
				if (!is_null($pa_dates['end']['year'])) {
					$pa_dates['end']['year'] = 0;
				} else {
					$va_current_date = getDate();
					$pa_dates['end']['year'] = $va_current_date['year'];
					if (($pa_dates['end']['month'] === null) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE)) { 
						$pa_dates['end']['month'] = $pa_dates['start']['month']; 
					}	
				}
			}
			

			if (($pa_dates['start']['month'] === null) && ($pa_dates['end']['month'] === null) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE) && ($pa_dates['end']['year'] != TEP_END_OF_UNIVERSE)) { 
				$pa_dates['start']['month'] = 1; 
				$pa_dates['end']['month'] = 12; 
			} else {
				//if (($pa_dates['end']['month'] === null) && ($pa_dates['end']['year'] != TEP_END_OF_UNIVERSE) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE)) { 
				//	$pa_dates['end']['month'] = $pa_dates['start']['month']; 
				//}	
			}
			
			# if no year is specified on the start date, then use the ending year 
			if (!$pa_dates['start']['year']) {
				$pa_dates['start']['year'] = $pa_dates['end']['year'];
				if ($pa_dates['start']['month'] > $pa_dates['end']['month']) {
					$pa_dates['start']['year']--;
				}
			}
			
			if (($pa_dates['start']['day'] === null) && ($pa_dates['end']['day'] === null) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE) && $pa_dates['end']['year'] != TEP_END_OF_UNIVERSE) { 
				$pa_dates['start']['day'] = 1; 
				$pa_dates['end']['day'] = $this->daysInMonth($pa_dates['end']['month'], $pa_dates['end']['year'] ? $pa_dates['end']['year'] : 2004); // use leap year if no year is defined
			} else {
				if (($pa_dates['end']['day'] === null) && ($pa_dates['end']['year'] != TEP_END_OF_UNIVERSE) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE)) { 
					$pa_dates['end']['day'] = $pa_dates['start']['day']; 
				}
			}
			
			if ($pa_dates['end']['month'] === null) { 
				if ($pa_dates['start']['year'] == $pa_dates['end']['year']) {
					$pa_dates['end']['month'] = $pa_dates['start']['month']; 
				} else {
					$pa_dates['end']['month'] = 12;
					$pa_dates['end']['day'] = 31;
				}
			}
			
			if ($pa_dates['start']['hours'] === null) { $pa_dates['start']['hours'] = 0; }
			if ($pa_dates['start']['minutes'] === null) { $pa_dates['start']['minutes'] = 0; }
			if ($pa_dates['start']['seconds'] === null) { $pa_dates['start']['seconds'] = 0; }
			if ($pa_dates['end']['hours'] === null) { $pa_dates['end']['hours'] = 23; }
			if ($pa_dates['end']['minutes'] === null) { $pa_dates['end']['minutes'] = 59; }
			if ($pa_dates['end']['seconds'] === null) { $pa_dates['end']['seconds'] = 59; }
		
			if (
				($pa_dates['start']['year'] >= 1970) && ($pa_dates['end']['year'] >= 1970)
				&&
				($pa_dates['start']['year'] <= 2037) && ($pa_dates['end']['year'] <= 2037)
			) {
				$vn_start_unixtime = mktime(
					$pa_dates['start']['hours'], $pa_dates['start']['minutes'], $pa_dates['start']['seconds'], 
					$pa_dates['start']['month'], $pa_dates['start']['day'], $pa_dates['start']['year']
				);
				$vn_end_unixtime = mktime(
					$pa_dates['end']['hours'], $pa_dates['end']['minutes'], $pa_dates['end']['seconds'], 
					$pa_dates['end']['month'], $pa_dates['end']['day'], $pa_dates['end']['year']
				);
				if ($vn_start_unixtime > $vn_end_unixtime) {
					$this->setParseError(null, TEP_ERROR_RANGE_ERROR);
					return false;
				}
				$this->setUnixTimestamps($vn_start_unixtime, $vn_end_unixtime);
			} else {
				// dates are outside the Unixtime domain
				if (($pa_dates['start']['year'] >= 1970) && ($pa_dates['start']['year'] < 2037)) {
					$vn_start_unixtime = mktime(
						$pa_dates['start']['hours'], $pa_dates['start']['minutes'], $pa_dates['start']['seconds'], 
						$pa_dates['start']['month'], $pa_dates['start']['day'], $pa_dates['start']['year']
					);
					$this->setUnixTimestamps($vn_start_unixtime, (pow(2,32)/2) - 1);
				} else {
					if (($pa_dates['end']['year'] >= 1970) && ($pa_dates['end']['year'] <= 2037)) {
						$vn_end_unixtime = mktime(
							$pa_dates['end']['hours'], $pa_dates['end']['minutes'], $pa_dates['end']['seconds'], 
							$pa_dates['end']['month'], $pa_dates['end']['day'], $pa_dates['end']['year']
						);
						$this->setUnixTimestamps(1, $vn_end_unixtime);
					}
				}
			}
			# create historic timestamps
			# -- encode uncertainty and circa status
		
			# date attribute byte (actually a single digit - 0 to 9 - which mean 3 effective bits)
			# bit 0 indicates whether date is "circa" or not
			# bit 1 & 2 indicate uncertainty units:
			#	00 = no uncertainty
			#	01 = uncertainty is in days
			#	10 = uncertainty is in years
			#
			# If units are not 00, then all digits following it are the uncertainty quantity
			$vn_start_attributes = 0;
			if ($pa_dates['start']['is_circa']) {
				$vn_start_attributes = 1;
			}
			
			$vn_start_uncertainty = '';
			if ($pa_dates['start']['uncertainty'] > 0) {
				switch($pa_dates['start']['uncertainty_units']) {
					case 'd':
						$vn_start_attributes += 2;
						break;
					case 'y':
						$vn_start_attributes += 4;
						break;
				}
				if ($vn_start_attributes > 1) { $vn_start_uncertainty = intval($pa_dates['start']['uncertainty']); }
			}
			
			if (strlen($vn_start_uncertainty) > 9) {
				$this->setParseError(null, TEP_ERROR_INVALID_UNCERTAINTY);
				return false;
			}
			
			$vn_start_historic = $pa_dates['start']['year'].".".sprintf('%02d',$pa_dates['start']['month']).sprintf('%02d',$pa_dates['start']['day']).sprintf('%02d',$pa_dates['start']['hours']).sprintf('%02d',$pa_dates['start']['minutes']).sprintf('%02d',$pa_dates['start']['seconds']).sprintf('%01d', $vn_start_attributes).strlen($vn_start_uncertainty).$vn_start_uncertainty; 
			
			$vn_end_attributes = 0;
			if ($pa_dates['end']['is_circa']) {
				$vn_end_attributes = 1;
			}
			$vn_end_uncertainty = '';
			if ($pa_dates['end']['uncertainty'] > 0) {
				switch($pa_dates['end']['uncertainty_units']) {
					case 'd':
						$vn_end_attributes += 2;
						break;
					case 'y':
						$vn_end_attributes += 4;
						break;
				}
				
				if ($vn_end_attributes > 1) { $vn_end_uncertainty = intval($pa_dates['end']['uncertainty']); }
			}
			
			if (strlen($vn_end_uncertainty) > 9) {
				$this->setParseError(null, TEP_ERROR_UNCERTAINTY_OVERFLOW);
				return false;
			}
			$vn_end_historic = $pa_dates['end']['year'].".".sprintf('%02d',$pa_dates['end']['month']).sprintf('%02d',$pa_dates['end']['day']).sprintf('%02d',$pa_dates['end']['hours']).sprintf('%02d',$pa_dates['end']['minutes']).sprintf('%02d',$pa_dates['end']['seconds']).sprintf('%01d', $vn_end_attributes).strlen($vn_end_uncertainty).$vn_end_uncertainty; 
			
			if (
				(($vn_end_historic > 0) && ($vn_start_historic > $vn_end_historic)) 
			){
				$this->setParseError(null, TEP_ERROR_RANGE_ERROR);
				return false;
			}
			$this->setHistoricTimestamps($vn_start_historic, $vn_end_historic);
		}
		return true;
	}
	# -------------------------------------------------------------------
	# Accessors
	# -------------------------------------------------------------------
	public function getHistoricTimestamps() {
		//if ($this->opn_start_historic == null) {
		//	return false;
		//}
		
		return array(
			0 => $this->opn_start_historic, 1 => $this->opn_end_historic,
			'start' => $this->opn_start_historic, 'end' => $this->opn_end_historic
		);
	}
	# -------------------------------------------------------------------
	public function getUnixTimestamps() {
		if ($this->opn_start_unixtime == null) {
			return false;
		}
		
		return array(
			0 => $this->opn_start_unixtime, 1 => $this->opn_end_unixtime,
			'start' => $this->opn_start_unixtime, 'end' => $this->opn_end_unixtime
		);
	}
	# -------------------------------------------------------------------
	public function getTimes() {
		if ($this->opn_start_time == null) {
			return false;
		}
		return array(
			0 => $this->opn_start_time, 1 => $this->opn_end_time,
			'start' => $this->opn_start_time, 'end' => $this->opn_end_time
		);
	}
	# -------------------------------------------------------------------
	# Options:
	#
	#	timeFormat		(12|24) [default is 24]		=	time format; 12 hour or 24 hour format
	#	timeDelimiter	(string) [default is first delimiter in language config file]	=	Must be a valid time delimiter for the current language or default will be used
	#	timeRangeConjunction (string)	[default is first in lang. config]
	#	timeOmitSeconds (true|false) [default is false]
	#	timeOmit		(true|false) [default is false] if true, no times are displayed
	#
	#	rangePreConjunction (string) [default is none]
	#	rangeConjunction (string) [default is first in lang. config]
	#	dateTimeConjunction (string) [default is first in lang. config]
	#	showADEra (true|false) [default is false]
	#	uncertaintyIndicator (string) [default is first in lang. config]
	#	dateFormat		(text|delimited|iso8601)	[default is text]
	#	dateDelimiter	(string) [default is first delimiter in language config file]
	#	circaIndicator	(string) [default is first indicator in language config file]
	#	beforeQualifier	(string) [default is first indicator in language config file]
	#	afterQualifier	(string) [default is first indicator in language config file]
	#	presentDate		(string) [default is first indicator in language config file]
	#	isLifespan		(true|false) [default is false; if true, date is output with 'born' and 'died' syntax if appropriate]
	#   useQuarterCenturySyntaxForDisplay (true|false) [default is false; if true dates ranging over uniform quarter centuries (eg. 1900 - 1925, 1925 - 1950, 1950 - 1975, 1975-2000) will be output in the format "20 Q1" (eg. 1st quarter of 20th century... 1900 - 1925)
	#   useRomanNumeralsForCenturies (true|false) [default is false; if true century only dates (eg 18th century) will be output in roman numerals like "XVIIIth century"
	#	start_as_iso8601 (true|false) [if true only the start date of the range is returned, in ISO8601 format]
	#	end_as_iso8601 (true|false) [if true only the end date of the range is returned, in ISO8601 format]
	#	startHistoricTimestamp
	#	endHistoricTimestamp 
	public function getText($pa_options=null) {
		if (!$pa_options) { $pa_options = array(); }
		foreach(array(
			'dateFormat', 'dateDelimiter', 'uncertaintyIndicator', 
			'showADEra', 'timeFormat', 'timeDelimiter', 
			'circaIndicator', 'beforeQualifier', 'afterQualifier', 
			'presentDate', 'useQuarterCenturySyntaxForDisplay', 'timeOmit', 'useRomanNumeralsForCenturies', 
			'rangePreConjunction', 'rangeConjunction', 'timeRangeConjunction', 'dateTimeConjunction'
		) as $vs_opt) {
			if (!isset($pa_options[$vs_opt]) && ($vs_opt_val = $this->opo_datetime_settings->get($vs_opt))) {
				$pa_options[$vs_opt] = $vs_opt_val;
			}
		}
		
		if (isset($pa_options['startHistoricTimestamp']) && $pa_options['startHistoricTimestamp']) {
			$va_dates = $this->getHistoricTimestamps();
			return $va_dates['start'];
		}
		if (isset($pa_options['endHistoricTimestamp']) && $pa_options['endHistoricTimestamp']) {
			$va_dates = $this->getHistoricTimestamps();
			return $va_dates['end'];
		}
	
		$va_times = $this->getTimes();
		if ($va_times['start'] != null) {
			//
			// Time-only expression
			//
			$vn_start = $va_times['start'];
			$vn_end = $va_times['end'];
			
			return $this->_timerangeToText($vn_start, $vn_end, $pa_options);
		}
			
		//
		// Date/time expression
		//
		
		// get date and time conjunctions
		$va_range_preconjunctions = $this->opo_language_settings->getList('rangePreConjunctions');
		$va_range_conjunctions = $this->opo_language_settings->getList('rangeConjunctions');
		$va_datetime_conjunctions = $this->opo_language_settings->getList('dateTimeConjunctions');
		
		if (isset($pa_options['rangePreConjunction']) && is_array($va_range_preconjunctions) && in_array($pa_options['rangePreConjunction'], $va_range_preconjunctions)) {
			$vs_range_preconjunction = $pa_options['rangePreConjunction'];
		} else {
			$vs_range_preconjunction = '';
		}
		
		if (isset($pa_options['rangeConjunction']) && is_array($va_range_conjunctions) && in_array($pa_options['rangeConjunction'], $va_range_conjunctions)) {
			$vs_range_conjunction = $pa_options['rangeConjunction'];
		} else {
			$vs_range_conjunction = $va_range_conjunctions[0];
		}
		if (isset($pa_options['dateTimeConjunction']) && is_array($va_datetime_conjunctions) && in_array($pa_options['dateTimeConjunction'], $va_datetime_conjunctions)) {
			$vs_datetime_conjunction = $pa_options['dateTimeConjunction'];
		} else {
			$vs_datetime_conjunction = $va_datetime_conjunctions[0];
		}
	
	
		$va_dates = $this->getHistoricTimestamps();
		
		if (!$va_dates['start']) {
			$va_unix_dates = $this->getUnixTimestamps();
		
			if (($va_unix_dates['start'] != null) && ($va_unix_dates['start'] != -1)) {
				// convert unix timestamps for historic timestamp format for evaluation
				$va_dates = array(
					'start' 	=> $this->unixToHistoricTimestamp($va_unix_dates['start']),
					'end' 		=> $this->unixToHistoricTimestamp($va_unix_dates['end'])
				);
			} 
		}
		
		// is it undated?
		if (($va_dates['start'] === null) && ($va_dates['end'] === null)) {
			if (is_array($va_undated = $this->opo_language_settings->getList('undatedDate'))) {
				return array_shift($va_undated);
			} 
			return "????";
		}
	
		
		// only return times?
		if (isset($pa_options['timeOnly']) && $pa_options['timeOnly']) {
			$va_start_pieces = $this->getHistoricDateParts($va_dates['start']);
			$va_end_pieces = $this->getHistoricDateParts($va_dates['end']);
			$vn_start = ($va_start_pieces['hours'] * 60 * 60) + ($va_start_pieces['minutes'] * 60) + $va_start_pieces['seconds'];
			$vn_end = ($va_end_pieces['hours'] * 60 * 60) + ($va_end_pieces['minutes'] * 60) + $va_end_pieces['seconds'];

			return $this->_timerangeToText($vn_start, $vn_end, $pa_options);
		}
		if (isset($va_dates['start']) && ($va_dates['start'] != null)) {
			
		
			//
			// Date-time expression using historic timestamps
			//
			$va_start_pieces = $this->getHistoricDateParts($va_dates['start']);
			
			$va_end_pieces = $this->getHistoricDateParts($va_dates['end']);
				
			if ($pa_options['start_as_iso8601']) {
				return $this->getISODateTime($va_start_pieces, 'FULL', $pa_options);
			}
			if ($pa_options['end_as_iso8601']) {
				return $this->getISODateTime($va_end_pieces, 'FULL', $pa_options);
			}
			
			
			// start is same as end so just output start date
			if ($va_dates['start'] == $va_dates['end']) {
				if ($pa_options['start_as_iso8601'] || $pa_options['end_as_iso8601']) {
					return $this->getISODateTime($va_start_pieces, 'FULL', $pa_options);
				}
				if ((isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'iso8601'))) { 
					return $this->getISODateTime($va_start_pieces, 'START', $pa_options);
				} else {
					return $this->_dateTimeToText($va_start_pieces, $pa_options);
				}
			}
			
		
			if ($va_start_pieces['year'] == 0) {		// year is not known
				$va_start_pieces['year'] = '????';
				$pa_options['dateFormat'] = 'delimited';		// always output dates with unknown years as delimited as that is the only format that supports them
			}
			if ($va_end_pieces['year'] == 0) {
				$va_end_pieces['year'] = '????';
				$pa_options['dateFormat'] = 'delimited';
			}
			
			if ($va_start_pieces['era'] != $va_end_pieces['era']) {
				$pa_options['showADEra'] = true;
			}
			
			
			if (isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'iso8601')) {
				$vs_start = $this->getISODateTime($va_start_pieces, 'START', $pa_options);
				$vs_end = $this->getISODateTime($va_end_pieces, 'END', $pa_options);
				
				if ($vs_start != $vs_end) {
					return "{$vs_start}/{$vs_end}";
				} else {
					return $vs_start;
				}
			}
			
			if ($pa_options['start_as_na_date']) {
				$vs_date = $va_start_pieces['month'].'-'.$va_start_pieces['day'].'-'.$va_start_pieces['year'];
				
				if (!($va_start_pieces['hours'] == 0 && $va_start_pieces['minutes'] == 0 && $va_start_pieces['seconds'] == 0)) {
					$vs_date .= ' '.$va_start_pieces['hours'].':'.$va_start_pieces['minutes'].':'.$va_start_pieces['seconds'];
				}
				return $vs_date;
			}
			if ($pa_options['end_as_na_date']) {
				$vs_date = $va_end_pieces['month'].'-'.$va_end_pieces['day'].'-'.$va_end_pieces['year'];
				
				if (!($va_end_pieces['hours'] == 23 && $va_end_pieces['minutes'] == 59 && $va_end_pieces['seconds'] == 59)) {
					$vs_date .= ' '.$va_end_pieces['hours'].':'.$va_end_pieces['minutes'].':'.$va_end_pieces['seconds'];
				}
				return $vs_date;
			}
			
			if (
				$va_start_pieces['hours'] == 0 && $va_start_pieces['minutes'] == 0 && $va_start_pieces['seconds'] == 0 &&
				$va_end_pieces['hours'] == 23 && $va_end_pieces['minutes'] == 59 && $va_end_pieces['seconds'] == 59
			) {
				$vb_full_day_time_range = true;
			} else {
				$vb_full_day_time_range = false;
			}
			if (
				($va_start_pieces['uncertainty'] != $va_end_pieces['uncertainty']) 
				|| 
				($va_start_pieces['uncertainty_units'] != $va_end_pieces['uncertainty_units'])
			) {
				$vb_differing_uncertainties = true;
			} else {
				$vb_differing_uncertainties = false;
			}
		
		// catch quarter centuries
		if (
			($pa_options['useQuarterCenturySyntaxForDisplay']) 
			&&
			(((int)$va_start_pieces['year'] > 0) && (!((int)$va_start_pieces['year'] % 25)))
			&& 
			(((int)$va_end_pieces['year'] > 0) && (!((int)$va_end_pieces['year'] % 25)))
			&& 
			(((int)$va_end_pieces['year'] - (int)$va_start_pieces['year']) == 25)
			&& 
			(((int)$va_start_pieces['month']  == 1) && ((int)$va_end_pieces['month'] == 12))
			&& 
			(((int)$va_start_pieces['day']  == 1) && ((int)$va_end_pieces['day'] == 31))
			&& 
			(((int)$va_start_pieces['hours']  == 0) && ((int)$va_end_pieces['hours'] == 23))
			&& 
			(((int)$va_start_pieces['minutes']  == 0) && ((int)$va_end_pieces['minutes'] == 59))
			&& 
			(((int)$va_start_pieces['seconds']  == 0) && ((int)$va_end_pieces['seconds'] == 59))
		) {
			$vn_y = intval(($va_start_pieces['year'] / 100) + 1);
			$vn_q = intval(($va_start_pieces['year'] % 100) / 25) + 1;
			
			if (($vn_y > 0) && ($vn_q > 0)) {
				return "{$vn_y} Q{$vn_q}";
			}
		}
		
		// catch 'present' date
		if (($va_start_pieces['year'] == TEP_START_OF_UNIVERSE) && ($va_end_pieces['year'] == TEP_END_OF_UNIVERSE)) {
			$va_present_date = $this->opo_language_settings->getList('presentDate');
			if (isset($pa_options['presentDate']) && in_array($pa_options['presentDate'], $va_present_date)) {
				$vs_present_date = $pa_options['presentDate'] ;
			} else {
				$vs_present_date = $va_present_date[0];
			}
			return $vs_present_date;
		}
		
		// catch 'before' dates
		if ($va_dates['start'] <= TEP_START_OF_UNIVERSE) {
			$va_died_qualifiers = $this->opo_language_settings->getList('diedQualifier');
			if ($pa_options['isLifespan'] && (sizeof($va_died_qualifiers) > 0)) {
				$vs_before_qualifier = $va_died_qualifiers[0];
			} else {
				$va_before_qualifiers = $this->opo_language_settings->getList('beforeQualifier');
				if ($pa_options['beforeQualifier'] && in_array($pa_options['beforeQualifier'], $va_before_qualifiers)) {
					$vs_before_qualifier = $pa_options['beforeQualifier'] ;
				} else {
					$vs_before_qualifier = $va_before_qualifiers[0];
				}
			}
			
			if ($va_end_pieces['hours'] == 23 && $va_end_pieces['minutes'] == 59 && $va_end_pieces['seconds'] == 59) {
				if ($va_end_pieces['day'] == 31 && $va_end_pieces['month'] == 12) {
					return $vs_before_qualifier.' '. $this->_dateToText(array('year' => $va_end_pieces['year'], 'era' => $va_end_pieces['era'], 'uncertainty' => $va_end_pieces['uncertainty'], 'uncertainty_units' => $va_end_pieces['uncertainty_units']), $pa_options);
				} else {
					return $vs_before_qualifier.' '. $this->_dateToText($va_end_pieces, $pa_options);
				}
			} else {
				return $vs_before_qualifier.' '. $this->_dateTimeToText($va_end_pieces, $pa_options);
			}
		}
		
		// catch 'after' dates
		if ($va_dates['end'] >= TEP_END_OF_UNIVERSE) {
			$va_born_qualifiers = $this->opo_language_settings->getList('bornQualifier');
			if ($pa_options['isLifespan'] && (sizeof($va_born_qualifiers) > 0)) {
				$vs_after_qualifier = $va_born_qualifiers[0];
			} else {
				$va_after_qualifiers = $this->opo_language_settings->getList('afterQualifier');
				if ($pa_options['afterQualifier'] && in_array($pa_options['afterQualifier'], $va_after_qualifiers)) {
					$vs_after_qualifier = $pa_options['afterQualifier'] ;
				} else {
					$vs_after_qualifier = $va_after_qualifiers[0];
				}
			}
			
			if ($va_start_pieces['hours'] == 0 && $va_start_pieces['minutes'] == 0 && $va_start_pieces['seconds'] == 0) {
				if ($va_start_pieces['day'] == 1 && $va_start_pieces['month'] == 1) {
					return $vs_after_qualifier.' '. $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
				} else {
					return $vs_after_qualifier.' '. $this->_dateToText($va_start_pieces, $pa_options);
				}
			} else {
				return $vs_after_qualifier.' '. $this->_dateTimeToText($va_start_pieces, $pa_options);
			}
		}
		
		// catch 'circa' dates
		$vs_circa = '';
		if ($va_start_pieces['is_circa']) {
			$va_circa_indicators = $this->opo_language_settings->getList('dateCircaIndicator');
			if ($pa_options['circaIndicator'] && in_array($pa_options['circaIndicator'], $va_circa_indicators)) {
				$vs_circa = $pa_options['circaIndicator'] ;
			} else {
				$vs_circa = $va_circa_indicators[0];
			}
			$vs_circa .= ' ';
		}
		
			if ($va_start_pieces['year'] == $va_end_pieces['year']) {	
				if ($va_start_pieces['month'] == $va_end_pieces['month']) {		
					if ($va_start_pieces['day'] == $va_end_pieces['day']) {	// dates on same day
						// print date
						$vs_day = $this->_dateToText(array('year' => $va_start_pieces['year'], 'month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day'], 'era' => $va_end_pieces['era'], 'uncertainty' => $va_end_pieces['uncertainty'], 'uncertainty_units' => $va_end_pieces['uncertainty_units']), $pa_options);
						
						if (!$vb_full_day_time_range) {
							$vn_start_time = ($va_start_pieces['hours'] * 3600) + ($va_start_pieces['minutes'] * 60) + $va_start_pieces['seconds'];
							$vn_end_time = ($va_end_pieces['hours'] * 3600) + ($va_end_pieces['minutes'] * 60) + $va_end_pieces['seconds'];
						
							return $vs_circa.$vs_day.' '.$vs_datetime_conjunction.' '.$this->_timerangeToText($vn_start_time, $vn_end_time, $pa_options);
						} 
						
						return $vs_circa.$vs_day;
					} else {													// dates in same month and year
						if ($vb_differing_uncertainties) {
			// dates within same month and year with differing uncertainties
							$vs_start_date = $this->_datetimeToText($va_start_pieces, $pa_options);
							$vs_end_date = $this->_datetimeToText($va_end_pieces, $pa_options);
							return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date;
						} else {
							if (
								$vb_full_day_time_range && 
								$va_start_pieces['day'] == 1 &&
								$va_end_pieces['day'] == $this->daysInMonth($va_end_pieces['month'], $va_end_pieces['year'] ? $va_end_pieces['year'] : 2004)
							) {
			// month and year only
								return $vs_circa.$this->_dateToText(array('month' => $va_start_pieces['month'], 'year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
							} else {
								if ($vb_full_day_time_range) {
			// days, but no times
									$vs_start_date = $this->_dateToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day']), $pa_options);
									$vs_end_date = $this->_dateToText(array('month' => $va_end_pieces['month'], 'day' => $va_end_pieces['day']), $pa_options);
									$vs_year = $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
									return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date.' '.$vs_year;
								} else {
			// days with times
									$vs_start_date = $this->_datetimeToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day'], 'hours' => $va_start_pieces['hours'], 'minutes' => $va_start_pieces['minutes'], 'seconds' => $va_start_pieces['seconds']), $pa_options);
									$vs_end_date = $this->_datetimeToText(array('month' => $va_end_pieces['month'], 'day' => $va_end_pieces['day'], 'year' => $va_end_pieces['year'], 'era' => $va_end_pieces['era'], 'uncertainty' => $va_end_pieces['uncertainty'], 'uncertainty_units' => $va_end_pieces['uncertainty_units'], 'hours' => $va_end_pieces['hours'], 'minutes' => $va_end_pieces['minutes'], 'seconds' => $va_end_pieces['seconds']), $pa_options);
									return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date;					
								}
							}
						}
					}
				} else {	
					if ($vb_differing_uncertainties) {
		// dates within same year with differing uncertainties
						$vs_start_date = $this->_datetimeToText($va_start_pieces, $pa_options);
						$vs_end_date = $this->_datetimeToText($va_end_pieces, $pa_options);
						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date;
					} else {
						if (
							$vb_full_day_time_range &&
							$va_start_pieces['month'] == 1 && $va_start_pieces['day'] == 1 &&
							$va_end_pieces['month'] == 12 && $va_end_pieces['day'] == 31
						) {
		// year only
							return $vs_circa.$this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);	
						} else {
							if ($vb_full_day_time_range) {
		// date range within single year without time
								$vs_start_date = $this->_dateToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day']), $pa_options);
								$vs_end_date = $this->_dateToText(array('month' => $va_end_pieces['month'], 'day' => $va_end_pieces['day']), $pa_options);
								$vs_year = $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
								return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date.' '.$vs_year;
							} else {
		// date range within single year with time
								$vs_start_date = $this->_datetimeToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day'], 'hours' => $va_start_pieces['hours'], 'minutes' => $va_start_pieces['minutes'], 'month' => $va_start_pieces['month'], 'seconds' => $va_start_pieces['seconds']), $pa_options);
								$vs_end_date = $this->_datetimeToText($va_end_pieces, $pa_options);
								return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date;
							}
						}
					}
				}
			} else {															// dates in different years
		// handle multi-year ranges (ie. 1941 to 1945)
				if (
					$vb_full_day_time_range && 
					$va_start_pieces['month'] == 1 && $va_start_pieces['day'] == 1 &&
					$va_end_pieces['month'] == 12 && $va_end_pieces['day'] == 31 
				) {
		// years only
				
			// catch decade dates
					$vs_start_year = $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
					$vs_end_year = $this->_dateToText(array('year' => $va_end_pieces['year'], 'era' => $va_end_pieces['era'], 'uncertainty' => $va_end_pieces['uncertainty'], 'uncertainty_units' => $va_end_pieces['uncertainty_units']), $pa_options);
					if ((($vs_start_year % 10) == 0) && ($vs_end_year == ($vs_start_year + 9))) {
						$va_decade_indicators = $this->opo_language_settings->getList("decadeIndicator");
						if(is_array($va_decade_indicators)){
							$vs_decade_indicator = array_shift($va_decade_indicators);
						} else {
							$vs_decade_indicator = "s";
						}
						return $vs_start_year.$vs_decade_indicator;
					} else {
						// catch century dates
						if ((($va_start_pieces['year'] % 100) == 0) && ($va_end_pieces['year'] == ($va_start_pieces['year'] + 99))) {
							$vn_century = intval($va_start_pieces['year']/100) + 1;
							$va_ordinals = $this->opo_language_settings->getList("ordinalSuffixes");
							
							if (!($vs_ordinal = $va_ordinals[$vn_century])) {
								$vs_ordinal = $this->opo_language_settings->get("ordinalSuffixDefault");
							}
							
							$va_century_indicators = $this->opo_language_settings->getList("centuryIndicator");
							
							$vs_era = ($vn_century < 0) ? ' '.$this->opo_language_settings->get('dateBCIndicator') : '';

							// if useRomanNumeralsForCenturies is set in datetime.conf, 20th Century will be displayed as XXth Century
							if ($pa_options["useRomanNumeralsForCenturies"]) {
								return caArabicRoman(abs($vn_century)).$vs_ordinal.' '.$va_century_indicators[0].$vs_era;
							}
							
							return abs($vn_century).$vs_ordinal.' '.$va_century_indicators[0].$vs_era;
						}
						
						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_circa).$vs_start_year.' '.$vs_range_conjunction.' '.$vs_end_year;
					}
					
				} else {
					if ($vb_full_day_time_range) {
		// full dates with no times
						$vs_start_date = $this->_dateToText($va_start_pieces, $pa_options);
						$vs_end_date = $this->_dateToText($va_end_pieces, $pa_options);
						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date;
					} else {
		// full dates with times
						$vs_start_date = $this->_dateTimeToText($va_start_pieces, $pa_options);
						$vs_end_date = $this->_dateTimeToText($va_end_pieces, $pa_options);
						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date;
					}
				}
			}
		} else {
			return '';
		}
	}
	# -------------------------------------------------------------------
	public function getUncertainty() {
		$va_dates = $this->getHistoricTimestamps();
		$va_start_parts = $this->getHistoricDateParts($va_dates['start']);
		$va_end_parts = $this->getHistoricDateParts($va_dates['end']);
		
		return array(
			'start' => array(
				'uncertainty' => $va_start_parts['uncertainty'],
				'uncertainty_units' => $va_start_parts['uncertainty_units']
			),
			'end' => array(
				'uncertainty' => $va_end_parts['uncertainty'],
				'uncertainty_units' => $va_end_parts['uncertainty_units']
			)
		);
	}
	# -------------------------------------------------------------------
	public function setUnixTimestamps($pn_start, $pn_end) {
		if (($pn_start >= -1) && ($pn_end >= -1) && ($pn_end >= $pn_start)) {
			$this->opn_start_unixtime = $pn_start;
			$this->opn_end_unixtime = $pn_end;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------------------
	public function setHistoricTimestamps($pn_start, $pn_end) {
		if (
				(
					(($pn_start >= 0) && ($pn_start <= $pn_end)) 
					||
					(($pn_end <= 0) && ($pn_start >= $pn_end))
					||
					(($pn_start <= 0) && ($pn_start <= $pn_end))
				) 
				
		){
			$this->opn_start_historic = $pn_start;
			$this->opn_end_historic = $pn_end;
			return true;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------------------
	public function setTimes($pn_start, $pn_end) {
		if (($pn_start >= 0) && ($pn_end >= 0) && ($pn_end >= $pn_start)) {
			$this->opn_start_time = $pn_start;
			$this->opn_end_time = $pn_end;
			return true;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------------------
	# Date/time to text
	# -------------------------------------------------------------------
	# Options:
	#	timeDelimiter
	#	timeFormat
	#
	private function _timeToText($pn_seconds, $pa_options=null) {		// pn_seconds is number of seconds since midnight
		if (!$pa_options) { $pa_options = array(); }
		foreach(array('timeFormat', 'timeFormat') as $vs_opt) {
			if ($vs_opt_val = $this->opo_datetime_settings->get($vs_opt)) {
				$pa_options[$vs_opt] = $vs_opt_val;
			}
		}
		$va_time_delimiters = $this->opo_language_settings->getList('timeDelimiters');
		
		if ($pa_options['timeDelimiter'] && in_array($pa_options['timeDelimiter'], $va_time_delimiters)) {
			$vs_time_delim = $pa_options['timeDelimiter'];
		} else {
			$vs_time_delim = $va_time_delimiters[0];
		}
		
		$vn_hours = floor($pn_seconds/3600);
		$pn_seconds -= ($vn_hours * 3600);
		$vn_minutes = floor($pn_seconds/60);
		$pn_seconds -= ($vn_minutes * 60);
		$vn_seconds = $pn_seconds;
		
		if ($pa_options['timeFormat'] == 12) {
			if ($vn_hours < 12) {
				$vs_meridian = $this->opo_language_settings->get('timeAMMeridian');
			} else {
				$vs_meridian = $this->opo_language_settings->get('timePMMeridian');
				$vn_hours -= 12;
			}
			
			if (($vn_hours == 0) && ($vs_meridian == $this->opo_language_settings->get('timePMMeridian'))) {
				$vn_hours = 12;
			}
			if (($vn_hours == 0) && ($vs_meridian == $this->opo_language_settings->get('timeAMMeridian'))) {
				$vn_hours = 12;
			}
			
			if ($pa_options['timeOmitSeconds'] || ($vn_seconds == 0)) {
				$vs_text = join($vs_time_delim, array($vn_hours, sprintf('%02d', $vn_minutes))).' '.$vs_meridian;		
			} else {
				$vs_text = join($vs_time_delim, array($vn_hours, sprintf('%02d', $vn_minutes), sprintf('%02d', $vn_seconds))).' '.$vs_meridian;
			}
		} else {
			if ($pa_options['timeOmitSeconds'] || ($vn_seconds == 0)) {
				$vs_text = join($vs_time_delim, array($vn_hours, sprintf('%02d', $vn_minutes)));
			} else {
				$vs_text = join($vs_time_delim, array($vn_hours, sprintf('%02d', $vn_minutes), sprintf('%02d', $vn_seconds)));
			}
		}
		return $vs_text;
	}	
	# -------------------------------------------------------------------
	private function _timerangeToText($pn_start, $pn_end, $pa_options=null) { // pn_start and pn_end are both number of seconds since midnight
		if (!$pa_options) { $pa_options = array(); }
		if ($pn_start > $pn_end) { return 'Invalid time range'; }

		$vs_start = $this->_timeToText($pn_start, $pa_options);
		if ($pn_start != $pn_end) {
			$vs_end = $this->_timeToText($pn_end, $pa_options);
			$va_tmp = $this->opo_language_settings->getList("rangeConjunctions");
			
			if (isset($pa_options['timeRangeConjunction']) && (in_array($pa_options['timeRangeConjunction'], $va_tmp))) {
				$vs_timerange_conjunction = $pa_options['timeRangeConjunction'];
			} else {
				$vs_timerange_conjunction = $va_tmp[0];
			}
			return $vs_start.' '.$vs_timerange_conjunction.' '.$vs_end;
		} else {
			return $vs_start;
		}
	}
	# -------------------------------------------------------------------
	private function _dateToText($pa_date_pieces, $pa_options=null) {
		foreach(array('dateFormat', 'dateDelimiter', 'uncertaintyIndicator', 'showADEra') as $vs_opt) {
			if (!isset($pa_options[$vs_opt]) && ($vs_opt_val = $this->opo_datetime_settings->get($vs_opt))) {
				$pa_options[$vs_opt] = $vs_opt_val;
			}
		}
	
		$va_uncertainty_indicators = $this->opo_language_settings->getList("dateUncertaintyIndicator");
		if ($pa_options['uncertaintyIndicator'] && in_array($pa_options['uncertaintyIndicator'], $va_uncertainty_indicators)) {
			$vs_uncertainty_indicator = $pa_options['uncertaintyIndicator'];
		} else {
			$vs_uncertainty_indicator = $va_uncertainty_indicators[0];
		}
		
		$va_date_delimiters = $this->opo_language_settings->getList("dateDelimiters");
		if (isset($pa_options['dateDelimiter']) && in_array($pa_options['dateDelimiter'], $va_date_delimiters)) {
			$vs_date_delimiter = $pa_options['dateDelimiter'];
		} else {
			$vs_date_delimiter = $va_date_delimiters[0];
		}
		
		if ($pa_date_pieces['is_circa']) {
			//$vs_text = ;
		}
		
		$vs_month = null;
		if ($pa_date_pieces['month'] > 0) {		// date with month
			if ($pa_options['dateFormat'] == 'delimited') {
				$vs_month = $pa_date_pieces['month'];
			} else {
				$va_months = $this->getMonthList();
				$vs_month = $va_months[$pa_date_pieces['month'] - 1];
			}
		} 	
		$vs_day = null;
		if ($pa_date_pieces['day'] > 0) {		// date with day
			$vs_day = $pa_date_pieces['day'];
		}	
		
		if ($pa_date_pieces['year']) {
			if ($pa_date_pieces['year'] < 0) {		// date with year
				$vs_year = abs($pa_date_pieces['year']).' '.$pa_date_pieces['era'];
			} else {
				$vs_year = $pa_date_pieces['year'];
				if ($pa_options['showADEra']) {
					$vs_year .= ' '.$pa_date_pieces['era'];
				}
			}
					
			if ($pa_date_pieces['uncertainty_units'] && $pa_date_pieces['uncertainty']) {
				$vs_year .= ' '.$vs_uncertainty_indicator.' '.$pa_date_pieces['uncertainty'].$pa_date_pieces['uncertainty_units'];
			}
		}
		
		$va_date = array();
		
		$vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
		
		if (($vs_day > 0) && ($pa_options['dateFormat'] != 'delimited') && ($vs_day_suffix = $this->opo_language_settings->get('daySuffix'))){
			// add day suffix
			$vs_day .= $vs_day_suffix;
		}
		
		if ($vb_month_comes_first) {
			if ($vs_month) { $va_date[] = (($pa_options['dateFormat'] == 'delimited') ? sprintf("%02d", $vs_month) : $vs_month); }
			if ($vs_day) { 
				if (((bool)$this->opo_datetime_settings->get('showCommaAfterDayForTextDates')) && ($pa_options['dateFormat'] == 'text') && $vs_year) {
					$vs_day .= ",";
				}
				$va_date[] = (($pa_options['dateFormat'] == 'delimited') ? sprintf("%02d", $vs_day) : $vs_day);
			}
		} else {
			if ($vs_day) { $va_date[] = (($pa_options['dateFormat'] == 'delimited') ? sprintf("%02d", $vs_day) : $vs_day); }
			if ($vs_month) { $va_date[] = (($pa_options['dateFormat'] == 'delimited') ? sprintf("%02d", $vs_month)  : $vs_month); }
		}
		if ($vs_year) { $va_date[] = $vs_year; }
		
		if ($pa_options['dateFormat'] == 'delimited') {
			return join($vs_date_delimiter, $va_date);
		} else {
			return join(' ', $va_date);
		}
		
		return null;
	}
	# -------------------------------------------------------------------
	private function _dateTimeToText($pa_date_pieces, $pa_options=null) {
		$va_datetime_conjunctions = $this->opo_language_settings->getList('dateTimeConjunctions');
		if (isset($pa_options['dateTimeConjunction']) && is_array($va_datetime_conjunctions) && in_array($pa_options['dateTimeConjunction'], $va_datetime_conjunctions)) {
			$vs_datetime_conjunction = $pa_options['dateTimeConjunction'];
		} else {
			$vs_datetime_conjunction = $va_datetime_conjunctions[0];
		}
		
		$vs_date = $this->_dateToText($pa_date_pieces, $pa_options);
		
		if (!$pa_options['timeOmit']) {
			$vn_seconds = ($pa_date_pieces['hours'] * 3600) + ($pa_date_pieces['minutes'] * 60) + $pa_date_pieces['seconds'];
			$vs_time = $this->_timeToText($vn_seconds, $pa_options);
			
			return $vs_date. ' '.$vs_datetime_conjunction.' '.$vs_time;
		} else {
			return $vs_date;
		}
	}
	# -------------------------------------------------------------------
	# Language
	# -------------------------------------------------------------------
	public function setLanguage($ps_iso_code) {
		if (file_exists(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser/'.$ps_iso_code.'.lang')) {
			$this->ops_language = $ps_iso_code;
			$this->opo_language_settings = Configuration::load(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser/'.$ps_iso_code.'.lang');
			if (!$this->opo_language_settings->getAssoc('monthTable')) {
				return false;
			} 
			
			return true;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------------------
	public function language() {
		return $this->ops_language;
	}
	# -------------------------------------------------------------------
	/**
	 * Returns a Configuration object with the date/time localization  
	 * settings for the current locale
	 */
	public function getLanguageSettings() {
		return $this->opo_language_settings;
	}
	# -------------------------------------------------------------------
	# Error handling
	# -------------------------------------------------------------------
	private function setParseError($pa_token, $pn_error) {
		if ($pn_error > 0) {
			$this->opn_error = $pn_error;
			if ($this->opa_error_messages[$pn_error]) {
				$this->ops_error = $this->opa_error_messages[$pn_error];
			} else {
				$this->ops_error = 'Unknown error';
			}
			
			if (($this->opb_debug) && $pa_token) {
				$this->ops_error .= " (Error at '".$pa_token['value']."' [".$pa_token['type']."])";
			}
		}
		return true;
	}
	# -------------------------------------------------------------------
	public function clearParseError() {
		$this->opn_error = 0;
		$this->ops_error = "";
	}
	# -------------------------------------------------------------------
	public function getParseError() {
		return $this->opn_error;
	}
	# -------------------------------------------------------------------
	public function getParseErrorMessage() {
		return $this->ops_error;
	}
	# -------------------------------------------------------------------
	# Utilities
	# -------------------------------------------------------------------
	public function daysInMonth($pn_month, $pn_year=null) {
		if (!$pn_year) {
			$va_tmp = getdate();
			$pn_year = $va_tmp['year'];
		} else {
			if (preg_match('!^[\?]+$!', $pn_year)) { $pn_year = 0; }
		}
		return date("t", mktime(0, 0, 0, $pn_month, 1, $pn_year));
	}
	# -------------------------------------------------------------------
	public function getDayList() {
		return $this->opo_language_settings->getList('dayListDisplay');
	}
	# -------------------------------------------------------------------
	public function getMonthList() {
		return $this->opo_language_settings->getList('monthListDisplay');
	}
	# -------------------------------------------------------------------
	public function getMonthName($pn_month) {
		$va_months = $this->getMonthList();
		return $va_months[$pn_month-1];
	}
	# -------------------------------------------------------------------
	public function &getHistoricDateParts($pn_historic_date) {
		$va_tmp = explode('.', $pn_historic_date);
		
		$vn_year = $va_tmp[0];
		if ($vn_year < 0) {
			$vs_era = $this->opo_language_settings->get('dateBCIndicator');
			$vn_abs_year = abs($vn_year);
		} else {
			$vs_era = $this->opo_language_settings->get('dateADIndicator');
			$vn_abs_year = $vn_year;
		}
		$vn_attributes = substr($va_tmp[1], 10, 1);
		
		$vb_is_circa = ($vn_attributes & 0x0001) ? 1 : 0;
		
		$vs_uncertainty_units = ((intval($vn_attributes) >> 1) == 1) ? 'd' : null;
		if (!$vs_uncertainty_units) { 	$vs_uncertainty_units = ((intval($vn_attributes) >> 2) == 1) ? 'y' : null; }
		
		if ($vs_uncertainty_units) {
			$vn_uncertainty_length = intval(substr($va_tmp[1], 11, 1));
			$vn_uncertainty = substr($va_tmp[1], 12, $vn_uncertainty_length);
		} else {
			$vn_uncertainty = null;
		}
		
		$va_parts = array(
			'year' 				=> $vn_year,
			'abs_year'			=> $vn_abs_year,
			'month' 			=> intval(substr($va_tmp[1], 0, 2)),		// don't want this padded with zero
			'day'				=> intval(substr($va_tmp[1], 2, 2)),		// don't want this padded with zero
			'hours'				=> substr($va_tmp[1], 4, 2),
			'minutes'			=> substr($va_tmp[1], 6, 2),
			'seconds'			=> substr($va_tmp[1], 8, 2),
			'era'				=> $vs_era,
			'is_circa'			=> $vb_is_circa,
			'uncertainty'		=> $vn_uncertainty,
			'uncertainty_units'	=> $vs_uncertainty_units
			
		);
		
		return $va_parts;
	}
	# -------------------------------------------------------------------
	public function unixToHistoricTimestamp($pn_unix_timestamp) {
		$va_date_info = getdate($pn_unix_timestamp);
		return $va_date_info['year'].".".sprintf('%02d',$va_date_info['mon']).sprintf('%02d',$va_date_info['mday']).sprintf('%02d',$va_date_info['hours']).sprintf('%02d',$va_date_info['minutes']).sprintf('%02d',$va_date_info['seconds']).'00'; 
	}
	# -------------------------------------------------------------------
	public function setDebug($pn_debug) {
		$this->opb_debug = ($pn_debug) ? true: false;
	}
	# -------------------------------------------------------------------
	public function getISODateTime($pa_date, $ps_mode='START', $pa_options=null) {
		if (!$pa_date['month']) { $pa_date['month'] = ($ps_mode == 'END') ? 12 : 1; }
		if (!$pa_date['day']) { $pa_date['day'] = ($ps_mode == 'END') ? 31 : 1; }
		if ($ps_mode = 'FULL') {
			$vs_date = $pa_date['year'].'-'.sprintf("%02d", $pa_date['month']).'-'.sprintf("%02d", $pa_date['day']);
			
			if (!isset($pa_options['timeOmit']) || !$pa_options['timeOmit']) {
				$vs_date .= 'T'.sprintf("%02d", $pa_date['hours']).':'.sprintf("%02d", $pa_date['minutes']).':'.sprintf("%02d", $pa_date['seconds']).'Z';
			}
			return $vs_date;
		}
		if (
			(!($pa_date['month'] == 1 && $pa_date['day'] == 1 && ($ps_mode == 'START'))) &&
			(!($pa_date['month'] == 12 && $pa_date['day'] == 31 && ($ps_mode == 'END')))
		) {
			$vs_date = $pa_date['year'].'-'.sprintf("%02d", $pa_date['month']).'-'.sprintf("%02d", $pa_date['day']);
		} else {
			$vs_date = $pa_date['year'];
		}
		
		if (!isset($pa_options['timeOmit']) || !$pa_options['timeOmit']) {
			if (
				(!($pa_date['hours'] == 0 && $pa_date['minutes'] == 0 && $pa_date['seconds'] == 0 && ($ps_mode == 'START'))) &&
				(!($pa_date['hours'] == 23 && $pa_date['minutes'] == 59 && $pa_date['seconds'] == 59 && ($ps_mode == 'END')))
			) {
				$vs_date .= 'T'.sprintf("%02d", $pa_date['hours']).':'.sprintf("%02d", $pa_date['minutes']).':'.sprintf("%02d", $pa_date['seconds']).'Z';
			}
		}
		
		return $vs_date;
	}
	# -------------------------------------------------------------------
	public function normalizeDateRange($pn_historic_start, $pn_historic_end, $ps_normalization) {
		$va_values = array();
		switch($ps_normalization) {
			case 'decades':
				$vn_s = intval($pn_historic_start);
				$vn_e = intval($pn_historic_end);
				if ($vn_s <= TEP_START_OF_UNIVERSE) { $vn_s = $vn_e; }
				if ($vn_e >= TEP_END_OF_UNIVERSE) { $vn_e = $vn_s; }
				
				if (($vn_s <= TEP_START_OF_UNIVERSE) || ($vn_e >= TEP_END_OF_UNIVERSE)) { break; }
				
				$vn_s = intval($vn_s/10) * 10;
				$vn_e = intval($vn_e/10) * 10;
				
				$va_decade_indicators = $this->opo_language_settings->getList("decadeIndicator");
				$vs_bc_indicator = $this->opo_language_settings->get("dateBCIndicator");
				if ($vn_s <= $vn_e) {
					for($vn_y=$vn_s; $vn_y <= $vn_e; $vn_y+= 10) {
						if ($vn_y == 0) { continue; }
						
						if ($vn_y < 0) {
							$va_values[(int)$vn_y] = abs($vn_y).$va_decade_indicators[0].' '.$vs_bc_indicator;
						} else {
							$va_values[(int)$vn_y] = $vn_y.$va_decade_indicators[0];
						}
					}
				}
				break;
			case 'centuries':
				$vn_s = intval($pn_historic_start);
				$vn_e = intval($pn_historic_end);
				if ($vn_s <= TEP_START_OF_UNIVERSE) { $vn_s = $vn_e; }
				if ($vn_e >= TEP_END_OF_UNIVERSE) { $vn_e = $vn_s; }
				
				if (($vn_s <= TEP_START_OF_UNIVERSE) || ($vn_e >= TEP_END_OF_UNIVERSE)) { break; }
				
				$vn_s = intval($vn_s/100) * 100;
				$vn_e = intval($vn_e/100) * 100;
				
				if ($vn_s <= $vn_e) {
					$va_century_indicators = 	$this->opo_language_settings->getList("centuryIndicator");
					$va_ordinals = 				$this->opo_language_settings->getList("ordinalSuffixes");
					$vs_ordinal_default = 		$this->opo_language_settings->get("ordinalSuffixDefault");
					$vs_bc_indicator = 			$this->opo_language_settings->get("dateBCIndicator");
					
					for($vn_y=$vn_s; $vn_y <= $vn_e; $vn_y+= 100) {
						
						$vn_century_num = abs(floor($vn_y/100)) + 1;
						if ($vn_century_num == 0)  { continue; }
						$vn_x = substr((string)$vn_century_num, strlen($vn_century_num) - 1, 1); 
						$vs_ordinal_to_display = isset($va_ordinals[$vn_x]) ? $va_ordinals[$vn_x] : $vs_ordinal_default;
						
						$va_values[(int)$vn_y] = ($vn_century_num).$vs_ordinal_to_display.' '.$va_century_indicators[0].((floor($vn_y/100) < 0) ? ' '.$vs_bc_indicator : '');
					}
				}
				break;
			case 'months':
				$vn_s_year = intval($pn_historic_start);
				$va_tmp = explode('.', (string)$pn_historic_start);
				$vn_s_month = intval(substr($va_tmp[1], 0, 2));
				if ($vn_s_month < 1) { $vn_s_month = 1; }
				if ($vn_s_month > 12) { $vn_s_month = 12; }
				
				$vn_e_year = intval($pn_historic_end);
				$va_tmp = explode('.', (string)$pn_historic_end);
				$vn_e_month = intval(substr($va_tmp[1], 0, 2));
				if ($vn_e_month < 1) { $vn_e_month = 1; }
				if ($vn_e_month > 12) { $vn_e_month = 12; }
				
			
				if ($vn_s_year <= TEP_START_OF_UNIVERSE) { $vn_s_year = $vn_e_year; $vn_s_month = 1; }
				if ($vn_e_year >= TEP_END_OF_UNIVERSE) { $vn_e_year = $vn_s_year; $vn_e_month = 12; }
				
				if (($vn_s_year <= TEP_START_OF_UNIVERSE) || ($vn_e_year >= TEP_END_OF_UNIVERSE)) { break; }
				
				if (!$vn_s_year || !$vn_e_year) { break; }
				if ($vn_s_year <= $vn_e_year) {
					if (($vn_s_year == $vn_e_year) && ($vn_s_month > $vn_e_month)) { break; }
					
					$va_month_indicators = 	$this->opo_language_settings->getList("monthListDisplay");
					for($vn_y=$vn_s_year; $vn_y <= $vn_e_year; $vn_y++) {
						if ($vn_y == $vn_s_year) { $vn_start_month = $vn_s_month; } else { $vn_start_month = 1; }
						if ($vn_y == $vn_e_year) { $vn_end_month = $vn_e_month; } else { $vn_end_month = 12; }
						for($vn_m=$vn_start_month; $vn_m <= $vn_end_month; $vn_m++) {
							$vn_m = sprintf("%02d", $vn_m);
							$va_values["{$vn_y}.{$vn_m}"] = $va_month_indicators[($vn_m - 1)].' '.$vn_y;
						}
					}
				}
				break;
			case 'days':
				$vn_s_year = intval($pn_historic_start);
				$va_tmp = explode('.', (string)$pn_historic_start);
				$vn_s_month = intval(substr($va_tmp[1], 0, 2));
				$vn_s_day = intval(substr($va_tmp[1], 2, 4));
				if ($vn_s_month < 1) { $vn_s_month = 1; }
				if ($vn_s_month > 12) { $vn_s_month = 12; }
				if ($vn_s_day < 1) { $vn_s_day = 1; }
				if ($vn_s_day > ($vn_num_days = $this->daysInMonth($vn_s_month, $vn_s_year))) { $vn_s_day = $vn_num_days; }
				
				$vn_e_year = intval($pn_historic_end);
				$va_tmp = explode('.', (string)$pn_historic_end);
				$vn_e_month = intval(substr($va_tmp[1], 0, 2));
				$vn_e_day = intval(substr($va_tmp[1], 2, 4));
				if ($vn_e_month < 1) { $vn_e_month = 1; }
				if ($vn_e_month > 12) { $vn_e_month = 12; }
				if ($vn_e_day < 1) { $vn_s_day = 1; }
				if ($vn_e_day > ($vn_num_days = $this->daysInMonth($vn_e_month, $vn_e_year))) { $vn_e_day = $vn_num_days; }
				
			
				if ($vn_s_year <= TEP_START_OF_UNIVERSE) { $vn_s_year = $vn_e_year; $vn_s_month = 1; }
				if ($vn_e_year >= TEP_END_OF_UNIVERSE) { $vn_e_year = $vn_s_year; $vn_e_month = 12; }
				
				if (($vn_s_year <= TEP_START_OF_UNIVERSE) || ($vn_e_year >= TEP_END_OF_UNIVERSE)) { break; }
				
				if (!$vn_s_year || !$vn_e_year) { break; }
				if ($vn_s_year <= $vn_e_year) {
					if (($vn_s_year == $vn_e_year) && ($vn_s_month > $vn_e_month)) { break; }
					
					$va_month_indicators = 	$this->opo_language_settings->getList("monthListDisplay");
					for($vn_y=$vn_s_year; $vn_y <= $vn_e_year; $vn_y++) {
						if ($vn_y == $vn_s_year) { $vn_start_month = $vn_s_month; } else { $vn_start_month = 1; }
						if ($vn_y == $vn_e_year) { $vn_end_month = $vn_e_month; } else { $vn_end_month = 12; }
						for($vn_m=$vn_start_month; $vn_m <= $vn_end_month; $vn_m++) {
							$vn_num_days = $this->daysInMonth($vn_m, $vn_y);
							for($vn_d = 1; $vn_d < $vn_num_days; $vn_d++) {
								$vn_m = sprintf("%02d", $vn_m);
								$vn_d = sprintf("%02d", $vn_d);
								$va_values["{$vn_y}.{$vn_m}.{$vn_d}"] = $va_month_indicators[($vn_m - 1)].' '.$vn_d.' '.$vn_y;
							}
						}
					}
				}
				break;
			default:
			case 'years':
				$vn_s = intval($pn_historic_start);
				$vn_e = intval($pn_historic_end);
				
				if ($vn_s <= TEP_START_OF_UNIVERSE) { $vn_s = $vn_e; }
				if ($vn_e >= TEP_END_OF_UNIVERSE) { $vn_e = $vn_s; }
				
				if (($vn_s <= TEP_START_OF_UNIVERSE) || ($vn_e >= TEP_END_OF_UNIVERSE)) { break; }
				
				if (!$vn_s || !$vn_e) { break; }
				
				$vs_bc_indicator = 			$this->opo_language_settings->get("dateBCIndicator");
				if ($vn_s <= $vn_e) {
					for($vn_y=$vn_s; $vn_y <= $vn_e; $vn_y++) {
						if ($vn_y == 0) { continue; }
						if ($vn_y < 0) {
							$va_values[(int)$vn_y] = abs($vn_y).' '.$vs_bc_indicator;
						} else {
							$va_values[(int)$vn_y] = $vn_y;
						}
					}
				}
				break;
		}
		
		return $va_values;
	}
 	# -------------------------------------------------------------------
}
?>