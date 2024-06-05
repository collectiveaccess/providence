<?php
/** ---------------------------------------------------------------------
 * app/lib/Parsers/TimeExpressionParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");

/**
 * Constant for expression that will parse as current date/time independent of current locale.
 */
define("__TEP_NOW__", "__NOW__");

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
define("TEP_TOKEN_BP", 27);
define("TEP_TOKEN_PROBABLY", 28);
define("TEP_TOKEN_EARLY", 29);
define("TEP_TOKEN_MID", 30);
define("TEP_TOKEN_LATE", 31);
define("TEP_TOKEN_ACADEMIC_DATE", 32);

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
define('TEP_STATE_PROBABLY', 9);

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
	
	/**
	 * Cached word lists converted to lower case
	 */
	static $s_language_settings_list_cache = array();
	
	/** 
	 * Lengths of early/mid/late qualified intervals for decades and centuries
	 * Use to translate date constructions such as "mid 1920s" or "early 20th century" into start and end years
	 */
	static $early_mid_late_range_intervals = ['century' => 100, 'decade' => 10];
	static $early_mid_late_range_lengths = ['century' => 20, 'decade' => 4];
	
	# -------------------------------------------------------------------
	# Constructor
	# -------------------------------------------------------------------
	public function __construct($ps_expression=null, $ps_iso_code=null, $pb_debug=false) {	
		global $g_ui_locale;
		
		$o_config = Configuration::load();
		$this->opo_datetime_settings = Configuration::load(__CA_CONF_DIR__.'/datetime.conf');
		
		if (!$ps_iso_code) { $ps_iso_code = $g_ui_locale; }
		if (!$ps_iso_code) { $ps_iso_code = $o_config->get('locale_default'); }
		
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
	/**
	 * Parse date/time expression
	 *
	 * @param string $ps_expression 
	 * @param array $pa_options Options include:
	 *		locale = set locale for parse. Locale setting will be set as current locale for subsequent parses. [Default is null]
	 *
	 * @return bool
	 */
	public function parse($ps_expression, $pa_options=null) {
		if ($ps_expression == __TEP_NOW__) {
			$ps_expression = array_shift($this->opo_language_settings->getList("nowDate"));		
		}
		
		if (!$pa_options) { $pa_options = array(); }
		
		if($locale = caGetOption('locale', $pa_options, null)) {
			$this->setLanguage($locale);
		}
		
		$this->init();
		
		if ($this->tokenize($this->preprocess($ps_expression)) == 0) {
			// nothing to parse
			return false;
		}

		$va_dates = array();
		
		$vn_state = TEP_STATE_BEGIN;
		$vb_can_accept = false;
		
		$vb_circa_is_set = $vb_is_probably_set = false;
		$part_of_range_qualifier = null;
		while($va_token = $this->peekToken()) {
			if ($this->getParseError()) { break; }
			switch($vn_state) {
				# -------------------------------------------------------
				case TEP_STATE_BEGIN:
					switch($va_token['type']) {						
						# ----------------------
						case TEP_TOKEN_RANGE_CONJUNCTION:
							$this->getToken();
							if ($va_date = $this->_parseDateExpression()) {
								if (!isset($va_dates['start'])) {
								    $va_dates['start'] = array(
                                        'month' => null, 'day' => null, 
                                        'year' => TEP_START_OF_UNIVERSE,
                                        'hours' => null, 'minutes' => null, 'seconds' => null,
                                        'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
                                    );
                                }
								$va_dates['end'] = $va_date;
								$vn_state = TEP_STATE_ACCEPT;
								$vb_can_accept = true;
								break(2);
							}
							break;
						# ----------------------
						case TEP_TOKEN_ACADEMIC_DATE:
							$this->getToken();
							$va_dates['start'] = array(
								'month' => 7, 'day' => 1, 'year' => $va_token['start'],
								'hours' => null, 'minutes' => null, 'seconds' => null,
								'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
							);
							$va_dates['end'] = array(
								'month' => 6, 'day' => 30, 'year' => $va_token['end'],
								'hours' => null, 'minutes' => null, 'seconds' => null,
								'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
							);		
							$vn_state = TEP_STATE_DATE_RANGE_CONJUNCTION;
							$vb_can_accept = true;
							break(2);
						# ----------------------
						case TEP_TOKEN_INTEGER:
							if((strlen($va_token['value']) === 8) && preg_match("!^[\d]+$!", $va_token['value'])) {
								// is this an 8-digit compacted ISO date?
								$year = (int)substr($va_token['value'], 0, 4);
								$month = (int)substr($va_token['value'], 4, 2);
								$day = (int)substr($va_token['value'], 6, 2);
								
								if(($month >= 1) && ($month <=12) && ($day >= 1) && ($day <= $this->daysInMonth($month, $year))) {
									$va_dates['start'] = array(
										'month' => $month, 'day' => $day, 'year' => $year, 'era' => TEP_ERA_AD,
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
									);
									$va_dates['end'] = array(
										'month' => $month, 'day' => $day, 'year' => $year, 'era' => TEP_ERA_AD,
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
									);
									
									$this->skipToken();
									$vn_state = TEP_STATE_ACCEPT;
									$vb_can_accept = true;
									break(2);
								} 
							} elseif (((int)$va_token['value'] > 0) && ((int)$va_token['value'] <= 21)) {
								// is this a quarter century expression?
								$va_peek = $this->peekToken(2);
								if ($va_peek['type'] == TEP_TOKEN_ALPHA) {
									if (preg_match('!^Q([\d]{1})$!i', $va_peek['value'], $va_matches)) {
										$vn_q = (int)$va_matches[1];
										if (($vn_q >= 1) && ($vn_q <= 4)) {
											$vn_start_year = (((int)$va_token['value'] -1) * 100) + (($vn_q - 1) * 25);
											$vn_end_year = (((int)$va_token['value'] -1) * 100) + (($vn_q) * 25);
											$va_dates['start'] = array(
												'month' => 1, 'day' => 1, 'year' => $vn_start_year, 'era' => TEP_ERA_AD,
												'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
											);
											$va_dates['end'] = array(
												'month' => 12, 'day' => 31, 'year' => $vn_end_year, 'era' => TEP_ERA_AD,
												'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
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
							if(!is_array($va_peek = $this->peekToken(2))) { break; }
							if ($va_peek['type'] == TEP_TOKEN_MYA) {
								$va_dates['start'] = array(
									'month' => 1, 'day' => 1, 'year' => intval($va_token['value']) * -1000000,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
								);
								$va_dates['end'] = array(
									'month' => 12, 'day' => 31, 'year' => intval($va_token['value']) * -1000000,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
								);
								$this->skipToken();
								$this->skipToken();
							
								$vn_state = TEP_STATE_DATE_RANGE_CONJUNCTION;
								$vb_can_accept = true;
								break(2);
							} elseif ($va_peek['type'] == TEP_TOKEN_BP) {
								$va_dates['start'] = array(
									'month' => 1, 'day' => 1, 'year' => 1950 - intval($va_token['value']),
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true, 'is_bp' => true
								);
								$va_dates['end'] = array(
									'month' => 12, 'day' => 31, 'year' => 1950 - intval($va_token['value']),
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true, 'is_bp' => true
								);
								$this->skipToken();
								$this->skipToken();
						
								$vn_state = TEP_STATE_DATE_RANGE_CONJUNCTION;
								$vb_can_accept = true;
								break(2);
							}
							break;
						# ----------------------
					}
					
					if ($va_date = $this->_parseDateExpression()) {
						if(!isset($va_dates['start'])) { 
							$va_dates['start'] = $va_date; 
						} elseif(!$this->tokens()) {
							$va_dates['end'] = $va_date; 
						}
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
									$va_today = $this->gmgetdate();
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
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
								);
								$va_dates['end'] = array(
									'month' => $vn_end_month, 'day' => 20, 'year' => $vn_start_year + $vn_year_offset,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
								);
								$vn_state = TEP_STATE_ACCEPT;
								$vb_can_accept = true;
								break;
							
							# ----------------------
							case TEP_TOKEN_UNDATED:
								$va_dates['start']  = array(
									'month' => null, 'day' => null, 'year' => null,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'is_undated' => true
								);
								$va_dates['end']  = array(
									'month' => null, 'day' => null, 'year' => null,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'is_undated' => true
								);
								
								$this->skipToken();
								$vn_state = TEP_STATE_ACCEPT;
								$vb_can_accept = true;
								
								break;
							# ----------------------
							case TEP_TOKEN_EARLY:
							case TEP_TOKEN_MID:
							case TEP_TOKEN_LATE:
								$this->skipToken();
								$part_of_range_qualifier = $va_token['type'];
								break;
							# ----------------------
							case TEP_TOKEN_ALPHA:
								#
								# is this a decade expression?
								#
								$vb_is_range = false;
								$va_decade_dates = $this->_parseDecade($va_token, $vb_circa_is_set);
								
								if (sizeof($va_decade_dates) > 0) { // found decade
									$va_next_token = $this->peekToken();
									if (is_array($va_next_token) && ($va_next_token['type'] == TEP_TOKEN_RANGE_CONJUNCTION)) { // decade is part of range
										$va_decade_dates['start']['year'] = self::applyPartOfRangeQualifier($part_of_range_qualifier, 'start', 'decade', $va_decade_dates['start']['year']);
                                        $va_dates['start'] = $va_decade_dates['start'];
										$vn_state = TEP_STATE_BEGIN;
										$this->skipToken();	// skip range conjunction
										$vb_is_range = true;
										break;
									} else {
										$va_decade_dates['start']['year'] = self::applyPartOfRangeQualifier($part_of_range_qualifier, 'start', 'decade', $va_decade_dates['start']['year']);
                                        $va_decade_dates['end']['year'] = self::applyPartOfRangeQualifier($part_of_range_qualifier, 'end', 'decade', $va_decade_dates['end']['year']);
                                       
                                       	if (!isset($va_dates['start'])) { $va_dates['start'] = $va_decade_dates['start']; }
										$va_dates['end'] = $va_decade_dates['end'];
										$vn_state = TEP_STATE_ACCEPT;
										$vb_can_accept = true;
										break;
									}
								}
								
								#
								# is this a century expression?
								#
								if (is_array($d = $this->_parseCentury($va_token, $part_of_range_qualifier))) {
									$vb_can_accept = true;
									if (!isset($va_dates['start'])) { 
										$va_dates = $d;
									} else {
										$va_dates['end'] = $d['end'];
									}
									break;
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
													'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
												);
												$va_dates['end'] = array(
													'month' => 12, 'day' => 31, 'year' => intval($va_token['value']) * -1000000,
													'hours' => null, 'minutes' => null, 'seconds' => null,
													'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'is_bp' => true
												);
												$vb_can_accept = true;
												
												break;
											} elseif ($va_token_mya['type'] == TEP_TOKEN_BP) {
												$va_dates['start'] = array(
													'month' => null, 'day' => null, 'year' => 1950 - intval($va_token['value']),
													'hours' => null, 'minutes' => null, 'seconds' => null,
													'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false
												);
												$va_dates['end'] = array(
													'month' => 12, 'day' => 31, 'year' => 1950 - intval($va_token['value']),
													'hours' => null, 'minutes' => null, 'seconds' => null,
													'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'is_bp' => true
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
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => true, 'is_probably' => false
									);
									$va_dates['end'] = array(
										'month' => $va_date_element['month'], 'day' => $va_date_element['day'], 
										'year' => $va_date_element['year'],
										'hours' => null, 'minutes' => null, 'seconds' => null,
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => true, 'is_probably' => false
									);
									
									$vn_state = TEP_STATE_DATE_RANGE_CONJUNCTION;
									$vb_can_accept = true;
								}
								break;
							# ----------------------
							case TEP_TOKEN_PROBABLY:
								$vb_probably_is_set = true;
								
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
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => true
									);
									$va_dates['end'] = array(
										'month' => $va_date_element['month'], 'day' => $va_date_element['day'], 
										'year' => $va_date_element['year'],
										'hours' => null, 'minutes' => null, 'seconds' => null,
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => true
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
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
					);
					if(!isset($va_date['hours'])) { $va_date['hours'] = 23; }
					if(!isset($va_date['minutes'])) { $va_date['minutes'] = 59; }
					if(!isset($va_date['seconds'])) { $va_date['seconds'] = 59; }
					
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
					if(!$va_date['month']) { $va_date['month'] = 1; }
					if (!$va_date['day']) { $va_date['day'] = 1; }
					if(!isset($va_date['hours'])) { $va_date['hours'] = 0; }
					if(!isset($va_date['minutes'])) { $va_date['minutes'] = 0; }
					if(!isset($va_date['seconds'])) { $va_date['seconds'] = 0; }
					
					$va_dates['start'] = $va_date;
					$va_dates['end'] = array(
						'month' => null, 'day' => null, 
						'year' => TEP_END_OF_UNIVERSE,
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
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
				$vb_circa_is_set = $vb_probably_is_set = false;
				if ($va_token['type'] == TEP_TOKEN_RANGE_CONJUNCTION) {
					$this->skipToken();
					
					$vn_state = TEP_STATE_DATE_RANGE_END_DATE;
				} else {
					$this->setParseError($va_token, TEP_ERROR_INVALID_EXPRESSION);
				}
				$vb_can_accept = false;
				break;
			# -------------------------------------------------------
			case TEP_STATE_DATE_RANGE_END_DATE:
				//
				// Look for MYA dates
				//
				$va_peek = $this->peekToken(2);
				if ($va_peek && ($va_peek['type'] == TEP_TOKEN_MYA)) {
					$va_dates['end'] = array(
						'month' => 12, 'day' => 31, 'year' => intval($va_token['value']) * -1000000,
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
					);
					$this->skipToken();
					$this->skipToken();
				
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
					break;
				} elseif ($va_peek && ($va_peek['type'] == TEP_TOKEN_BP)) {
					$va_dates['end'] = array(
						'month' => 12, 'day' => 31, 'year' => 1950 - intval($va_token['value']),
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true, 'is_bp' => true
					);
					$this->skipToken();
					$this->skipToken();
			
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
					break;
				} elseif($va_token['type'] == TEP_TOKEN_ACADEMIC_DATE) {
					//$this->getToken();
					$va_dates['end'] = array(
						'month' => 6, 'day' => 30, 'year' => $va_token['end'],
						'hours' => null, 'minutes' => null, 'seconds' => null,
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false, 'dont_window' => true
					);		
					$this->skipToken();
			
					$vn_state = TEP_STATE_ACCEPT;
					$vb_can_accept = true;
					break;
				}
				
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
					} elseif (isset($va_dates['start']['is_probably']) && $va_dates['start']['is_probably']) {
						$va_dates['end']['is_probably'] = true;
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
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
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
		// Trigger TimeExpressionParser preprocess hook
		$o_app_plugin_manager = new ApplicationPluginManager();
		$va_hook_result = $o_app_plugin_manager->hookTimeExpressionParserPreprocessBefore(array("expression"=>$ps_expression));
		if ($va_hook_result["expression"] != $ps_expression) {
			$ps_expression = $va_hook_result["expression"];
		}
		
		
		// Transform <year>c (Ex. 1950c) into circa date
		$circa_indicators = $this->getLanguageSettingsWordList("dateCircaIndicator");
		$ps_expression = preg_replace('!([\d]{3,})[Cc]{1}!', $circa_indicators[0]." $1", $ps_expression);
		
		// Convert ISO ranges
		if (preg_match("!^([\d\-:TZ]{3,20})/([\d\-:TZ]{3,20})$!", trim($ps_expression), $matches)) {
			$conjunction = array_shift($this->opo_language_settings->getList("rangeConjunctions"));
			$ps_expression = $matches[1]." {$conjunction} ".$matches[2];
		}
	
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
		
		// Convert 19th-century to 19th century
		if (is_array($va_century = $this->opo_language_settings->getList("centuryIndicator"))) {
			foreach($va_century as $vs_century) {
				$ps_expression = preg_replace("/[\-—]+{$vs_century}/i", " {$vs_century}", $ps_expression);
			}
		}
		
		// Convert mid-19th century to mid 19th century
		if (!is_array($early = $this->opo_language_settings->getList("earlyQualifier"))) { $early = []; }
		if (!is_array($mid = $this->opo_language_settings->getList("midQualifier"))) { $mid = []; }
		if (!is_array($late = $this->opo_language_settings->getList("lateQualifier"))) { $late = []; }
		foreach(array_merge($early, $mid, $late) as $q) {
			$ps_expression = preg_replace("/{$q}[\-—]+/i", "{$q} ", $ps_expression);
		}
		#replace time keywords containing spaces with conf defined replacement, allowing treatments for expression like "av. J.-C." in french
		if(!is_array($wordsWithSpaces = $this->opo_language_settings->getList("wordsWithSpaces"))) { $wordsWithSpaces = []; }
		if (!is_array($wordsWithSpacesReplacements = $this->opo_language_settings->getList("wordsWithSpacesReplacements"))) { $wordsWithSpacesReplacements = []; }
		if ((sizeof($wordsWithSpaces)) && (sizeof($wordsWithSpacesReplacements))) {
			$ps_expression=str_replace($wordsWithSpaces,$wordsWithSpacesReplacements,$ps_expression);
		}
		
		# separate '?' from words
		$ps_expression = preg_replace('!([^\?\/]+)\?{1}([^\?]+)!', '\1 ? \2', $ps_expression);
		$ps_expression = preg_replace('!([^\?\/]+)\?{1}$!', '\1 ?', $ps_expression);

		# make sure all leading keywords have trailing spaces. Eg. c.1959 => c. 1959
		foreach(['dateCircaIndicator', 'beforeQualifier', 'afterQualifier'] as $l) {
			if($keywords = $this->getLanguageSettingsWordList($l)) {
				usort($keywords, function($a, $b) {
					return strlen($b) - strlen($a);
				});
				foreach($keywords as $c) {
					$ps_expression = preg_replace('!(?<=^|[\b]{1})('.preg_quote($c, '!').')[-–]+!i', "$1 ", $ps_expression);
					if (!preg_match('!(^|[^A-Za-z]+)'.preg_quote($c, '!').'([\-\d]+)!i', $ps_expression, $m)) { continue; }
					$ps_expression = preg_replace('!(^|[^A-Za-z]+)'.preg_quote($c, '!').'([\-]*)!i', "$1 {$c} $2", $ps_expression);
					break;
				}
			}
		}
		
		# Remove UTC offset if present
		$ps_expression = preg_replace("/(T[\d]{1,2}:[\d]{2}:[\d]{2})-[\d]{1,2}:[\d]{2}/i", "$1", $ps_expression);
		
		# distinguish w3cdtf dates since we already use '-' for ranges
		$ps_expression = preg_replace("/([\d]{4})-([\d]{2})-([\d]{2})/", "$1#$2#$3", $ps_expression);
		
		# distinguish w3cdtf dates since we already use '-' for ranges
		$ps_expression = preg_replace("/([\d]{4})-([\d]{2})([^\d\-\/\.]+)/", "$1#$2$3", $ps_expression);
		
		# distinguish dd-MMM-yy and dd-MMM-yyyy dates since we already use '-' for ranges (ex. 10-JUN-80 or 10-JUN-1980)
		$ps_expression = preg_replace("/([\d]{1,2})-([A-Za-z]{3,15})-([\d]{2,4})/", "$1#$2#$3", $ps_expression);
		
		# convert dd-mm-yyyy dates to dd/mm/yyyy to prevent our range conjunction code below doesn't mangle it
		$ps_expression = preg_replace("/([\d]{1,2})-([\d]{1,2})-([\d]{4})/", "$1/$2/$3", $ps_expression);
		$ps_expression = preg_replace("/([\d]{2})-([\d]{2})-([\d]{2})/", "$1/$2/$3", $ps_expression);
		
		if (preg_match("/([\d]{4})-([\d]{2})(\/|$)/", $ps_expression, $va_matches)) {
			if (intval($va_matches[2]) > 12) {
				$ps_expression = preg_replace("/([\d]{4})-([\d]{2})(\/|$)/", "$1-".substr($va_matches[1], 0, 2)."$2$3", $ps_expression);
			} else {
				$ps_expression = preg_replace("/(?<![\/#\-])([\d]{4})-([\d]{2})(\/|$)/", "$1#$2$3", $ps_expression);
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
		
		if (preg_match("!([\d]+)[ ]*[\-]{1}[ ]*([\d]+)!", $ps_expression, $m)) {	
			$ps_expression = preg_replace("!([\d]+)[ ]*[\-]{1}[ ]*([\d]+)!", "$1 - $2", $ps_expression);
		}
		
		if (!preg_match("!^[\-]{1}[\d]+$!", $ps_expression)) {			
			$ps_expression = preg_replace("!([A-Za-z]+)([\-\–\—]+)!", "$1 - ", $ps_expression);
			$ps_expression = preg_replace("!([\-\–\—]+)([A-Za-z]+)!", " - $2", $ps_expression);
		}
		
		// Handle ?-<date> (Ex. ?-1948)
		if (preg_match("!^\?[\-–]{1}!", $ps_expression)) {			
			$ps_expression = preg_replace("!^\?[\-–]{1}!", "? - ", $ps_expression);
		}
		
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
			if (!preg_match("/^[A-Za-z0-9\-]+$/", $vs_conjunction)) {		// only add spaces around non-alphanumeric conjunctions
				$ps_expression = str_replace($vs_conjunction, ' '.$vs_conjunction.' ', $ps_expression);
			}
		}
		
		// check for ISO 8601 date/times... if we find one split the time off into a separate token
		$va_datetime_conjunctions = $this->opo_language_settings->getList('dateTimeConjunctions');
		$ps_expression = preg_replace("/([\d]+)T([\d]+)/i", "$1 ".$va_datetime_conjunctions[0]." $2", $ps_expression);
		
		// support year ranges in the form yyyy/yyyy
		$ps_expression = preg_replace("!^([\d]{4})/([\d]{4})$!", "$1 - $2", trim($ps_expression));

		// support date entry in the form yyyy-mm-dd/yyy-mm-dd (HSP)
		$ps_expression = preg_replace("/([\d]{4}#[\d]{2}#[\d]{2})\/([\d]{4}#[\d]{2}#[\d]{2})/", "$1 - $2", $ps_expression);

		// Trigger TimeExpressionParser preprocess hook
		$va_hook_result = $o_app_plugin_manager->hookTimeExpressionParserPreprocessAfter(array("expression"=>$ps_expression));
		if ($va_hook_result["expression"] != $ps_expression) {
			$ps_expression = $va_hook_result["expression"];
		}

		return trim($ps_expression);
	}
	# -------------------------------------------------------------------
	# Productions (kinda sorta)
	# -------------------------------------------------------------------
	private function &_parseDateElement($pa_options=null) {
		$vn_state = TEP_STATE_BEGIN_DATE_ELEMENT;
		
		$vn_day = $vn_month = $vn_year = null;
		
	    if (is_null($vb_month_comes_first = $this->opo_datetime_settings->get('monthComesFirstInDelimitedDate'))) {
		    $vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
	    }
	    
		$vb_is_circa =  $vb_is_probably = false;
		while($va_token = $this->peekToken()) {
			switch($vn_state) {
				# -------------------------------------------------------
				case TEP_STATE_BEGIN_DATE_ELEMENT:
					switch($va_token['type']) {
						# ----------------------
						case TEP_TOKEN_CIRCA:
							$vb_is_circa = true;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_PROBABLY:
							$vb_is_probably = true;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_PROBABLY:
							$vb_is_probablty = true;
							$this->skipToken();
							break;
						# ----------------------
						case TEP_TOKEN_DATE:
							$this->skipToken();
							return array('month' => $va_token['month'], 'day' => $va_token['day'], 'year' => $va_token['year'], 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
							break;
						# ----------------------
						case TEP_TOKEN_TODAY:
						
							break;
						# ----------------------
						case TEP_TOKEN_INTEGER:
							$vn_int = intval($va_token['value']);
							if (($vn_int >= 1000) && ($vn_int <= 9999)) {
								$this->skipToken();
								return array('day' => null, 'month' => null, 'year' => $vn_int, 'era'=> TEP_ERA_AD, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
							} elseif(($vn_int < 0) && is_int($vn_int)) {
								$this->skipToken();
								return array('day' => null, 'month' => null, 'year' => $vn_int, 'era'=> TEP_ERA_BC, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
							} else {
								$va_peek = $this->peekToken(2);
								if (
									(
										(($vn_int >= 1) && ($vn_int <=31)) && 
										($va_peek['type'] != TEP_TOKEN_ERA)
									)
								) {
									$this->skipToken();
									if ($va_peek['type'] == TEP_TOKEN_MERIDIAN) {
										// support time format with single hour integer (eg. 10 am)
										array_unshift($this->opa_tokens, $va_token['value']);
										return false;
									} else {
										$vn_day = $vn_int;
										$vn_state = TEP_STATE_DATE_ELEMENT_GET_MONTH_NEXT;
									}
									
									// No more tokens? treat it as a year after all
									if(sizeof($this->getTokensToConjunction()) === 0) {
										return array('day' => null, 'month' => null, 'year' => $vn_int, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
									}
								} else {
									if ($vn_int == $va_token['value']) {
										$this->skipToken();
										return array('day' => null, 'month' => null, 'year' => $vn_int, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
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
									// we need support for "May 31, 1990" so we can't check $vn_int > 1 here
									if (($vn_int > 31) && ($vn_int <= 9999)) {
										$vn_year = $vn_int;
										$this->skipToken();
									} else {
										if (($vn_int >= 1) && ($vn_int <= 31)) {
											$vn_day = $vn_int;
											$this->skipToken();
											if ($va_peek = $this->peekToken()) {
												if ($va_peek['type'] == TEP_TOKEN_INTEGER) {
													$vn_int = intval($va_peek['value']);
													if (($vn_int >= 1) && ($vn_int <= 9999)) {
														$vn_year = $vn_int;
														$this->skipToken();
													}
												}
											}
										} 
									}
								}
							}
							return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
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
								if (($va_peek['type'] == TEP_TOKEN_INTEGER) && (intval($va_peek['value']) >= 1) && (intval($va_peek['value']) <= 9999)) {
									$vn_year = intval($va_peek['value']);
									$this->skipToken();
								}
							}
							
							return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
							
							break;
						# ----------------------
						case TEP_TOKEN_RANGE_CONJUNCTION:
							# assume month will be set by ending expression
							return array('day' => $vn_day, 'month' => null, 'year' => null, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
							break;
						# ----------------------
						default:
							if (isset($pa_options['start']) && isset($pa_options['start']['month']) && $pa_options['start']['month']) {
								$vn_month = $pa_options['start']['month'];
								$vn_year = intval($va_token['value']);
								$this->skipToken();
								return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
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
		
		if ($vn_day && isset($pa_options['start']) && is_null($pa_options['start']['year'] ?? null) && is_null($pa_options['start']['month'] ?? null)) {
			return array('day' => $vn_day, 'month' => null, 'year' => null, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
		}
		if ($vn_day && isset($pa_options['start']) && isset($pa_options['start']['month']) && $pa_options['start']['month']) {
			$vn_month = $pa_options['start']['month'];
			$vn_year = $pa_options['start']['year'];
			$this->skipToken();
			return array('day' => $vn_day, 'month' => $vn_month, 'year' => $vn_year, 'is_circa' => $vb_is_circa, 'is_probably' => $vb_is_probably);
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
							'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
						);
						return $va_date;
						break;
					} else {
						switch($va_token['type']) {
							# ----------------------
							case TEP_TOKEN_PRESENT:
							case TEP_TOKEN_QUESTION_MARK_UNCERTAINTY:
								$va_date = array(
									'month' => null, 'day' => null, 'year' => TEP_END_OF_UNIVERSE,
									'hours' => null, 'minutes' => null, 'seconds' => null,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_NOW:
								$va_now = $this->gmgetdate();
								$va_date = array(
									'month' => $va_now['mon'], 'day' => $va_now['mday'], 'year' => $va_now['year'],
									'hours' => $va_now['hours'], 'minutes' => $va_now['minutes'], 'seconds' => $va_now['seconds'],
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_YESTERDAY:
								$va_yesterday = $this->gmgetdate(time() - (24 * 60 * 60));
								$va_date = array(
									'month' => $va_yesterday['mon'], 'day' => $va_yesterday['mday'], 'year' => $va_yesterday['year'],
									'hours' => 0, 'minutes' => 0, 'seconds' => 0,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_TODAY:
								$va_today = $this->gmgetdate();
								$va_date = array(
									'month' => $va_today['mon'], 'day' => $va_today['mday'], 'year' => $va_today['year'],
									'hours' => 0, 'minutes' => 0, 'seconds' => 0,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
								);
								$this->skipToken();
								
								return $va_date;
								break;
							# ----------------------
							case TEP_TOKEN_TOMORROW:
								$va_tomorrow = $this->gmgetdate(time() + (24 * 60 * 60));
								$va_date = array(
									'month' => $va_tomorrow['mon'], 'day' => $va_tomorrow['mday'], 'year' => $va_tomorrow['year'],
									'hours' => 0, 'minutes' => 0, 'seconds' => 0,
									'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => false, 'is_probably' => false
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
										'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => $va_date_element['is_circa'], 'is_probably' => $va_date_element['is_probably']
									);
									
									if(!is_array($va_peek = $this->peekToken())) { return $va_date; }
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
								'uncertainty' => false, 'uncertainty_units' => ''
							);
							$this->skipToken();
							
							$va_peek = $this->peekToken();
							
							switch($va_peek['type'] ?? null) {
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
										'uncertainty' => false, 'uncertainty_units' => ''
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
				(preg_match("/^([\d]{2,4})[\'’’]{0,1}(".join("|", $va_decade_indicators)."){1}$/iu", $va_token['value'], $va_matches))
				||
				(preg_match("/^([\d]{3})(\_)$/u", $va_token['value'], $va_matches))
				||
				(preg_match("/^([\d]{2,4})#([\d]{2,4})(".join("|", $va_decade_indicators)."{1})$/iu", $va_token['value'], $va_matches))
			) {
				$vn_is_circa = $vb_circa_is_set ? 1 : 0;
				
				if ($vb_was_peeked) { $this->skipToken(); }
				$this->skipToken();
			
			    $vb_is_bc = false;
				while($va_modfier_token = $this->peekToken()) {
					switch($va_modfier_token['type']) {
						case TEP_TOKEN_ERA:
							if($va_modfier_token['era'] == TEP_ERA_BC) {
								$vb_is_bc = true;
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

				if(sizeof($va_matches) === 4) {	// is range of decades with truncated end date
					if($va_matches[2] <= 99) { $va_matches[2] += ((int)substr($va_matches[1], 0, 2) * 100); }
					$vn_end_year = (int) ($va_matches[2] - ($va_matches[2] % 10));
				} else {
					// decade expression with trailing underscore: 191_
					if (isset($va_matches[2]) && ($va_matches[2] == '_') && (strlen($va_matches[1]) == 3)) {
						$va_matches[1].='0';
					}
					$vn_end_year = (int) ($va_matches[1] - ($va_matches[1] % 10));
				}
			
				$vn_start_year = (int) ($va_matches[1] - ($va_matches[1] % 10));
				if ($vb_is_bc) { $vn_start_year *= -1; }
				$va_dates['start'] = array(
					'month' => 1, 'day' => 1, 'year' => $vn_start_year,
					'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa, 'is_probably' => false
				);
				$va_dates['end'] = array(
					'month' => 12, 'day' => 31, 'year' => $vb_is_bc ? ($vn_end_year - 9) : ($vn_end_year + 9),
					'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa, 'is_probably' => false
				);
			}
		}
		return $va_dates;
	}
	# -------------------------------------------------------------------
	/**
	 * 
	 */
	private function _parseCentury($va_token, $part_of_range_qualifier=null) {
		$va_next_token = $this->peekToken(2);
		
		$vs_next_token_lc = mb_strtolower($va_next_token['value'] ?? null);
		$vn_use_romans = $this->opo_datetime_settings->get("useRomanNumeralsForCenturies");
		$vb_is_range = false;
								
		if (
			($vn_use_romans && in_array($vs_next_token_lc, $this->opo_language_settings->getList("centuryIndicator")) && preg_match("/^([MDCLXVI]+)(.*)$/", $va_token['value'], $va_roman_matches))
			||	
			((in_array($vs_next_token_lc, $this->opo_language_settings->getList("centuryIndicator"))) && (preg_match("/^([\d]+)(.*)$/", $va_token['value'], $va_matches)))
			||
			(preg_match("/^([\d]{2})[_]{2}$/", $va_token['value'], $va_matches))
		) {	
			
			$va_ordinals = $this->opo_language_settings->getList("ordinalSuffixes");
			$va_ordinals[] = $this->opo_language_settings->get("ordinalSuffixDefault");

			if ($vn_use_romans && caIsRomanNumerals($va_roman_matches[1])) {
				$vn_century = intval(caRomanArabic($va_roman_matches[1]));
			} else {
				$vn_century = intval($va_matches[1]);
			}
			
			if (in_array($vs_next_token_lc, $this->opo_language_settings->getList("centuryIndicator"))) {
				$va_next_token = null;
			}
			
			$this->skipToken();
			$this->skipToken();
			
			$vn_is_circa = 0;
			$era = null;
			if($va_modfier_token = (is_array($va_next_token) ? $va_next_token : $this->peekToken())) {
				$va_next_token = null;
				switch($va_modfier_token['type']) {
					case TEP_TOKEN_ERA:
						if($va_modfier_token['era'] == TEP_ERA_BC) {
							$vn_century *= -1;
							$era = TEP_ERA_BC;
						}
						
						$this->skipToken();
						$va_next_next_token = $this->peekToken();
						if (is_array($va_next_next_token) && ($va_next_next_token['type'] == TEP_TOKEN_RANGE_CONJUNCTION)) {
							$vb_is_range = true;
							$this->skipToken();
						}
						break;
					case TEP_TOKEN_QUESTION_MARK_UNCERTAINTY:
						$vn_is_circa = 1;
						$this->skipToken();
						break;
					case TEP_TOKEN_RANGE_CONJUNCTION:
						$vb_is_range = true;
						$this->skipToken();
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
					$vn_start_year = (($vn_century + 1) * 100) - 99;
					$vn_end_year = ($vn_century + 1) * 100;
				} else {
					$vn_start_year = ($vn_century - 1) * 100;
					$vn_end_year = (($vn_century - 1) * 100) + 99;
				}
				
				$vn_start_year = self::applyPartOfRangeQualifier($part_of_range_qualifier, 'start', 'century', $vn_start_year);
				$va_dates['start'] = array(
					'month' => 1, 'day' => 1, 'year' => $vn_start_year,
					'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa, 'is_probably' => false,
					'dont_window' => true
				);
				if(!is_null($era)) {
					$va_dates['start']['era'] = $era;
				}
				if (!$vb_is_range) {
					$vn_end_year = self::applyPartOfRangeQualifier($part_of_range_qualifier, 'end', 'century', $vn_end_year);
					$va_dates['end'] = array(
						'month' => 12, 'day' => 31, 'year' => $vn_end_year,
						'uncertainty' => false, 'uncertainty_units' => '', 'is_circa' => $vn_is_circa, 'is_probably' => false,
						'dont_window' => true
					);
					if(!is_null($era)) {
						$va_dates['start']['era'] = $era;
					}
				}
				
				$vn_state = $vb_is_range ? TEP_STATE_BEGIN : TEP_STATE_ACCEPT;
				$vb_can_accept = !$vb_is_range;
				$part_of_range_qualifier = null;
			}
			
			$part_of_range_qualifier = null;
			return $va_dates;
		}
		return null;
	}
	# -------------------------------------------------------------------
	# Lexical analysis
	# -------------------------------------------------------------------
	private function tokenize($ps_expression) {
		$this->opa_tokens = preg_split("/[\s]+/u", $ps_expression);
		if(!is_array($this->opa_tokens)) { $this->opa_tokens = []; }	// Tokenization can fail if string is invalid UTF-8
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
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("undatedDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_UNDATED);
		}
		// today
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("todayDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_TODAY);
		}
		// yesterday
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("yesterdayDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_YESTERDAY);
		}
		// tomorrow
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("tomorrowDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_TOMORROW);
		}
		// now
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("nowDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_NOW);
		}
		
		// early
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("earlyQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_EARLY);
		}
		
		// mid
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("midQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_MID);
		}
		
		// late
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("lateQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_LATE);
		}
		
		if ($vs_token_lc == '?') {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_QUESTION_MARK_UNCERTAINTY);
		}
		
		// present
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("presentDate"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PRESENT);
		}

		// seasons
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("winterSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_WINTER);
		}
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("springSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_SPRING);
		}
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("summerSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_SUMMER);
		}
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("autumnSeason"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_SEASON_AUTUMN);
		}

		
		if($this->opo_datetime_settings->get('assumeAcademicYears') && preg_match('!^([\d]{4})/([\d]{2})$!', $vs_token, $m)) {
			$s = (int)$m[1];
			$e = (int)$m[2];
			$sx = $s % 100;
			
			if ((($e > $sx) && (($e - $sx) === 1)) || (($sx === 99) && ($e === 0))) {	
				return ['value' => $vs_token, 'start' => $s, 'end' => $s+1, 'type' => TEP_TOKEN_ACADEMIC_DATE];
			}
		}
		
		// text month
		$va_month_table = $this->opo_language_settings->getAssoc("monthTable");
		if ($va_month_table[$vs_token_lc] ?? false) {
			$vs_token_lc = $va_month_table[$vs_token_lc];
		}
		$va_month_list = $this->getLanguageSettingsWordList("monthList");
		if (in_array($vs_token_lc, $va_month_list)) {
			$vn_month = array_search($vs_token_lc, $va_month_list) + 1;
			return array('value' => $vs_token, 'month' => $vn_month, 'type' => TEP_TOKEN_ALPHA_MONTH);
		}
			
		// range pre-conjunction
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("rangePreConjunctions"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PRE_RANGE_CONJUNCTION);
		}
			
		// range conjunction
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("rangeConjunctions"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_RANGE_CONJUNCTION);
		}
		
		// multiword range conjunction?
		foreach($this->getLanguageSettingsWordList("rangeConjunctions") as $vs_conjunction) {
			if (preg_match("!^".preg_quote($vs_token_lc, '!')."!", $vs_conjunction)) {
				$va_pieces = preg_split("![ ]+!", $vs_conjunction);
				array_shift($va_pieces);
				
				$vn_i = 1;
				$vb_is_match = true;
				foreach($va_pieces as $vs_piece) {
					$va_peek_token = $this->peekToken($vn_i);
					$vs_peek_token = $va_peek_token['value'];
					if (trim(strtolower($vs_piece)) != ($vs_peek_token)) {
						$vb_is_match = false;
						break;
					}
					$vn_i++;
				}
				
				if ($vb_is_match) {
					foreach($va_pieces as $vs_piece) { $this->skipToken(); }
					return array('value' => join(' ', array_merge([$vs_token_lc], $va_pieces)), 'type' => TEP_TOKEN_RANGE_CONJUNCTION);
				}
			}
		}
		
			
		// time conjunction
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("dateTimeConjunctions"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_TIME_CONJUNCTION);
		}
		
		// before
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("beforeQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_BEFORE);
		}
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("diedQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_BEFORE);
		}
		
		// after
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("afterQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_AFTER);
		}
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("bornQualifier"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_AFTER);
		}
		
		// margin of error indicator
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("dateUncertaintyIndicator"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_MARGIN_OF_ERROR);
		}
		
		// punctuation
		if (in_array($vs_token_lc, array('.',','))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PUNCTUATION);
		}

		// century with ordinalSuffix
		$va_ordinals = $this->getLanguageSettingsWordList("ordinalSuffixes");
		$va_ordinals[] = $ordinal_suffix_default = $this->opo_language_settings->get("ordinalSuffixDefault");
		if (!is_array($va_ordinal_words = $this->opo_language_settings->getList("ordinalWords"))) { $va_ordinal_words = []; }
		$va_ordinal_word_alts = array_map(function($v) use ($ordinal_suffix_default) { return preg_replace("!".preg_quote($ordinal_suffix_default, "!")."$!i", "", $v); }, $va_ordinal_words);
		
		foreach($va_ordinals as $vs_ordinal){
		    if (
		    	(($i = array_search($vs_token_lc, $va_ordinal_words, true)) !== false)
		    	||
		    	(($i = array_search($vs_token_lc, $va_ordinal_word_alts, true)) !== false)
		    ) { // convert text ordinals (Ex. "fourth") to numeric ordinal ("4th")
		        $ord = isset($va_ordinals[$i]) ? $va_ordinals[$i] : $ordinal_suffix_default;
		        return array('value' => $i.$ord, 'type' => TEP_TOKEN_ALPHA);	
		    }
			if(substr($vs_token_lc, 0 - strlen($vs_ordinal)) == $vs_ordinal){
				$vn_cent = substr($vs_token_lc,0,strlen($vs_token_lc) - strlen($vs_ordinal));
				if(preg_match("/^\d+$/",$vn_cent)){ // could use is_numeric here but this seems safer
					$va_next_tok = $this->peekToken();
					if($va_next_tok['type'] == TEP_TOKEN_ALPHA){ // must not be TEP_TOKEN_ALPHA_MONTH, as in 28. Januar 1985
						return array('value' => $vs_token, 'type' => TEP_TOKEN_ALPHA);	
					} else {
						// strip ordinal 
						return array('value' => $vn_cent, 'type' => TEP_TOKEN_INTEGER);	
					}
				}
			}
		}
		
		// Meridians (AM/PM)
		$va_meridian_lookup = $this->opo_language_settings->getAssoc("meridianTable");
		if ($va_meridian_lookup[$vs_token_lc] ?? false) {
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
		if ($va_era_lookup[$vs_token_lc] ?? false) {
			$vs_token_lc = $va_era_lookup[$vs_token_lc];
		}
		if ($vs_token_lc == $this->opo_language_settings->get("dateADIndicator")) {
			return array('value' => $vs_token, 'era' => TEP_ERA_AD, 'type' => TEP_TOKEN_ERA);
		}
		if ($vs_token_lc == $this->opo_language_settings->get("dateBCIndicator")) {
			return array('value' => $vs_token, 'era' => TEP_ERA_BC, 'type' => TEP_TOKEN_ERA);
		}
		
		// mya
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("dateMYA"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_MYA);
		}
		
		// bp (radiocarbon) dates
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("dateBP"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_BP);
		}
		
		// circa
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("dateCircaIndicator"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_CIRCA);
		}
		
		// probably
		if (in_array($vs_token_lc, $this->getLanguageSettingsWordList("dateProbablyIndicator"))) {
			return array('value' => $vs_token, 'type' => TEP_TOKEN_PROBABLY);
		}
		
		// EXIF date
		if (preg_match("/^([\d]{4}):([\d]{2}):([\d]{2})$/", $vs_token, $va_matches)) {
			return(array('value' => $vs_token, 'month' => $va_matches[2], 'day' => $va_matches[3], 'year' => $va_matches[1], 'type' => TEP_TOKEN_DATE));
		}
		
		// EXIF time
		if (preg_match("/^([\d]{2}):([\d]{2}):([\d]{2}[\.]{0,1}[\d]*)$/", $vs_token, $va_matches)) {
			// year-month
			if ((($va_matches[1] >= 0) && ($va_matches[1] <= 23)) && (($va_matches[2] >= 0) && ($va_matches[2] <= 59))  && (($va_matches[3] >= 0) && ($va_matches[3] < 60))) {
				return(array('value' => $vs_token, 'minutes' => $va_matches[2], 'seconds' => floor($va_matches[3]), 'hours' => $va_matches[1], 'type' => TEP_TOKEN_TIME));
			}
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
			$vn_month = array_search($vs_m, $this->getLanguageSettingsWordList('monthList')) + 1;
			if ((($va_matches[3] >= 0) && ($va_matches[3] <= 2999)) && ($vs_m) && (($va_matches[1] >= 1) && ($va_matches[1] <= $this->daysInMonth($vn_month, $va_matches[3])))) {
				return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $va_matches[1], 'year' => $va_matches[3], 'type' => TEP_TOKEN_DATE));
			}
		}
		
		// date
		$va_date_delimiters = $this->getLanguageSettingsWordList("dateDelimiters");
		
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
						if (!$this->opo_datetime_settings->get('assumeMonthYearDelimitedDates') && ($va_tmp[1] >= 1) && ($va_tmp[1] <= $this->daysInMonth($vn_month, 2004))) {		// since year is unspecified we use a leap year
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
				
				if (is_null($vb_month_comes_first = $this->opo_datetime_settings->get('monthComesFirstInDelimitedDate'))) {
				    $vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
				}
				
				if ((bool)$vb_month_comes_first) {
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
				} elseif (($va_tmp[0] >= 1000) && ($va_tmp[0] <= 9999)) {
					// hmmm... maybe this is a year-month-day date
					$vn_year = (int)$va_tmp[0]; $vn_month = $va_tmp[1]; $vn_day = $va_tmp[2];
					if (($vn_day >= 1) && ($vn_day <= $this->daysInMonth($vn_month, $vn_year ? $vn_year : 2004))) {
						if (($vn_month >= 1) && ($vn_month <= 12)) {
							return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => $vn_year, 'type' => TEP_TOKEN_DATE));
						}
					}
				} elseif(
					($vn_month > 0) && ($vn_month < $this->daysInMonth($vn_day, $vn_year ? $vn_year : 2004)) 
					&& 
					(($vn_day > 0) && ($vn_day <= 12))
				) {
					// try to swap day and month and see if that works...
					$m = $vn_month;
					$vn_month = $vn_day;
					$vn_day = $m;
					if (($vn_day >= 1) && ($vn_day <= $this->daysInMonth($vn_month, $vn_year ? $vn_year : 2004))) {
						if ($vn_year > 0) {
							return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => $vn_year, 'type' => TEP_TOKEN_DATE));
						} else {
							if ((int)$vn_year === 0) {		// no year
								return(array('value' => $vs_token, 'month' => $vn_month, 'day' => $vn_day, 'year' => 0, 'type' => TEP_TOKEN_DATE));
							}
						}
					}
				}
				break;
		}
		
		// time
		$va_time_delimiters = $this->getLanguageSettingsWordList("timeDelimiters");
		
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
				$vn_hours = (int)$va_tmp[0]; 
				$vn_minutes = (int)$va_tmp[1]; 
				if(!isset($va_tmp[3])) { $va_tmp[3] = 0; }
				$vn_seconds = (int)$va_tmp[2] + (is_numeric($va_tmp[3]) ? (intval($va_tmp[3]) / pow(10, strlen((intval($va_tmp[3]))))) : 0);
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
		
		if (!is_array($pa_dates['end'] ?? null)) { 
			if (($pa_dates['start']['hours'] == 0) && ($pa_dates['start']['minutes'] == 0) && ($pa_dates['start']['seconds'] == 0)) {
				$pa_dates['end'] = $pa_dates['start'];
				$pa_dates['end']['hours'] = 23; $pa_dates['end']['minutes'] = 59; $pa_dates['end']['seconds'] = 59;
			} else {
				$pa_dates['end'] = $pa_dates['start']; 
			}
		}
		
		$pa_options['mode'] = $pa_options['mode'] ?? null;
		
		if ($pa_dates['start']['is_undated'] ?? false) {
			$this->opn_start_unixtime = null;
			$this->opn_end_unixtime = null;
			
			$this->opn_start_historic = null;
			$this->opn_end_historic = null;
			
			return true;
		}
		if (!$pa_dates['start']['day'] && !$pa_dates['start']['month'] && !$pa_dates['start']['year']) {
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
			
			// Implicitly set BCE on start date if no era set for start and end is BCE
			if(isset($pa_dates['end']['era']) && ($pa_dates['end']['era'] === TEP_ERA_BC) && !isset($pa_dates['start']['era'])) {
				$pa_dates['start']['era'] = TEP_ERA_BC;
				$pa_dates['start']['year'] *= -1;
			}
			
			// first date is a bare two digit number interpreted as a year but should be day in a range
			if ($pa_dates['start']['year'] && is_null($pa_dates['start']['month']) && (strlen($pa_dates['start']['year']) === 2) && ($pa_dates['end']['year'] >= 100)) {
				$pa_dates['start']['day'] = $pa_dates['start']['year'];
				$pa_dates['start']['year'] = null;
			}
			
			// last date is a bare two digit number interpreted as a year but should be day in a range
			if ($pa_dates['end']['year'] && is_null($pa_dates['end']['month']) && (strlen($pa_dates['end']['year']) === 2) && is_null($pa_dates['start']['year'])) {
				$pa_dates['end']['day'] = $pa_dates['end']['year'];
				$pa_dates['end']['year'] = null;
			}
			
			
			// Blank start month and year and year implies carry over of start date
			if (!$pa_dates['start']['month'] && !$pa_dates['start']['year'] && $pa_dates['start']['day']) {
				$pa_dates['start']['year'] = $pa_dates['end']['year'];
				$pa_dates['start']['month'] = $pa_dates['end']['month'];
			}
			
			// Blank end day and month and year implies carry over of start date
			if (!$pa_dates['end']['year'] && !$pa_dates['end']['month'] && !$pa_dates['end']['day']) {
				$pa_dates['end']['year'] = $pa_dates['start']['year'];
				$pa_dates['end']['month'] = $pa_dates['start']['month'];
				$pa_dates['end']['day'] = $pa_dates['start']['day'];
			}

			// Two-digit year windowing
			if (
				(!isset($pa_dates['start']['dont_window']) || !$pa_dates['start']['dont_window'])
				&&
				(!isset($pa_dates['start']['era']) && ($pa_dates['start']['month'] > 0) && ($pa_dates['start']['year'] > 0) && ($pa_dates['start']['year'] <= 99))
			) {
				$pa_dates['start']['year'] = $this->windowYear($pa_dates['start']['year']);
			}
			
			if (
				(!isset($pa_dates['end']['dont_window']) || !$pa_dates['end']['dont_window'])
				&&
				(!isset($pa_dates['end']['era']) && ($pa_dates['end']['month'] > 0) && ($pa_dates['end']['year'] > 0) && ($pa_dates['end']['year'] <= 99))
			) {
				$pa_dates['end']['year'] = $this->windowYear($pa_dates['end']['year']);
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
					$va_current_date = $this->gmgetdate();
					$pa_dates['end']['year'] = $va_current_date['year'];
					if (($pa_dates['end']['month'] === null) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE)) { 
						$pa_dates['end']['month'] = $pa_dates['start']['month']; 
					}	
				}
			}
			
			if (($pa_dates['start']['year'] === TEP_START_OF_UNIVERSE) && ($pa_dates['end']['year'] !== TEP_END_OF_UNIVERSE)) {
				if($pa_dates['end']['month'] === null) { $pa_dates['end']['month'] = 12; }
				if($pa_dates['end']['year'] === null) { $pa_dates['end']['year'] = date("Y"); }
				if($pa_dates['end']['day'] === null) { $pa_dates['end']['day'] = $this->daysInMonth( $pa_dates['end']['month'], $pa_dates['end']['year']); }
			}
			
			if (($pa_dates['start']['year'] !== TEP_START_OF_UNIVERSE) && ($pa_dates['end']['year'] === TEP_END_OF_UNIVERSE)) {
				if($pa_dates['start']['month'] === null) { $pa_dates['start']['month'] = 1; }
				if($pa_dates['start']['day'] === null) { $pa_dates['start']['day'] = 1; }
				if($pa_dates['start']['year'] === null) { $pa_dates['start']['year'] = date("Y"); }
			}
		
			if (($pa_dates['start']['month'] === null) && ($pa_dates['end']['month'] === null) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE) && ($pa_dates['end']['year'] != TEP_END_OF_UNIVERSE)) { 
				$pa_dates['start']['month'] = 1; 
				$pa_dates['end']['month'] = 12; 
			} 
			
			# if no year is specified on the start date, then use the ending year 
			if (is_null($pa_dates['start']['year'])) {
				$pa_dates['start']['year'] = $pa_dates['end']['year'];
				if ($pa_dates['start']['month'] > $pa_dates['end']['month']) {
					$pa_dates['start']['year']--;
				}
			}
			
			if (($pa_dates['start']['day'] === null) && ($pa_dates['end']['day'] === null) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE) && $pa_dates['end']['year'] != TEP_END_OF_UNIVERSE) { 
				$pa_dates['start']['day'] = 1; 
				if(!$pa_dates['start']['month']) { $pa_dates['start']['month'] = 1; }
				if(!$pa_dates['end']['month']) { $pa_dates['end']['month'] = 12; }
				$pa_dates['end']['day'] = $this->daysInMonth($pa_dates['end']['month'], $pa_dates['end']['year'] ? $pa_dates['end']['year'] : 2004); // use leap year if no year is defined
			} elseif (($pa_dates['end']['day'] === null) && ($pa_dates['end']['year'] != TEP_END_OF_UNIVERSE) && ($pa_dates['start']['year'] != TEP_START_OF_UNIVERSE)) { 
				$pa_dates['end']['day'] = $this->daysInMonth($pa_dates['end']['month'], $pa_dates['end']['year']);
			}
			
			if ($pa_dates['end']['month'] === null) { 
				if ($pa_dates['start']['year'] == $pa_dates['end']['year']) {
					$pa_dates['end']['month'] = $pa_dates['start']['month']; 
				} else {
					$pa_dates['end']['month'] = 12;
					$pa_dates['end']['day'] = 31;
				}
			}
			
			if (($pa_dates['start']['hours'] ?? null) === null) { $pa_dates['start']['hours'] = 0; }
			if (($pa_dates['start']['minutes'] ?? null) === null) { $pa_dates['start']['minutes'] = 0; }
			if (($pa_dates['start']['seconds'] ?? null) === null) { $pa_dates['start']['seconds'] = 0; }
			if (($pa_dates['end']['hours'] ?? null) === null) { $pa_dates['end']['hours'] = 23; }
			if (($pa_dates['end']['minutes'] ?? null) === null) { $pa_dates['end']['minutes'] = 59; }
			if (($pa_dates['end']['seconds'] ?? null) === null) { $pa_dates['end']['seconds'] = 59; }
		
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
			
			
			// 
			if (!$pa_dates['start']['month'] && $pa_dates['start']['year'] && (abs($pa_dates['start']['year']) !== 2000000000)) {
				$pa_dates['start']['month'] = 1;
			}
			if (!$pa_dates['start']['day'] && $pa_dates['start']['month'] && (abs($pa_dates['start']['year']) !== 2000000000)) {
				$pa_dates['start']['day'] = 1;
			}
			
			
			# create historic timestamps
			# -- encode uncertainty, circa and probably status
		
			# date attribute byte (actually a single digit - 0 to 9 - which mean 3 effective bits)
			# bit 0 indicates whether date is "circa" or not
			# bit 1 & 2 indicate uncertainty units:
			#	00 = no uncertainty
			#	01 = uncertainty is in days
			#	10 = uncertainty is in years
			#
			# If value is 9 then "probably" is indicated; no uncertainty can be set with probably
			#
			# If units are not 00, then all digits following it are the uncertainty quantity
			$vn_start_attributes = 0;
			if ($pa_dates['start']['is_circa'] ?? false) {
				$vn_start_attributes = 1;
			}
			if ($pa_dates['start']['is_bp'] ?? false) {
				$vn_start_attributes += 8;
			}
			if ($pa_dates['start']['is_probably'] ?? false) {
				$vn_start_attributes = 9;
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
			if ($pa_dates['end']['is_circa'] ?? false) {
				$vn_end_attributes = 1;
			}
			if ($pa_dates['end']['is_bp'] ?? false) {
				$vn_end_attributes += 8;
			}
			if ($pa_dates['end']['is_probably'] ?? false) {
				$vn_start_attributes = 9;
			}
			
			$vn_end_uncertainty = '';
			if (isset($pa_dates['end']['uncertainty']) && ($pa_dates['end']['uncertainty'] > 0)) {
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
			if (($vn_start_historic < 0) && ($vn_end_historic < 0)) {
			    if ((int)$vn_start_historic > (int)$vn_end_historic) {
				    $this->setParseError(null, TEP_ERROR_RANGE_ERROR);
				    return false;
				}
				if (((int)$vn_start_historic === (int)$vn_end_historic) && ($vn_end_historic > $vn_start_historic)) {
				    $this->setParseError(null, TEP_ERROR_RANGE_ERROR);
				    return false;
				}
			}
			
			if((int)$vn_end_historic < (int)$vn_start_historic) {
				print "$vn_end_historic  // $vn_start_historic\n";
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
	/**
	 * Return parsed date/time range as text expression for display. The returned expression is always in a parseable format 
	 * subject to settings in datetime.conf and the options described below.
	 *
	 * @param array $pa_options Options include:
	 *		timeFormat = force use of specific time format (12 or 24 hour). Set to 12 or 24. [Default is 24]
	 *		timeFormat		(12|24) [default is 24]		=	time format; 12 hour or 24 hour format
	 *		timeDelimiter	(string) [default is first delimiter in language config file]	=	Must be a valid time delimiter for the current language or default will be used
	 *		timeRangeConjunction (string)	[default is first in lang. config]
	 *		timeOmitSeconds (true|false) [default is false]
	 *		timeOmit		(true|false) [default is false] if true, no times are displayed
	 *
	 *		rangePreConjunction (string) [default is none]
	 *		rangeConjunction (string) [default is first in lang. config]
	 *		dateTimeConjunction (string) [default is first in lang. config]
	 *		showADEra (true|false) [default is false]
	 *		uncertaintyIndicator (string) [default is first in lang. config]
 	 *		dateFormat		(text|delimited|iso8601|yearOnly|ymd|xsd)	[default is text]
	 *		dateDelimiter	(string) [default is first delimiter in language config file]
	 *		circaIndicator	(string) [default is first indicator in language config file]
	 *		beforeQualifier	(string) [default is first indicator in language config file]
	 *		afterQualifier	(string) [default is first indicator in language config file]
	 *		presentDate		(string) [default is first indicator in language config file]
	 *		showUndated		(true|false) [default is false; if true empty dates are output with the first undated specified for the current language]
	 *		isLifespan		(true|false) [default is false; if true, date is output with 'born' and 'died' syntax if appropriate]
	 *  	useQuarterCenturySyntaxForDisplay (true|false) [default is false; if true dates ranging over uniform quarter centuries (eg. 1900 - 1925, 1925 - 1950, 1950 - 1975, 1975-2000) will be output in the format "20 Q1" (eg. 1st quarter of 20th century... 1900 - 1925)
	 *  	useRomanNumeralsForCenturies (true|false) [default is false; if true century only dates (eg 18th century) will be output in roman numerals like "XVIIIth century"
	 *		startAsISO8601 (true|false) [if true only the start date of the range is returned, in ISO8601 format]
	 *		start_as_iso8601 (true|false) Synonym for startAsISO8601
	 *		endAsISO8601 (true|false) [if true only the end date of the range is returned, in ISO8601 format]
	 *		end_as_iso8601 (true|false) Synonym for endAsISO8601
	 *		dontReturnValueIfOnSameDayAsStart (true|false) [Only valid in conjunction with end_as_iso8601]
	 *		startHistoricTimestamp
	 *		endHistoricTimestamp
	 *		format 			(string) Format date/time output using PHP date()-style format string. The following subset of PHP date() formtting characters are supported: Y y d j F m n t g G h H i s [Default is null; don't use format string for output]
	 *		normalize = normalize parsed date range to "days"/"years"/"decades"/"centuries" and return as display text. Setting normalize to "years", for example will return a range that only includes the start and end year, regardless of the specificity of the parsed date range. [Default is null, no normalization]
	 *
	 *	@return string
	 */
	public function getText($pa_options=null) {
		if (!$pa_options) { $pa_options = array(); }
		foreach(array(
			'dateFormat', 'dateDelimiter', 'uncertaintyIndicator', 
			'showADEra', 'timeFormat', 'timeDelimiter', 
			'circaIndicator', 'beforeQualifier', 'afterQualifier', 
			'presentDate', 'useQuarterCenturySyntaxForDisplay', 'timeOmit', 'useRomanNumeralsForCenturies', 
			'rangePreConjunction', 'rangeConjunction', 'timeRangeConjunction', 'dateTimeConjunction', 'showUndated',
			'useConjunctionForAfterDates', 'showCommaAfterDayForTextDates'
		) as $vs_opt) {
			if (!isset($pa_options[$vs_opt]) && ($vs_opt_val = $this->opo_datetime_settings->get($vs_opt))) {
				$pa_options[$vs_opt] = $vs_opt_val;
			}
		}
		
		if ($ps_normalization = caGetOption('normalize', $pa_options, null)) {
			$va_dates = $this->getHistoricTimestamps();
			$va_normalized = $this->normalizeDateRange($va_dates['start'], $va_dates['end'], $ps_normalization);
			$vs_start = array_shift($va_normalized);
			$vs_end = array_pop($va_normalized);
			if ($vs_start === $vs_end) { return $vs_start; }
			if ($vs_start && !$vs_end) { return $vs_start; }
			$o_tep = new TimeExpressionParser();
			$vs_default_conjunction = array_shift($this->opo_language_settings->getList("rangeConjunctions"));
			if ($o_tep->parse("{$vs_start} {$vs_default_conjunction} {$vs_end}")) {
				return $o_tep->getText(array_merge($pa_options, ['normalize' => null]));
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
		if (($va_times['start'] ?? null) != null) {
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
		
			if ((($va_unix_dates['start'] ?? null) != null) && (($va_unix_dates['start'] ?? null) != -1)) {
				// convert unix timestamps for historic timestamp format for evaluation
				$va_dates = array(
					'start' 	=> $this->unixToHistoricTimestamp($va_unix_dates['start']),
					'end' 		=> $this->unixToHistoricTimestamp($va_unix_dates['end'])
				);
			} 
		}
		
		// is it undated?
		if (($va_dates['start'] === null) && ($va_dates['end'] === null)) {
			if (($pa_options['isLifespan'] ?? false) || !($pa_options['showUndated'] ?? false)) { return ''; }	// no "undated" for lifedates
			if (is_array($va_undated = $this->opo_language_settings->getList('undatedDate'))) {
				return array_shift($va_undated);
			} 
			return "????";
		}
	
	
		// academic dates
		if($this->opo_datetime_settings->get('assumeAcademicYears')) {
			
			if(
				(substr($va_dates['start'], 5, 10) === '0701000000') &&
				(substr($va_dates['end'], 5, 10) === '0630235959')
			) {
				return (int)$va_dates['start'].'/'.substr((int)$va_dates['end'], 2);
			}
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
				
			if ($start_as_iso = caGetOption(['start_as_iso8601', 'startAsISO8601'], $pa_options, false)) {
				return $this->getISODateTime($va_start_pieces, 'FULL', $pa_options);
			}
			if ($end_as_iso = caGetOption(['end_as_iso8601', 'endAsISO8601'], $pa_options, false)) {
				if(caGetOption('dontReturnValueIfOnSameDayAsStart', $pa_options, false)) {
					if(
						$va_start_pieces['year'] == $va_end_pieces['year'] &&
						$va_start_pieces['month'] == $va_end_pieces['month'] &&
						$va_start_pieces['day'] == $va_end_pieces['day']
					) {
						return null;
					}
				}
				return $this->getISODateTime($va_end_pieces, 'FULL', $pa_options);
			}
			
			// start is same as end so just output start date
			if ($va_dates['start'] == $va_dates['end']) {
				if ((isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'yearOnly'))) { 
					return $va_start_pieces['year'];
				}
				if (($start_as_iso) || $end_as_iso) {
					return $this->getISODateTime($va_start_pieces, 'FULL', $pa_options);
				}
				if ((isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'iso8601'))) { 
					return $this->getISODateTime($va_start_pieces, 'START', $pa_options);
				} else {
					return $this->_dateTimeToText($va_start_pieces, $pa_options);
				}
			} elseif ((isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'yearOnly'))) { 
				$va_range_conjunctions = $this->opo_language_settings->getList('rangeConjunctions');
				return ($va_start_pieces['year'] != $va_end_pieces['year']) ? $va_start_pieces['year']." ".$va_range_conjunctions[0]." ".$va_end_pieces['year'] : $va_start_pieces['year'];
			}
			
		
			if ($va_start_pieces['year'] == 0) {		// year is not known
				$va_start_pieces['year'] = '????';
				$pa_options['dateFormat'] = 'delimited';		// always output dates with unknown years as delimited as that is the only format that supports them
			}
			if ($va_end_pieces['year'] == 0) {
				$va_end_pieces['year'] = '????';
				$pa_options['dateFormat'] = 'delimited';
			}

			// show era for dates that span eras, but not for 'before XXXX' dates, which technically span
			// eras but .. you know, not really. Their startpoint is TEP_START_OF_UNIVERSE
			if (($va_start_pieces['era'] != $va_end_pieces['era']) && ($va_dates['start'] > TEP_START_OF_UNIVERSE)) {
				$pa_options['showADEra'] = true;
			}
			
			
			if (isset($pa_options['dateFormat']) && (in_array($pa_options['dateFormat'], ['iso8601', 'xsd'], true))) {
				return $this->getISODateRange($va_start_pieces, $va_end_pieces, $pa_options);
			}

			// special treatment for HSP
			if (caGetOption('dateFormat', $pa_options) == 'onlyDatesWithHyphens') {
				// full year -> just return the year
				if (
					$va_start_pieces['year'] == $va_end_pieces['year']  &&
					$va_start_pieces['day'] == 1 && $va_start_pieces['month'] == 1 &&
					$va_start_pieces['hours'] == 0 && $va_start_pieces['minutes'] == 0 && $va_start_pieces['seconds'] == 0 &&
					$va_end_pieces['day'] == 31 && $va_end_pieces['month'] == 12 &&
					$va_end_pieces['hours'] == 23 && $va_end_pieces['minutes'] == 59 && $va_end_pieces['seconds'] == 59
				) {
					return ''.$va_start_pieces['year'];
				}

				$vs_date = $va_start_pieces['year'].'-'.sprintf('%02d', $va_start_pieces['month']).'-'.sprintf('%02d', $va_start_pieces['day']);

				if(!(
					$va_start_pieces['year'] == $va_end_pieces['year'] &&
					$va_start_pieces['month'] == $va_end_pieces['month'] &&
					$va_start_pieces['day'] == $va_end_pieces['day']
				)) {
					$vs_date .= '/'.$va_end_pieces['year'].'-'.sprintf('%02d', $va_end_pieces['month']).'-'.sprintf('%02d', $va_end_pieces['day']);
				}

				return $vs_date;
			}

			if ($pa_options['start_as_na_date'] ?? null) {
				$vs_date = $va_start_pieces['month'].'-'.$va_start_pieces['day'].'-'.$va_start_pieces['year'];
				
				if (!($va_start_pieces['hours'] == 0 && $va_start_pieces['minutes'] == 0 && $va_start_pieces['seconds'] == 0)) {
					$vs_date .= ' '.$va_start_pieces['hours'].':'.$va_start_pieces['minutes'].':'.$va_start_pieces['seconds'];
				}
				return $vs_date;
			}
			if ($pa_options['end_as_na_date'] ?? null) {
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
			
			// catch formats
			if ($pa_options['format'] ?? null) {
				$va_seen = array();
				$vs_output = '';
				for($vn_i=0; $vn_i < strlen($pa_options['format']); $vn_i++) {
					$vb_not_handled = false;
					switch($vs_c = $pa_options['format'][$vn_i]) {
						case 'Y':
							$vn_year = (!$va_seen[$vs_c]) ? $va_start_pieces['year'] : $va_end_pieces['year'];
							if (($vn_year == TEP_START_OF_UNIVERSE) || ($vn_year == TEP_END_OF_UNIVERSE)) { $vn_year = null; }
							$vs_output .= $vn_year;
							break;
						case 'y':
							$vn_year = (!$va_seen[$vs_c]) ? $va_start_pieces['year'] : $va_end_pieces['year'];
							if (($vn_year == TEP_START_OF_UNIVERSE) || ($vn_year == TEP_END_OF_UNIVERSE)) { $vn_year = null; }
							$vs_output .= substr($vn_year, 2);
							break;
						case 'd':
							$vs_output .= (!$va_seen[$vs_c]) ? sprintf("%02d", $va_start_pieces['day']) : sprintf("%02d", $va_end_pieces['day']);
							break;
						case 'j':
							$vs_output .= (!$va_seen[$vs_c]) ? (int)$va_start_pieces['day'] : (int)$va_end_pieces['day'];
							break;
						case 'F':
							$vs_output .= (!$va_seen[$vs_c]) ? $this->getMonthName($va_start_pieces['month']) : $this->getMonthName($va_end_pieces['month']);
							break;
						case 'm':
							$vs_output .= (!$va_seen[$vs_c]) ? sprintf("%02d", $va_start_pieces['month']) : sprintf("%02d", $va_end_pieces['month']);
							break;
						case 'n':
							$vs_output .= (!$va_seen[$vs_c]) ? (int)$va_start_pieces['month'] : (int)$va_end_pieces['month'];
							break;
						case 't':
							$vs_output .= (!$va_seen[$vs_c]) ? $this->daysInMonth($va_start_pieces['month'], $va_start_pieces['year']) : $this->daysInMonth($va_end_pieces['month'], $va_end_pieces['year']);
							break;
						case 'g':
							$vn_24h_time = (!$va_seen[$vs_c]) ? (int)$va_start_pieces['hours'] : (int)$va_end_pieces['hours'];
							if ($vn_24h_time > 12) { $vn_24h_time -= 12; }
							$vs_output .= $vn_24h_time;
							break;
						case 'G':
							$vs_output .= (!$va_seen[$vs_c]) ? (int)$va_start_pieces['hours'] : (int)$va_end_pieces['hours'];
							break;
						case 'h':
							$vn_24h_time = (!$va_seen[$vs_c]) ? (int)$va_start_pieces['hours'] : (int)$va_end_pieces['hours'];
							if ($vn_24h_time > 12) { $vn_24h_time -= 12; }
							$vs_output .= sprintf("%02d", $vn_24h_time);
							break;
						case 'H':
							$vs_output .= (!$va_seen[$vs_c]) ? sprintf("%02d", $va_start_pieces['hours']) : sprintf("%02d", $va_end_pieces['hours']);
							break;
						case 'i':
							$vs_output .= (!$va_seen[$vs_c]) ? sprintf("%02d", $va_start_pieces['minutes']) : sprintf("%02d", $va_end_pieces['minutes']);
							break;
						case 's':
							$vs_output .= (!$va_seen[$vs_c]) ? sprintf("%02d", $va_start_pieces['seconds']) : sprintf("%02d", $va_end_pieces['seconds']);
							break;
						default:
							$vb_not_handled = true;
							$vs_output .= $vs_c;
							break;
					}
					if (!$vb_not_handled) {
						$va_seen[$vs_c]++;
					}
				}
				
				return $vs_output;
			}

			// catch quarter centuries
			if (
				($pa_options['useQuarterCenturySyntaxForDisplay'] ?? null)
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
					if (isset($pa_options['beforeQualifier']) && $pa_options['beforeQualifier'] && in_array($pa_options['beforeQualifier'], $va_before_qualifiers)) {
						$vs_before_qualifier = $pa_options['beforeQualifier'] ;
					} else {
						$vs_before_qualifier = $va_before_qualifiers[0];
					}
				}

				if ($va_end_pieces['hours'] == 23 && $va_end_pieces['minutes'] == 59 && $va_end_pieces['seconds'] == 59) {
					if ($va_end_pieces['day'] == 31 && $va_end_pieces['month'] == 12) {
						return $vs_before_qualifier.' '. $this->_dateToText(array(
							'year' => $va_end_pieces['year'],
							'era' => $va_end_pieces['era'],
							'uncertainty' => $va_end_pieces['uncertainty'] ?? null,
							'uncertainty_units' => $va_end_pieces['uncertainty_units'] ?? null
						), $pa_options);
					} else {
						if ($va_end_pieces['day'] == $this->daysInMonth($va_end_pieces['month'], $va_end_pieces['year'])) { unset($va_end_pieces['day']); }
						return $vs_before_qualifier.' '. $this->_dateToText($va_end_pieces, $pa_options);
					}
				} else {
					return $vs_before_qualifier.' '. $this->_dateTimeToText($va_end_pieces, $pa_options);
				}
			}

			// catch 'after' dates
			if ($va_dates['end'] >= TEP_END_OF_UNIVERSE) {
				if ($va_start_pieces['hours'] == 0 && $va_start_pieces['minutes'] == 0 && $va_start_pieces['seconds'] == 0) {
					if ($va_start_pieces['day'] == 1 && $va_start_pieces['month'] == 1) {
						$vs_date = $this->_dateToText(array(
							'year' => $va_start_pieces['year'],
							'era' => $va_start_pieces['era'],
							'uncertainty' => $va_start_pieces['uncertainty'],
							'uncertainty_units' => $va_start_pieces['uncertainty_units']
						), $pa_options);
					} else {
						if ($va_start_pieces['day'] == 1) { unset($va_start_pieces['day']); }
						$vs_date = $this->_dateToText($va_start_pieces, $pa_options);
					}
				} else {
					$vs_date = $this->_dateTimeToText($va_start_pieces, $pa_options);
				}

				if (caGetOption('useConjunctionForAfterDates', $pa_options, false)) {
					$va_range_conjunctions = $this->opo_language_settings->getList('rangeConjunctions');
					return "{$vs_date} ".$va_range_conjunctions[0];
				} else {
					$va_born_qualifiers = $this->opo_language_settings->getList('bornQualifier');
					if (($pa_options['isLifespan'] ?? false) && (sizeof($va_born_qualifiers) > 0)) {
						$vs_after_qualifier = $va_born_qualifiers[0];
					} else {
						$va_after_qualifiers = $this->opo_language_settings->getList('afterQualifier');
						if (($pa_options['afterQualifier'] ?? null) && in_array($pa_options['afterQualifier'], $va_after_qualifiers)) {
							$vs_after_qualifier = $pa_options['afterQualifier'] ;
						} else {
							$vs_after_qualifier = $va_after_qualifiers[0];
						}
					}
					return "{$vs_after_qualifier} {$vs_date}";
				}
			}

			// catch 'circa' and 'probably' dates
			$va_circa_indicators = $this->opo_language_settings->getList('dateCircaIndicator');
			$vs_circa_indicator = (($pa_options['circaIndicator'] ?? null) && in_array($pa_options['circaIndicator'], $va_circa_indicators)) ? $pa_options['circaIndicator'] : $va_circa_indicators[0];

            $va_probably_indicators = $this->opo_language_settings->getList('dateProbablyIndicator');
			$vs_probably_indicator = (($pa_options['probablyIndicator'] ?? null) && in_array($pa_options['probablyIndicator'], $va_probably_indicators)) ? $pa_options['probablyIndicator'] : $va_probably_indicators[0];

			$vs_start_circa = $vs_end_circa = '';
			if ($va_start_pieces['is_circa']) { $vs_start_circa = $vs_circa_indicator.' '; }
			if ($va_end_pieces['is_circa'] && !$va_start_pieces['is_circa']) { $vs_end_circa = $vs_circa_indicator.' '; }
			if ($va_start_pieces['is_probably']) { $vs_start_circa = $vs_probably_indicator.' '; }
			if ($va_end_pieces['is_probably'] && !$va_start_pieces['is_probably']) { $vs_end_circa = $vs_probably_indicator.' '; }

			if ($va_start_pieces['year'] == $va_end_pieces['year']) {
				if ($va_start_pieces['month'] == $va_end_pieces['month']) {
					if ($va_start_pieces['day'] == $va_end_pieces['day']) {	// dates on same day
						// print date
						$vs_day = $this->_dateToText(array('year' => $va_start_pieces['year'], 'month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day'], 'era' => $va_end_pieces['era'], 'uncertainty' => $va_end_pieces['uncertainty'], 'uncertainty_units' => $va_end_pieces['uncertainty_units']), $pa_options);

						if (!$vb_full_day_time_range) {
							$vn_start_time = ((int)$va_start_pieces['hours'] * 3600) + ((int)$va_start_pieces['minutes'] * 60) + (int)$va_start_pieces['seconds'];
							$vn_end_time = ((int)$va_end_pieces['hours'] * 3600) + ((int)$va_end_pieces['minutes'] * 60) + (int)$va_end_pieces['seconds'];

							return $vs_start_circa.$vs_day.' '.$vs_datetime_conjunction.' '.$this->_timerangeToText($vn_start_time, $vn_end_time, $pa_options);
						}

						return $vs_start_circa.$vs_day;
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
								return $vs_start_circa.$this->_dateToText(array('month' => $va_start_pieces['month'], 'year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
							} else {
								if ($vb_full_day_time_range) {
									// days, but no times
									if (is_null($vb_month_comes_first = $this->opo_datetime_settings->get('monthComesFirstInDelimitedDate'))) {
									    $vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
									}
									if($vb_month_comes_first) {
										if((bool)$this->opo_datetime_settings->get('forceCommaAfterDay')) {
											$pa_options['forceCommaAfterDay'] = true;
										}
										$vs_start_date = $this->_dateToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day']), $pa_options);
										$vs_end_date = $this->_dateToText(array('day' => $va_end_pieces['day']), $pa_options);
									} else {
										$vs_start_date = $this->_dateToText(array('day' => $va_start_pieces['day']), $pa_options);
										$vs_end_date = $this->_dateToText(array('month' => $va_start_pieces['month'], 'day' => $va_end_pieces['day']), $pa_options);
									}

									$vs_year = $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);

									return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': '').$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_date.((((bool)$pa_options['showCommaAfterDayForTextDates'] || $pa_options['forceCommaAfterDay']) && ($pa_options['dateFormat'] == 'text')) ? ', ' : ' ').$vs_year;
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
							return $vs_start_circa.$this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units'], 'is_bp' => $va_start_pieces['is_bp']), $pa_options);
						} else {
							if ($vb_full_day_time_range) {
								// date range within single year without time
								
								list($va_start_pieces, $va_end_pieces) = $this->_nullDaysAndMonthsForDisplay($va_start_pieces, $va_end_pieces);
								
								if(caGetOption('dateFormat', $pa_options, null) == 'text') {
									$vs_start_date = $this->_dateToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day']), $pa_options);
									if((bool)($pa_options['showCommaAfterDayForTextDates'] ?? false) || (bool)$this->opo_datetime_settings->get('forceCommaAfterDay')) {
										$pa_options['forceCommaAfterDay'] = true;
									}
									$vs_end_date = $this->_dateToText(array('month' => $va_end_pieces['month'], 'day' => $va_end_pieces['day']), $pa_options);
									$vs_year = $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
									return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_start_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_circa.$vs_end_date.(($pa_options['forceCommaAfterDay'] ?? false) ? ', ' : ' ').$vs_year;
								} else {
									$vs_start_date = $this->_dateToText($va_start_pieces, $pa_options);
									$vs_end_date = $this->_dateToText($va_end_pieces, $pa_options);
									return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_start_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_circa.$vs_end_date;
								}
							} else {
								// date range within single year with time
								$vs_start_date = $this->_datetimeToText(array('month' => $va_start_pieces['month'], 'day' => $va_start_pieces['day'], 'hours' => $va_start_pieces['hours'], 'minutes' => $va_start_pieces['minutes'], 'seconds' => $va_start_pieces['seconds']), $pa_options);
								$vs_end_date = $this->_datetimeToText($va_end_pieces, $pa_options);
								return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_start_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_circa.$vs_end_date;
							}
						}
					}
				}
			} else {															// dates in different years
			
				// Try to infer qualified ranges from years (Eg. 1700 - 1720  => "early 18th century")
				if (!$this->opo_datetime_settings->get('dontInferQualifiedRanges') && is_array($qualified_range_info = self::inferRangeQualifier(['start' => $va_start_pieces, 'end' => $va_end_pieces], $pa_options))) {
					return $qualified_range_info['value'];
				}
				
				// handle multi-year ranges (ie. 1941 to 1945)
				if (
					$vb_full_day_time_range &&
					$va_start_pieces['month'] == 1 && $va_start_pieces['day'] == 1 &&
					$va_end_pieces['month'] == 12 && $va_end_pieces['day'] == 31
				) {
					// years only
					if($va_start_pieces['is_bp'] ?? false) {
						$va_bp_indicators = $this->opo_language_settings->getList("dateBP");
						return (1950 - $va_start_pieces['year']).' '.$va_bp_indicators[0].' '.$vs_range_conjunction.' '.(1950 - $va_end_pieces['year']).' '.$va_bp_indicators[0];
					}

					// catch decade dates
					$vs_start_year = $this->_dateToText(array('year' => $va_start_pieces['year'], 'era' => $va_start_pieces['era'], 'uncertainty' => $va_start_pieces['uncertainty'], 'uncertainty_units' => $va_start_pieces['uncertainty_units']), $pa_options);
					$vs_end_year = $this->_dateToText(array('year' => $va_end_pieces['year'], 'era' => $va_end_pieces['era'], 'uncertainty' => $va_end_pieces['uncertainty'], 'uncertainty_units' => $va_end_pieces['uncertainty_units']), $pa_options);
					if ((((int)$vs_start_year % 10) == 0) && ((int)$vs_end_year == ((int)$vs_start_year + 9))) {
						return $this->makeDecadeString(['start' => $va_start_pieces, 'end' => $va_end_pieces], $pa_options);
					} else {
						// catch century dates
						if (
							(((int)$va_start_pieces['year'] % 100) == 0) && 
							(
								(((int)$va_start_pieces['year'] >= 0) && ((int)$va_end_pieces['year'] == ((int)$va_start_pieces['year'] + 99)))
							)
						) {
							return $this->makeCenturyString(['start' => $va_start_pieces, 'end' => $va_end_pieces], $pa_options);
						}
						if (
							(((int)$va_end_pieces['year'] % 100) == 0) && 
							(
								(((int)$va_start_pieces['year'] <= 0) && ((int)$va_start_pieces['year'] == ((int)$va_end_pieces['year'] - 99)))
							)
						) {
							return $this->makeCenturyString(['start' => $va_start_pieces, 'end' => $va_end_pieces], $pa_options);
						}

						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_start_circa).$vs_start_year.' '.$vs_range_conjunction.' '.$vs_end_circa.$vs_end_year;
					}

				} else {
					if ($vb_full_day_time_range) {
						// full dates with no times	
						list($va_start_pieces, $va_end_pieces) = $this->_nullDaysAndMonthsForDisplay($va_start_pieces, $va_end_pieces);
						
						$vs_start_date = $this->_dateToText($va_start_pieces, $pa_options);
						$vs_end_date = $this->_dateToText($va_end_pieces, $pa_options);
						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_start_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_circa.$vs_end_date;
					} else {
						// full dates with times
						$vs_start_date = $this->_dateTimeToText($va_start_pieces, $pa_options);
						$vs_end_date = $this->_dateTimeToText($va_end_pieces, $pa_options);
						return ($vs_range_preconjunction ? $vs_range_preconjunction.' ': $vs_start_circa).$vs_start_date.' '.$vs_range_conjunction.' '.$vs_end_circa.$vs_end_date;
					}
				}
			}
		} else {
			return '';
		}
	}
	# -------------------------------------------------------------------
	private function _nullDaysAndMonthsForDisplay($pa_start_pieces, $pa_end_pieces) {
		if (((int)$pa_start_pieces['day'] === 1) && ((int)$pa_end_pieces['day'] === (int)$this->daysInMonth($pa_end_pieces['month'], $pa_end_pieces['year']))) {
			$pa_start_pieces['day'] = null;
		}
		if (
			(((int)$pa_end_pieces['day'] === (int)$this->daysInMonth(12, $pa_end_pieces['year'])) && ((int)$pa_end_pieces['month'] === 12))
			&&
			(((int)$pa_start_pieces['day'] === 1) && ((int)$pa_start_pieces['month'] === 1))
		) {
			$pa_end_pieces['day'] = $pa_end_pieces['month'] = null;
		}
		if (
			(((int)$pa_start_pieces['day'] === 1) && ((int)$pa_start_pieces['month'] === 1))
			&& 
			(((int)$pa_end_pieces['day'] === 31) && ((int)$pa_end_pieces['month'] === 12))
		) {
			$pa_start_pieces['day'] = $pa_start_pieces['month'] = null;
		}
		
		if (($pa_start_pieces['day'] === null) && ((int)$pa_end_pieces['day'] === (int)$this->daysInMonth($pa_end_pieces['month'], $pa_end_pieces['year']))) {
			$pa_end_pieces['day'] = null;
		}
		
		return [$pa_start_pieces, $pa_end_pieces];
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
			return true;
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
		
		if (isset($pa_options['timeDelimiter']) && in_array($pa_options['timeDelimiter'], $va_time_delimiters)) {
			$vs_time_delim = $pa_options['timeDelimiter'];
		} else {
			$vs_time_delim = $va_time_delimiters[0];
		}
		
		$vn_hours = floor($pn_seconds/3600);
		$pn_seconds -= ($vn_hours * 3600);
		$vn_minutes = floor($pn_seconds/60);
		$pn_seconds -= ($vn_minutes * 60);
		$vn_seconds = $pn_seconds;
		
		if (($pa_options['timeFormat'] ?? null) == 12) {
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
			
			if (($pa_options['timeOmitSeconds'] ?? false) || ($vn_seconds == 0)) {
				$vs_text = join($vs_time_delim, array($vn_hours, sprintf('%02d', $vn_minutes))).' '.$vs_meridian;		
			} else {
				$vs_text = join($vs_time_delim, array($vn_hours, sprintf('%02d', $vn_minutes), sprintf('%02d', $vn_seconds))).' '.$vs_meridian;
			}
		} else {
			if (($pa_options['timeOmitSeconds'] ?? false) || ($vn_seconds == 0)) {
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
		foreach(array('dateFormat', 'dateDelimiter', 'uncertaintyIndicator', 'showADEra', 'forceCommaAfterDay') as $vs_opt) {
			if (!isset($pa_options[$vs_opt]) && ($vs_opt_val = $this->opo_datetime_settings->get($vs_opt))) {
				$pa_options[$vs_opt] = $vs_opt_val;
			}
		}
		
		$vs_year = null;
		
		if ($pa_date_pieces['is_bp'] ?? null) {
			$va_bp_indicators = $this->opo_language_settings->getList("dateBP");
			return (1950 - $pa_date_pieces['year']).' '.$va_bp_indicators[0];
		}
	
		$va_uncertainty_indicators = $this->opo_language_settings->getList("dateUncertaintyIndicator");
		if (isset($pa_options['uncertaintyIndicator']) && in_array($pa_options['uncertaintyIndicator'], $va_uncertainty_indicators)) {
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
		
		$vs_month = null;
		if (($pa_date_pieces['month'] ?? null) > 0) {		// date with month
			if (in_array((string)$pa_options['dateFormat'], ['delimited', 'ymd'], true)) {
				$vs_month = $pa_date_pieces['month'];
			} else {
				$va_months = $this->getMonthList();
				$vs_month = $va_months[$pa_date_pieces['month'] - 1];
			}
		} 	
		$vs_day = null;
		if (($pa_date_pieces['day'] ?? null) > 0) {		// date with day
			$vs_day = $pa_date_pieces['day'];
		}	
		
		if ($pa_date_pieces['year'] ?? null) {
			if ($pa_date_pieces['year'] < 0) {		// date with year
				$vs_year = abs($pa_date_pieces['year']).' '.$pa_date_pieces['era'];
			} else {
				$vs_year = $pa_date_pieces['year'];
				if ($pa_options['showADEra'] ?? null) {
					$vs_year .= ' '.$pa_date_pieces['era'];
				}
			}
					
			if ($pa_date_pieces['uncertainty_units'] && $pa_date_pieces['uncertainty']) {
				$vs_year .= ' '.$vs_uncertainty_indicator.' '.$pa_date_pieces['uncertainty'].$pa_date_pieces['uncertainty_units'];
			}
		}
		
		$va_date = array();
		
		if (is_null($vb_month_comes_first = $this->opo_datetime_settings->get('monthComesFirstInDelimitedDate'))) {
		    $vb_month_comes_first = $this->opo_language_settings->get('monthComesFirstInDelimitedDate');
		}
		
		if (($vs_day > 0) && ($pa_options['dateFormat'] != 'delimited') && ($vs_day_suffix = $this->opo_language_settings->get('daySuffix'))){
			// add day suffix
			$vs_day .= $vs_day_suffix;
		}
		
		if($pa_options['dateFormat'] === 'ymd') {
			$va_date[] = $vs_year;
			if ($vs_month) { $va_date[] = sprintf("%02d", $vs_month); }
			if ($vs_day) { $va_date[] =  sprintf("%02d", $vs_day); }
			return join($vs_date_delimiter, $va_date);
		} elseif ($vb_month_comes_first) {
			if ($vs_month) { $va_date[] = (($pa_options['dateFormat'] == 'delimited') ? sprintf("%02d", $vs_month) : $vs_month); }
			if ($vs_day) { 
				if (
					(((bool)($pa_options['showCommaAfterDayForTextDates'] ?? false)) && ($pa_options['dateFormat'] == 'text') && $vs_year)
					||
					(isset($pa_options['forceCommaAfterDay']) && $pa_options['forceCommaAfterDay'])
				)
				{
					if ($vs_year) { $vs_day .= ","; }
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
		
		if (!($pa_options['timeOmit'] ?? false)) {
			$vn_seconds = ((int)$pa_date_pieces['hours'] * 3600) + ((int)$pa_date_pieces['minutes'] * 60) + (int)$pa_date_pieces['seconds'];
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
		if (file_exists(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser/'.$ps_iso_code.'.lang')) {
			$this->ops_language = $ps_iso_code;
			$this->opo_language_settings = Configuration::load(__CA_LIB_DIR__.'/Parsers/TimeExpressionParser/'.$ps_iso_code.'.lang');
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
	/**
	 * Returns a Configuration object with the date/time localization  
	 * settings for the specified locale
	 *
	 * @param string $ps_iso_code ISO code (ex. en_US) 
	 * @return Configuration Settings for the specified locale or null if the locale is not defined.
	 */
	static public function getSettingsForLanguage($ps_iso_code) {
		global $g_tep_lang_settings;
		if(isset($g_tep_lang_settings[$ps_iso_code])) { return $g_tep_lang_settings[$ps_iso_code]; }
		
		$vs_config_path = __CA_LIB_DIR__.'/Parsers/TimeExpressionParser/'.$ps_iso_code.'.lang';
		if(!file_exists($vs_config_path)) { return $g_tep_lang_settings[$ps_iso_code] = null; }
		
		return $g_tep_lang_settings[$ps_iso_code] = Configuration::load($vs_config_path);
	}
	# -------------------------------------------------------------------
	private function getLanguageSettingsWordList($ps_key) {
		if (TimeExpressionParser::$s_language_settings_list_cache[$this->ops_language][$ps_key] ?? null) { return TimeExpressionParser::$s_language_settings_list_cache[$this->ops_language][$ps_key]; }
		
		$va_values = $this->opo_language_settings->getList($ps_key);
		$va_list_lc = is_array($va_values) ? array_map('mb_strtolower', $va_values) : [];
		return TimeExpressionParser::$s_language_settings_list_cache[$this->ops_language][$ps_key] = $va_list_lc;
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
			$va_tmp = $this->gmgetdate();
			$pn_year = $va_tmp['year'];
		} else {
			if (preg_match('!^[\?]+$!', $pn_year)) { $pn_year = 0; }
		}
		return date("t", mktime(0, 0, 0, $pn_month, 1, $pn_year));
	}
	# -------------------------------------------------------------------
	public function daysInYear($year) {
		return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0))) ? 366 : 365;
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
		
		$vn_year = (int)$va_tmp[0];
		if ($vn_year < 0) {
			$vs_era = $this->opo_language_settings->get('dateBCIndicator');
			$vn_abs_year = abs($vn_year);
		} else {
			$vs_era = $this->opo_language_settings->get('dateADIndicator');
			$vn_abs_year = $vn_year;
		}
		$vn_attributes = (int)substr($va_tmp[1], 10, 1);
		$vb_is_circa = ($vn_attributes & 0b0001) ? 1 : 0;
		$vb_is_bp = ($vn_attributes & 0b1000) ? 1 : 0;
		if ($vb_is_probably = ($vn_attributes == 9) ? 1 : 0) {
		    $vb_is_circa = $vb_is_bp = false;   
		}
		
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
			'is_probably'		=> $vb_is_probably,
			'is_bp'				=> $vb_is_bp,
			'uncertainty'		=> $vn_uncertainty,
			'uncertainty_units'	=> $vs_uncertainty_units
			
		);
		
		return $va_parts;
	}
	# -------------------------------------------------------------------
	public function unixToHistoricTimestamp($pn_unix_timestamp) {
		$va_date_info = $this->gmgetdate($pn_unix_timestamp);
		return $va_date_info['year'].".".sprintf('%02d',$va_date_info['mon']).sprintf('%02d',$va_date_info['mday']).sprintf('%02d',$va_date_info['hours']).sprintf('%02d',$va_date_info['minutes']).sprintf('%02d',$va_date_info['seconds']).'00'; 
	}
	# -------------------------------------------------------------------
	public function historicToUnixTimestamp($pn_historic_timestamp) {
	    $year = (int)$pn_historic_timestamp;
	    $tmp = explode('.', $pn_historic_timestamp);
	    $month = substr($tmp[1], 0, 2);
	    $day = substr($tmp[1], 2, 2);
	    return strtotime("{$year}-{$month}-{$day}");
	}
	# -------------------------------------------------------------------
	public function setDebug($pn_debug) {
		$this->opb_debug = ($pn_debug) ? true: false;
	}
	# -------------------------------------------------------------------
	/**
	 * Test whether a date range (passed as arrays of date pieces) is a century, decade, year, month or day interval
	 *
	 * @param array $pa_start_pieces
	 * @param array $pa_end_pieces
	 *
	 * @return string CENTURY|DECADE|YEAR|MONTH|DAY if interval; false is not interval
	 */
	public function isDMYRange($pa_start_pieces, $pa_end_pieces) {
		if (
			($pa_start_pieces['year'] % 100) == 0 && ($pa_end_pieces['year'] == ($pa_start_pieces['year'] + 99))  &&
			$pa_start_pieces['day'] == 1 && $pa_start_pieces['month'] == 1 &&
			$pa_start_pieces['hours'] == 0 && $pa_start_pieces['minutes'] == 0 && $pa_start_pieces['seconds'] == 0 &&
			$pa_end_pieces['day'] == 31 && $pa_end_pieces['month'] == 12 &&
			$pa_end_pieces['hours'] == 23 && $pa_end_pieces['minutes'] == 59 && $pa_end_pieces['seconds'] == 59
		) {
			return 'CENTURY';
		}
		if (
			($pa_start_pieces['year'] % 10) == 0 && ($pa_end_pieces['year'] == ($pa_start_pieces['year'] + 9))  &&
			$pa_start_pieces['day'] == 1 && $pa_start_pieces['month'] == 1 &&
			$pa_start_pieces['hours'] == 0 && $pa_start_pieces['minutes'] == 0 && $pa_start_pieces['seconds'] == 0 &&
			$pa_end_pieces['day'] == 31 && $pa_end_pieces['month'] == 12 &&
			$pa_end_pieces['hours'] == 23 && $pa_end_pieces['minutes'] == 59 && $pa_end_pieces['seconds'] == 59
		) {
			return 'DECADE';
		}
		if (
			$pa_start_pieces['year'] == $pa_end_pieces['year']  &&
			$pa_start_pieces['day'] == 1 && $pa_start_pieces['month'] == 1 &&
			$pa_start_pieces['hours'] == 0 && $pa_start_pieces['minutes'] == 0 && $pa_start_pieces['seconds'] == 0 &&
			$pa_end_pieces['day'] == 31 && $pa_end_pieces['month'] == 12 &&
			$pa_end_pieces['hours'] == 23 && $pa_end_pieces['minutes'] == 59 && $pa_end_pieces['seconds'] == 59
		) {
			return 'YEAR';
		}
		
		if (
			$pa_start_pieces['year'] == $pa_end_pieces['year']  &&
			$pa_start_pieces['month'] == $pa_end_pieces['month']  &&
			$pa_start_pieces['day'] == 1 && ($pa_end_pieces['day'] == $this->daysInMonth($pa_end_pieces['month'], $pa_end_pieces['year'])) &&
			$pa_start_pieces['hours'] == 0 && $pa_start_pieces['minutes'] == 0 && $pa_start_pieces['seconds'] == 0 &&
			$pa_end_pieces['hours'] == 23 && $pa_end_pieces['minutes'] == 59 && $pa_end_pieces['seconds'] == 59
		) {
			return 'MONTH';
		}
		
		if (
			$pa_start_pieces['year'] == $pa_end_pieces['year']  &&
			$pa_start_pieces['month'] == $pa_end_pieces['month']  &&
			$pa_start_pieces['day'] == $pa_end_pieces['day'] &&
			$pa_start_pieces['hours'] == 0 && $pa_start_pieces['minutes'] == 0 && $pa_start_pieces['seconds'] == 0 &&
			$pa_end_pieces['hours'] == 23 && $pa_end_pieces['minutes'] == 59 && $pa_end_pieces['seconds'] == 59
		) {
			return 'DAY';
		}
		
		return false;
	}
	# -------------------------------------------------------------------
	/**
	 * Convert date value arrays (array with keys "month", "day", "year", "hours", "minutes", "seconds" used internally by parser) to ISO 8601 date/time range.
	 *
	 * @param array $pa_start_date
	 * @param array $pa_end_date
	 * @param array $pa_options Options include:
	 *		timeOmit = Omit time from returned ISO 8601 date. [Default is false]
	 *		returnUnbounded = Return extreme value for unbounded dates. For "before" dates the start date would be equal to -9999; for "after" dates the end date would equal "9999". [Default is false]
	 *		dateFormat = If set to "yearOnly" will return bare year. [Default is null]
	 * @return string
	 */
	public function getISODateRange($pa_start_date, $pa_end_date, $pa_options=null) {
		if(($pa_start_date['day'] == 1) && ($pa_start_date['month'] == 1) && ($pa_end_date['day'] == 31) && ($pa_end_date['month'] == 12)) {
			$start = $this->getISODateTime($pa_start_date, 'START', $pa_options);
			$end = $this->getISODateTime($pa_end_date, 'END', $pa_options);
		} else {
			$start = $this->getISODateTime($pa_start_date, 'FULL', $pa_options);
			$end = $this->getISODateTime($pa_end_date, 'FULL', $pa_options);
		}
		
		switch($this->isDMYRange($pa_start_date, $pa_end_date)) {
			case 'DAY':
				return $this->getISODateTime($pa_start_date, 'FULL', array_merge($pa_options, ['timeOmit' => true]));
			case 'MONTH':
				return $this->getISODateTime($pa_start_date, 'FULL', array_merge($pa_options, ['timeOmit' => true])).'/'.$this->getISODateTime($pa_end_date, 'FULL', array_merge($pa_options, ['timeOmit' => true]));
		}
		if ($start === $end) { return $start; }
		return "{$start}/{$end}";
	}
	# -------------------------------------------------------------------
	/**
	 * Convert date value array (array with keys "month", "day", "year", "hours", "minutes", "seconds" used internally by parser) to ISO 8601 date/time expression.
	 *
	 * @param array $pa_date
	 * @param string $ps_mode Part of date range to return. Valid values are "START" (beginning of range) "END" (end of range) and "FULL" (full range). [Default is "START"]
	 * @param array $pa_options Options include:
	 *		timeOmit = Omit time from returned ISO 8601 date. [Default is false]
	 *		returnUnbounded = Return extreme value for unbounded dates. For "before" dates the start date would be equal to -9999; for "after" dates the end date would equal "9999". [Default is false]
	 *		dateFormat = If set to "yearOnly" will return bare year; if set to 'xsd' BCE years are returned relative to zero. [Default is null]
	 *		full = Always return full date [Default is false]
	 * @return string
	 */
	public function getISODateTime($pa_date, $ps_mode='START', $pa_options=null) {
		if ((isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'yearOnly'))) { return $pa_date['year']; }
		if (!$pa_date['month']) { $pa_date['month'] = ($ps_mode == 'END') ? 12 : 1; }
		if (!$pa_date['day']) { $pa_date['day'] = ($ps_mode == 'END') ? 31 : 1; }
		
		if (($pa_date['year'] == TEP_END_OF_UNIVERSE) && !caGetOption('returnUnbounded', $pa_options, false)) { return ''; }
		if ($pa_date['year'] == TEP_START_OF_UNIVERSE && !caGetOption('returnUnbounded', $pa_options, false)) { return ''; }
		
		if((isset($pa_options['dateFormat']) && ($pa_options['dateFormat'] == 'xsd')) && ($pa_date['year'] < 0)) { $pa_date['year']++; }
		
		$pa_date['year'] = sprintf(($pa_date['year'] < 0) ? "%05d" : "%04d", $pa_date['year']);
		 
		if ($ps_mode == 'FULL') {
			$vs_date = $pa_date['year'].'-'.sprintf("%02d", $pa_date['month']).'-'.sprintf("%02d", $pa_date['day']);
			
			if (!isset($pa_options['timeOmit']) || !$pa_options['timeOmit']) {
				$vs_date .= 'T'.sprintf("%02d", $pa_date['hours']).':'.sprintf("%02d", $pa_date['minutes']).':'.sprintf("%02d", $pa_date['seconds']).'Z';
			}
			return $vs_date;
		}
		
		$vs_time = '';
		if (!isset($pa_options['timeOmit']) || !$pa_options['timeOmit']) {
			if (
				(!($pa_date['hours'] == 0 && $pa_date['minutes'] == 0 && $pa_date['seconds'] == 0 && ($ps_mode == 'START'))) &&
				(!($pa_date['hours'] == 23 && $pa_date['minutes'] == 59 && $pa_date['seconds'] == 59 && ($ps_mode == 'END')))
			) {
				$vs_time .= 'T'.sprintf("%02d", $pa_date['hours']).':'.sprintf("%02d", $pa_date['minutes']).':'.sprintf("%02d", $pa_date['seconds']).'Z';
			}
		}
		
		if (
			((!($pa_date['month'] == 1 && $pa_date['day'] == 1 && ($ps_mode == 'START'))) &&
			(!($pa_date['month'] == 12 && $pa_date['day'] == 31 && ($ps_mode == 'END')))) || $vs_time || caGetOption('full', $pa_options, $this->opo_datetime_settings->get('alwaysUseFullISODates'))
			
		) {
			$vs_date = $pa_date['year'].'-'.sprintf("%02d", $pa_date['month']).'-'.sprintf("%02d", $pa_date['day']);
		} else {
			$vs_date = $pa_date['year'];
		}
		
		$vs_date .= $vs_time;
		
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
				
				if (($vn_s <= TEP_START_OF_UNIVERSE) || ($vn_e >= TEP_END_OF_UNIVERSE) || ($vn_s == 0) || ($vn_e == 0)) { break; }
				
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
				
				if (($vn_s <= TEP_START_OF_UNIVERSE) || ($vn_e >= TEP_END_OF_UNIVERSE) || ($vn_s == 0) || ($vn_e == 0)) { break; }
				
				$vn_s = intval($vn_s/100) * 100;
				$vn_e = intval($vn_e/100) * 100;
				
				if ($vn_s <= $vn_e) {
					$va_century_indicators = 	$this->opo_language_settings->getList("centuryIndicator");
					$va_ordinals = 				$this->opo_language_settings->getList("ordinalSuffixes");
					$vs_ordinal_default = 		$this->opo_language_settings->get("ordinalSuffixDefault");
					$vs_bc_indicator = 			$this->opo_language_settings->get("dateBCIndicator");
					$va_ordinal_exceptions =	$this->opo_language_settings->get("ordinalSuffixExceptions");

					for($vn_y=$vn_s; $vn_y <= $vn_e; $vn_y+= 100) {

						$vn_century_num = ($vn_y >= 0) ? abs(floor($vn_y/100)) + 1 : abs(floor($vn_y/100));
						if ($vn_century_num == 0)  { continue; }
						$vn_x = substr((string)$vn_century_num, strlen($vn_century_num) - 1, 1);

						if(is_array($va_ordinal_exceptions) && isset($va_ordinal_exceptions[$vn_century_num])) {
							$vs_ordinal_to_display = $va_ordinal_exceptions[$vn_century_num];
						} else {
							$vs_ordinal_to_display = isset($va_ordinals[$vn_x]) ? $va_ordinals[$vn_x] : $vs_ordinal_default;
						}

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
				
				$vs_bc_indicator = $this->opo_language_settings->get("dateBCIndicator");
				if ($vn_s <= $vn_e) {
					for($y=$vn_s; $y <= $vn_e; $y++) {
						if ($y == 0) { continue; }
						if ($y < 0) {
							$va_values[(int)$y] = abs($y).' '.$vs_bc_indicator;
						} else {
							$va_values[(int)$y] = $y;
						}
					}
				}
				break;
		}

		return $va_values;
	}
	# -------------------------------------------------------------------
	/**
	 * Return current date/time 
	 *
	 * @param array $pa_options Options include:
	 *		returnAs = time units of return value. Valid values are: days, hours, minutes, seconds. [Default is seconds]
	 * @return int Time in interval
	 */
	public function interval($pa_options=null) {
		if (!$this->opn_start_historic || !$this->opn_end_historic) { return null; }
		if ($this->opn_start_historic > $this->opn_end_historic) { return null; }
		$va_start = $this->getHistoricDateParts($this->opn_start_historic);
		$va_end = $this->getHistoricDateParts($this->opn_end_historic);
		
		$vo_start = new DateTime($this->getISODateTime($va_start, 'FULL'));
		$vo_end = new DateTime($this->getISODateTime($va_end, 'FULL'));
		
		$vo_interval = $vo_start->diff($vo_end);
		
		switch(strtolower(caGetOption('returnAs', $pa_options, 'seconds'))) {
			case 'days':
				return $vo_interval->days + (($vo_interval->h >= 12) ? 1 : 0);
				break;
			case 'hours':
				return ($vo_interval->days * 24 * 60 * 60) + ($vo_interval->h * 60 * 60) + (($vo_interval->m >= 30) ? 1 : 0);
				break;
			case 'minutes':
				return ($vo_interval->days * 24 * 60 * 60) + ($vo_interval->h * 60 * 60) + ($vo_interval->m * 60) + (($vo_interval->s >= 30) ? 1 : 0);
				break;
			case 'seconds':
			default:
				return ($vo_interval->days * 24 * 60 * 60) + ($vo_interval->h * 60 * 60) + ($vo_interval->m * 60) + $vo_interval->s;
				break;
		}
		return null;
	}
	# -------------------------------------------------------------------
	/**
	 * Timezone-less version of getDate()
	 */
	public function gmgetdate($ts = null){ 
        $k = array('seconds','minutes','hours','mday', 
                'wday','mon','year','yday','weekday','month',0);
        return(array_combine($k,explode(":",
                date('s:i:G:j:w:n:Y:z:l:F:U',is_null($ts)?time():intval($ts)))));
    } 
 	# -------------------------------------------------------------------
	/**
	 * Return current date/time 
	 *
	 * @param array $pa_options Options include:
	 *		format = format of return value. Options are:
	 *				unix = Unix-timestamp
	 *				historic = Historic timestamp 
	 *				[Default is historic]
	 *				
	 */
	public static function now($pa_options=null) {
		$ps_format = caGetOption('format', $pa_options, null, ['toLowerCase' => true]);
		$o_tep = new TimeExpressionParser();
		$o_tep->parse(__TEP_NOW__);
		
		switch($ps_format) {
			case 'unix':
				return array_shift($o_tep->getUnixTimestamps());
				break;
			case 'historic':
			default:
				return array_shift($o_tep->getHistoricTimestamps());
				break;
		}
		return null;
	}
	# -------------------------------------------------------------------
	/**
	 * Transform start/end year of a range based upon a decade or century modifier.
	 *
	 * @param string $qualifier A TEP_TOKEN_* constant for the qualifier to apply. Possible values are TEP_TOKEN_EARLY, TEP_TOKEN_MID or TEP_TOKEN_LATE.
	 * @param string $state_or_end The end of the date range to qualify. Possible values are "start" or "end".
	 * @param string $range_type Type of date range being qualified. Possible values are "decade" or "century".
	 *
	 * @return int The year modified according to the qualifier.				
	 */
	public static function applyPartOfRangeQualifier($qualifier, $start_or_end, $range_type, $year) {
		$qualifier = strtolower($qualifier);	
		if (!in_array($qualifier, [TEP_TOKEN_EARLY, TEP_TOKEN_MID, TEP_TOKEN_LATE])) { return $year; }	
		$start_or_end = strtolower($start_or_end);
		$range_type = strtolower($range_type);
		if (!isset(self::$early_mid_late_range_intervals[$range_type])) { return $year; }
		$l = self::$early_mid_late_range_intervals[$range_type]; 
		$w = self::$early_mid_late_range_lengths[$range_type];
		
		$ret = null;
		
		if ($year >= 0) {
            if ($start_or_end == 'start') {
                switch($qualifier) {
                    case TEP_TOKEN_EARLY:
                        $ret = $year;
                        break;
                    case TEP_TOKEN_MID:
                        $ret = $year + floor($l/2) - floor($w/2);
                        break;
                    case TEP_TOKEN_LATE:
                        $ret = $year + $l - $w;
                        break;
                }
            } else {
                switch($qualifier) {
                    case TEP_TOKEN_EARLY:
                        $ret = $year - ($l - 1) + $w;
                        break;
                    case TEP_TOKEN_MID:
                        $ret = $year - ($l - 1) + floor($l/2) + floor($w/2);
                        break;
                    case TEP_TOKEN_LATE:
                        $ret = $year;
                        break;
                }
            } 
        } else {
            if ($start_or_end == 'start') {
                switch($qualifier) {
                    case TEP_TOKEN_EARLY:
                        $ret = $year + $w;
                        break;
                    case TEP_TOKEN_MID:
                        $ret = $year + floor($l/2) - floor($w/2);
                        break;
                    case TEP_TOKEN_LATE:
                        $ret = $year + $l - $w - 1;
                        break;
                }
            } else {
                switch($qualifier) {
                    case TEP_TOKEN_EARLY:
                        $ret = $year - $w;
                        break;
                    case TEP_TOKEN_MID:
                        $ret = $year - ($l - 1) + floor($l/2) + floor($w/2);
                        break;
                    case TEP_TOKEN_LATE:
                        $ret = $year - $w;
                        break;
                }
            }
        }

		return $ret;
	}
	# -------------------------------------------------------------------
	/**
	 * 
	 * @return 	
	 */
	public static function inferRangeQualifier($dates, $options=null) {
		if(!isset($dates['start']) || !is_array($start_pieces = $dates['start'])) { return null; }
		if(!isset($dates['end']) || !is_array($end_pieces = $dates['end'])) { return null; }
		
		if(!is_numeric($start_pieces['year']) || !is_numeric($end_pieces['year'])) { return null; }
		if (($start_pieces['hours'] != 0) || ($start_pieces['minutes'] != 0) || ($start_pieces['seconds'] != 0) ||
			($start_pieces['day'] != 1) || ($start_pieces['month'] != 1)) {
			return false;	
		}
		if (($end_pieces['hours'] != 23) || ($end_pieces['minutes'] != 59) || ($end_pieces['seconds'] != 59) ||
			($end_pieces['day'] != 31) || ($end_pieces['month'] != 12)) {
			return false;	
		}
		$o_tep = new TimeExpressionParser();
		$early_qualifiers = $o_tep->opo_language_settings->getList("earlyQualifier");
		$mid_qualifiers = $o_tep->opo_language_settings->getList("midQualifier");
		$late_qualifiers = $o_tep->opo_language_settings->getList("lateQualifier");
		
		// Early century
		if ((($start_pieces['year'] % 100) == 0) && ($end_pieces['year'] == ($start_pieces['year'] + self::$early_mid_late_range_lengths['century']))) {
			return ['qualifier' => TEP_TOKEN_EARLY, 'range_type' => 'century', 'value' => $early_qualifiers[0].' '.$o_tep->makeCenturyString($dates, $options)];
		}
		
		// Early decade
		if ((($start_pieces['year'] % 10) == 0) && ($end_pieces['year'] == ($start_pieces['year'] + self::$early_mid_late_range_lengths['decade']))) {
			return ['qualifier' => TEP_TOKEN_EARLY, 'range_type' => 'decade', 'value' => $early_qualifiers[0].' '.$o_tep->makeDecadeString($dates, $options)];
		}
		
		// Late century
		if (((($end_pieces['year'] - 99) % 100) == 0) && ($start_pieces['year'] == ($end_pieces['year'] - (self::$early_mid_late_range_lengths['century'] - 1)))) {
			return ['qualifier' => TEP_TOKEN_LATE, 'range_type' => 'century', 'value' => $late_qualifiers[0].' '.$o_tep->makeCenturyString($dates, $options)];
		}
		
		// Late decade
		if (((($end_pieces['year'] - 9) % 10) == 0) && ($start_pieces['year'] == ($end_pieces['year'] - (self::$early_mid_late_range_lengths['decade'] - 1)))) {
			return ['qualifier' => TEP_TOKEN_LATE, 'range_type' => 'century', 'value' => $late_qualifiers[0].' '.$o_tep->makeDecadeString($dates, $options)];
		}
		
		// Mid century
		if (((($start_pieces['year'] - floor(self::$early_mid_late_range_intervals['century']/2) + floor(self::$early_mid_late_range_lengths['century']/2)) % 100) == 0) && ($end_pieces['year'] == ($start_pieces['year'] + self::$early_mid_late_range_lengths['century']))) {
			return ['qualifier' => TEP_TOKEN_MID, 'range_type' => 'century', 'value' => $mid_qualifiers[0].' '.$o_tep->makeCenturyString($dates, $options)];
		}
		
		// Mid decade
		if (!$options['isSpan'] && ((($start_pieces['year'] - floor(self::$early_mid_late_range_intervals['decade']/2) + floor(self::$early_mid_late_range_lengths['decade']/2)) % 10) == 0) && ($end_pieces['year'] == ($start_pieces['year'] + self::$early_mid_late_range_lengths['decade']))) {
 			return ['qualifier' => TEP_TOKEN_MID, 'range_type' => 'decade', 'value' => $mid_qualifiers[0].' '.$o_tep->makeDecadeString($dates, $options)];
 		}
		
		
		// Does it span centuries?
		$mod_start = $start_pieces;
		$mod_end = $end_pieces;
		$second_century_info = null;
		if ((($end_pieces['year'] - ($end_pieces['year'] % 100)) - ($start_pieces['year'] - ($start_pieces['year'] % 100))) >= 100) {
			$options['isSpan'] = true;
			
			// first century
			if (($start_pieces['year'] % 100) == 0) { // early
				$mod_end['year'] =  $start_pieces['year'] + self::$early_mid_late_range_lengths['century'];
				$first_century_info = self::inferRangeQualifier(['start' => $start_pieces, 'end' => $mod_end], $options);
			} elseif(($start_pieces['year'] % 100) == (self::$early_mid_late_range_intervals['century'] - self::$early_mid_late_range_lengths['century'])) { // late
				$mod_end['year'] =  $start_pieces['year'] + self::$early_mid_late_range_lengths['century'] - 1;
				$first_century_info = self::inferRangeQualifier(['start' => $start_pieces, 'end' => $mod_end], $options);
			} elseif((($start_pieces['year'] + floor(self::$early_mid_late_range_lengths['century']/2) - floor(self::$early_mid_late_range_intervals['century']/2)) % 100) == 0) { // mid
				$mod_end['year'] =  $start_pieces['year'] + self::$early_mid_late_range_lengths['century'];
				$first_century_info = self::inferRangeQualifier(['start' => $start_pieces, 'end' => $mod_end], $options);
			}
			
			// second century
			if (($end_pieces['year'] % 100) == self::$early_mid_late_range_lengths['century']) { // early
				$mod_start['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 100);
				$mod_end['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 100);
				$second_century_info = self::inferRangeQualifier(['start' => $mod_start, 'end' => $end_pieces], $options);
			} elseif((($end_pieces['year'] + 1) % 100) == 0) { // late
				$mod_start['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 100) + self::$early_mid_late_range_intervals['century'] - self::$early_mid_late_range_lengths['century'];
				$second_century_info = self::inferRangeQualifier(['start' => $mod_start, 'end' => $end_pieces], $options);
			} elseif((($end_pieces['year'] - floor(self::$early_mid_late_range_lengths['century']/2) - floor(self::$early_mid_late_range_intervals['century']/2)) % 100) == 0) { // mid
				$mod_start['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 100) - floor(self::$early_mid_late_range_lengths['century']/2) + floor(self::$early_mid_late_range_intervals['century']/2);
				$second_century_info = self::inferRangeQualifier(['start' => $mod_start, 'end' => $end_pieces], $options);
			}
			
			if (is_array($first_century_info) && is_array($second_century_info) && !(($first_century_info['qualifier'] == TEP_TOKEN_EARLY) && ($second_century_info['qualifier'] == TEP_TOKEN_LATE))) {
				return ['qualifier' => null, 'range_type' => 'century', 'value' => $first_century_info['value']." - ".$second_century_info['value']];
			}
		}
		
		// Does it span decades?
		$mod_start = $start_pieces;
		$mod_end = $end_pieces;
		$second_decade_info = null;
		if ((($end_pieces['year'] - ($end_pieces['year'] % 10)) - ($start_pieces['year'] - ($start_pieces['year'] % 10))) >= 10) {
			$options['isSpan'] = true;
			
			// first decade
			if (($start_pieces['year'] % 10) == 0) { // early
				$mod_end['year'] =  $start_pieces['year'] + self::$early_mid_late_range_lengths['decade'];
				$first_decade_info = self::inferRangeQualifier(['start' => $start_pieces, 'end' => $mod_end], $options);
			} elseif(($start_pieces['year'] % 10) == (self::$early_mid_late_range_intervals['decade'] - self::$early_mid_late_range_lengths['decade'])) { // late
				$mod_end['year'] =  $start_pieces['year'] + self::$early_mid_late_range_lengths['decade'] - 1;
				$first_decade_info = self::inferRangeQualifier(['start' => $start_pieces, 'end' => $mod_end], $options);
			} elseif((($start_pieces['year'] + floor(self::$early_mid_late_range_lengths['decade']/2) - floor(self::$early_mid_late_range_intervals['decade']/2)) % 10) == 0) { // mid
				$mod_end['year'] =  $start_pieces['year'] + self::$early_mid_late_range_lengths['decade'];
				$first_decade_info = self::inferRangeQualifier(['start' => $start_pieces, 'end' => $mod_end], $options);
			}
			
			// second decade
			if (($end_pieces['year'] % 10) == self::$early_mid_late_range_lengths['decade']) { // early
				$mod_start['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 10);
				$mod_end['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 10);
				$second_decade_info = self::inferRangeQualifier(['start' => $mod_start, 'end' => $end_pieces], $options);
			} elseif((($end_pieces['year'] + 1) % 10) == 0) { // late
				$mod_start['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 10) + self::$early_mid_late_range_intervals['decade'] - self::$early_mid_late_range_lengths['decade'];
				$second_decade_info = self::inferRangeQualifier(['start' => $mod_start, 'end' => $end_pieces], $options);
			} elseif((($end_pieces['year'] - floor(self::$early_mid_late_range_lengths['decade']/2) - floor(self::$early_mid_late_range_intervals['decade']/2)) % 10) == 0) { // mid
				$mod_start['year'] =  $end_pieces['year'] - ($end_pieces['year'] % 10) - floor(self::$early_mid_late_range_lengths['decade']/2) + floor(self::$early_mid_late_range_intervals['decade']/2);
				$second_decade_info = self::inferRangeQualifier(['start' => $mod_start, 'end' => $end_pieces], $options);
			}
			
			if (is_array($first_decade_info) && is_array($second_decade_info)  && !(($first_decade_info['qualifier'] == TEP_TOKEN_EARLY) && ($second_decade_info['qualifier'] == TEP_TOKEN_LATE))) {
				return ['qualifier' => null, 'range_type' => 'decade', 'value' => $first_decade_info['value']." - ".$second_decade_info['value']];
			}
		}

		return null;
	}
 	# -------------------------------------------------------------------
 	/** 
 	 *
 	 */
 	public function makeCenturyString($dates, $options) {
		if(!isset($dates['start']) || !is_array($start_pieces = $dates['start'])) { return null; }
		if(!isset($dates['end']) || !is_array($end_pieces = $dates['end'])) { return null; }
		
 		$century = intval((int)$start_pieces['year']/100);
		$century = ((int)$end_pieces['year'] > 0) ? ((int)$century + 1) : ((int)$century - 1);
		
		$ordinals = $this->opo_language_settings->getList("ordinalSuffixes");
		$ordinal_exceptions = $this->opo_language_settings->get("ordinalSuffixExceptions");
		$ordinal_default = $this->opo_language_settings->get("ordinalSuffixDefault");

		$x = intval(substr((string)$century, -1));

		if(is_array($ordinal_exceptions) && isset($ordinal_exceptions[$century])) {
			$ordinal = $ordinal_exceptions[$century];
		} else {
			$ordinal = isset($ordinals[$x]) ? $ordinals[$x] : $ordinal_default;
		}

		$century_indicators = $this->opo_language_settings->getList("centuryIndicator");

		$era = ($century < 0) ? ' '.$this->opo_language_settings->get('dateBCIndicator') : '';

		// if useRomanNumeralsForCenturies is set in datetime.conf, 20th Century will be displayed as XXth Century
		if ($options["useRomanNumeralsForCenturies"] ?? false) {
			return caArabicRoman(abs($century)).$ordinal.' '.$century_indicators[0].$era;
		}

		return abs($century).$ordinal.' '.$century_indicators[0].$era;
 	}
 	# -------------------------------------------------------------------
 	/** 
 	 *
 	 */
 	public function makeDecadeString($dates, $options) {
		if(!isset($dates['start']) || !is_array($start_pieces = $dates['start'])) { return null; }
		if(!isset($dates['end']) || !is_array($end_pieces = $dates['end'])) { return null; }
		
		$decade_indicators = $this->opo_language_settings->getList("decadeIndicator");
		if(is_array($decade_indicators)){
			$decade_indicator = array_shift($decade_indicators);
		} else {
			$decade_indicator = "s";
		}
		return ($dates['start']['year'] - ($dates['start']['year'] % 10)).$decade_indicator;
 	}
 	# ------------------------------------------------------------------- 	
 	/**
 	 *
 	 * @param int $year
 	 * @return int
 	 */
 	public function windowYear(int $year) : int {
 		if(($year >= 0) && ($year <= 99)) {
 			$tmp = $this->gmgetdate();
			$current_year = intval(substr($tmp['year'], 2, 2));		// get last two digits of current year
			$current_century = intval(substr($tmp['year'], 0, 2)) * 100;
			
			if ($year <= ($current_year + 10)) {
				$year += $current_century;
			} else {
				$year += ($current_century - 100);
			}
 		}
 		return $year;
 	}
 	# ------------------------------------------------------------------- 	
 	/**
 	 *
 	 * @param int $year
 	 * @return int
 	 */
 	public function getTokensToConjunction() : array {
 		$toks = [];
 		$i = 1;
 		while($token = $this->peekToken($i)) {
 			if($token['type'] === TEP_TOKEN_RANGE_CONJUNCTION) { break; }
 			$toks[] = $token;
 			$i++;
 		}
 		return $toks;
 	}
 	# ------------------------------------------------------------------- 	
}
