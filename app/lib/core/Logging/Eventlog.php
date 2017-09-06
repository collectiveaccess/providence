<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Logging/Eventlog.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2012 Whirl-i-Gig
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
 * @subpackage Logging
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
include_once(__CA_LIB_DIR__."/core/Logging/BaseLogger.php");

# ----------------------------------------------------------------------
class Eventlog extends BaseLogger {
	# ----------------------------------------
  	private $opa_log_codes;
	# ----------------------------------------
	/** 
	 *
	 */
	public function __construct($pa_entry=null) {
		$o_config = Configuration::load();
		
		# system codes are: "LOGN" = "successful login"; "LOGF" = "failed login"; "SYS" = "system"; "QUE" = queue
		$va_codes = $o_config->getList("event_log_codes");
		if (!is_array($va_codes)) { $va_codes = array(); }
		$this->opa_log_codes = array_merge($va_codes,array("LOGN", "LOGF", "SYS", "DEBG", "QUE", "ERR"));
		
		parent::__construct();
	}
	# ----------------------------------------
	/** 
	 *
	 */
	public function isValidLoggingCode($ps_code) {
		return in_array($ps_code, $this->opa_log_codes);
	}
	# ----------------------------------------
	/** 
	 *
	 */
	public function log($pa_entry) {
		if (is_array($pa_entry)) {
			
			if (!$this->isValidLoggingCode($pa_entry["CODE"])) {
				return false;
			}
			if (!$pa_entry["SOURCE"]) {
				return false;
			}
			if (!$pa_entry["MESSAGE"]) {
				return false;
			}
			
			$this->o_db->query("
				INSERT INTO ca_eventlog 
				(date_time, code, message, source)
				VALUES
				(unix_timestamp(), ?, ?, ?)
			", $pa_entry["CODE"], $pa_entry["MESSAGE"], $pa_entry["SOURCE"]);
			
			return true;
		}
		return false;
	}
	# ----------------------------------------
	/** 
	 *
	 */
	public function search($ps_datetime_expression, $ps_code=null) {
		$o_tep = new TimeExpressionParser();
		
		if ($o_tep->parse($ps_datetime_expression)) {
			list($vn_period_start, $vn_period_end) = $o_tep->getUnixTimestamps();
		
			if ($vn_period_start && $vn_period_end) {
				$o_db = new Db();
				
				$qr_codes = $o_db->query("SELECT DISTINCT code FROM ca_eventlog ORDER BY code");
				$va_codes = array();
				while($qr_codes->nextRow()) {
					$va_codes[] = $qr_codes->get("code");
				}
				if (in_array($ps_code, $va_codes)) {
					$qr_log = $o_db->query("
						SELECT *
						FROM ca_eventlog
						WHERE 
							(date_time BETWEEN $vn_period_start AND $vn_period_end) 
							AND (code = ?)
						ORDER BY date_time DESC
					", $ps_code);
				} else {
					$qr_log = $o_db->query("
						SELECT *
						FROM ca_eventlog
						WHERE 
							(date_time BETWEEN $vn_period_start AND $vn_period_end)
						ORDER BY date_time DESC
					");
				}
				return $qr_log->getAllRows();
			}
		}
		return null;
	}
	# ----------------------------------------
}
?>