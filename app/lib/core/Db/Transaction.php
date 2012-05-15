<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/Transaction.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003-2008 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Db.php");

class Transaction {
  	var $o_db; # database connection
  	
	# ----------------------------------------
	public function Transaction($po_db=null) {
		$this->o_db = ($po_db) ? $po_db : new Db();
		$this->o_db->dieOnError(false);
		$this->start();
	}
	# ----------------------------------------
	public function getDb() {
		return $this->o_db;
	}
	# ----------------------------------------
	public function commitTransaction() {
		$this->o_db->commitTransaction();
	}
	# ----------------------------------------
	public function commit() {
		$this->commitTransaction();
	}
	# ----------------------------------------
	public function rollbackTransaction() {
		$this->o_db->rollbackTransaction();
	}
	# ----------------------------------------
	public function rollback() {
		$this->rollbackTransaction();
	}
	# ----------------------------------------
	public function beginTransaction() {
		$this->o_db->beginTransaction();
	}
	# ----------------------------------------
	public function start() {
		$this->beginTransaction();
	}
	# ----------------------------------------
}
?>