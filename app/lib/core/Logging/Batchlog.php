<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Logging/Batchlog.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
class Batchlog {
	# ----------------------------------------
	/**
	 *
	 */
  	private $opn_last_batch_id = null;
	# ----------------------------------------
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
				INSERT INTO ca_batch_log 
				(log_datetime, user_id, table_num, notes, batch_type)
				VALUES
				(unix_timestamp(), ?, ?, ?, ?)
			", $pa_entry['user_id'], $pa_entry['table_num'], $pa_entry['notes'], $pa_entry['batch_type']);
			
			return $this->opn_last_batch_id = (int)$this->o_db->getLastInsertID();
		}
		return false;
	}
	# ----------------------------------------
	/**
	 *
	 */
	public function search($ps_datetime_expression, $ps_code=null) {
		// TODO: implement
		return null;
	}
	# ----------------------------------------
	# Batch log-specific methods
	# ----------------------------------------
	/**
	 *
	 */
	 public function addItem($pn_row_id, $pn_log_id) {
	 	if (!$this->opn_last_batch_id) { return false; }
		$this->o_db->query("
				INSERT INTO ca_batch_log_items
				(row_id, log_id, batch_id)
				VALUES
				(?, ?, ?)
			", (int)$pn_row_id, (int)$pn_log_id, (int)$this->opn_last_batch_id);
			
		
		return $this->o_db->numErrors() ? false : true;
	}
	# ----------------------------------------
	/**
	 *
	 */
	 public function getLastLogBatchID() {
	 	return $this->opn_last_batch_id;
	}
	# ----------------------------------------
	/**
	 *
	 */
	 public static function getLogInfoForBatchID($pn_batch_id) {
	 	// TODO :implement
	}
	# ----------------------------------------
	/**
	 *
	 */
	 public static function getLogItemsForBatchID($pn_batch_id) {
	 	// TODO :implement
	}
	# ----------------------------------------
}
?>