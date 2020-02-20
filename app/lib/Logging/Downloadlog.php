<?php
/** ---------------------------------------------------------------------
 * app/lib/Logging/Downloadlog.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2019 Whirl-i-Gig
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
 
include_once(__CA_LIB_DIR__."/Logging/BaseLogger.php");

class Downloadlog extends BaseLogger {
	# ----------------------------------------
	/** 
	 *
	 */
	public function __construct($pa_entry=null) {
		parent::__construct();
	}
	# ----------------------------------------
	/** 
	 *
	 */
	public function log($pa_entry) {
		if (is_array($pa_entry)) {
			$this->o_db->query("
				INSERT INTO ca_download_log 
				(log_datetime, user_id, ip_addr, table_num, row_id, representation_id, download_source)
				VALUES
				(unix_timestamp(), ?, ?, ?, ?, ?, ?)
			", $pa_entry['user_id'], $pa_entry['ip_addr'], $pa_entry['table_num'], $pa_entry['row_id'], $pa_entry['representation_id'], $pa_entry['download_source']);
			
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
				
				$qr_log = $o_db->query("
					SELECT dl.*, u.fname, u.lname, u.email, u.userclass
					FROM ca_download_log dl
					LEFT JOIN ca_users AS u ON dl.user_id = u.user_id
					WHERE 
						(dl.log_datetime BETWEEN $vn_period_start AND $vn_period_end)
					ORDER BY dl.log_datetime DESC
				");
				
				
				$va_rows = array();
				while($qr_log->nextRow()) {
					$va_row = $qr_log->getRow();
					
					$t_table = Datamodel::getInstanceByTableNum($va_row['table_num'], true);
					$va_row['table_name'] = $t_table->getProperty('NAME_PLURAL');
					$va_row['user_name'] = $va_row['fname'].' '.$va_row['lname'];
					$va_rows[$va_row['log_id']] = $va_row;
				}
				
				return $va_rows;
			}
		}
		return null;
	}
	# ----------------------------------------
	/** 
	 *
	 */
	public static function purgeForRepresentation($representation_id, $options=null) {
		$trans = caGetOption('transaction', $options, null);
		$o_db = $trans ? $trans->getDb() : new Db();
		
		if ($representation_id && !is_array($representation_id)) { 
			$representation_id = [$representation_id];
		} elseif(is_array($representation_id) && !sizeof($representation_id)) {
			return null;
		} elseif(!$representation_id) {
			return null;
		}
		
		return $o_db->query("DELETE FROM ca_download_log WHERE representation_id IN (?)", [$representation_id]);
	}
	# ----------------------------------------
}
