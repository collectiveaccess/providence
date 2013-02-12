<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Logging/BaseLogger.php :
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
 
include_once(__CA_LIB_DIR__."/core/Error.php");
include_once(__CA_LIB_DIR__."/core/Configuration.php");
include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Db.php");
include_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");


class BaseLogger {
	# ----------------------------------------
  	protected $o_db;
	# ----------------------------------------
	public function __construct($pa_entry=null) {
		$this->clearTransaction();
		
		if (is_array($pa_entry)) {
			$this->log($pa_entry);
		}
	}
	# ----------------------------------------
	public function log($pa_entry) {
		die("Method must be implemented!");
	}
	# ----------------------------------------
	public function search($ps_datetime_expression, $ps_code=null) {
		die("Method must be implemented!");
	}
	# ----------------------------------------
	# --- Transactions
	# ----------------------------------------
	public function setTransaction($po_transaction) {
		if (is_object($po_transaction)) {
			$this->o_db = $po_transaction->getDb();
			return true;
		} else {
			return false;
		}
	}
	# ----------------------------------------
	public function clearTransaction() {
		$this->o_db = new Db();
	}
	# ----------------------------------------
}
?>