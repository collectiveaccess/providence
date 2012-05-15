<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/DbStatement.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2008 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/core/Db/DbBase.php");
require_once(__CA_LIB_DIR__."/core/Db/DbStatement.php");
require_once(__CA_LIB_DIR__."/core/Db/DbResult.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");

/**
 * Database abstraction statement class (supercedes ancient Db_Sql class)
 */
class DbStatement extends DbBase {
	/**
	 * Instance of db driver
	 *
	 * @access private
	 */
	var $opo_db;

	/**
	 * text of current SQL statement set by prepare()
	 *
	 * @access private
	 */
	var $ops_sql = "";

	/**
	 * limit number of returned records for SELECT statements (0 means no limit)
	 *
	 * @access private
	 */
	var $opn_limit ;

	/**
	 * starting record to return for SELECT statements
	 *
	 * @access private
	 */
	var $opn_offset ;

	/**
	 * The id generated from the previous SQL INSERT operation
	 *
	 * @access private
	 */
	var $opn_last_insert_id = null;

	/**
	 * Options array (containing stuff like limits)
	 *
	 * @access private
	 */
	var $opa_options;

	/**
	 * Constructor
	 *
	 * @param mixed $po_db instance of the db driver you are using
	 * @param string $ps_sql the SQL statement
	 * @param array $po_options options array
	 */
	function DbStatement(&$po_db, $ps_sql, $po_options=null) {
		$this->opo_db =& $po_db;
		$this->ops_sql = $ps_sql;

		$this->opa_options = $po_options;
	}

	/**
	 * Executes a stored statement (this class also serves as PerparedStatement abstraction).
	 * Options can be passed as arguments to this function.
	 *
	 * @return mixed result
	 */
	function execute() {
		$this->clearErrors();
		$va_args = func_get_args();
		if (is_array($va_args[0])) { 
			$va_args = $va_args[0];
		}
		
		if ($vb_res = $this->opo_db->execute($this, $this, $this->ops_sql, $va_args)) {
			$this->opn_last_insert_id = $this->opo_db->getLastInsertID($this);
		}
		return $vb_res;
	}

	/**
	 * Executes a stored statement (same as above) but in this case you can pass options as array.
	 *
	 * @see DbStatement::execute()
	 * @return mixed result
	 */
	function executeWithParamsAsArray($pa_params) {
		$this->clearErrors();

		if ($vb_res = $this->opo_db->execute($this, $this,$this->ops_sql, $pa_params)) {
			$this->opn_last_insert_id = $this->opo_db->getLastInsertID($this);
		}
		return $vb_res;
	}

	/**
	 * Fetches the id generated from the previous SQL INSERT operation
	 *
	 * @return int
	 */
	function getLastInsertID() {
		return $this->opn_last_insert_id;
	}
	# ---------------------------------------------------------------------------
	# Query options
	# ---------------------------------------------------------------------------
	/**
	 * Sets the limit and offset options to control the resultset you get when executing.
	 *
	 * @param int $pn_limit
	 * @param int $pn_offset
	 * @return bool success state
	 */
	function setLimit($pn_limit, $pn_offset=0) {
		if (!$this->opo_db->supports($this, "limit")) {
			$this->postError(212, _t("Driver does not support LIMIT"), "DbStatement->setLimit()");
			return false;
		}

		if (($pn_limit >= 0) || ($pn_offset >= 0)) {
			$this->opn_limit = intval($pn_limit);
			$this->opn_offset = intval($pn_offset);

			return true;
		} else {
			return false;
		}
	}

	/**
	 * How big is the current limit?
	 *
	 * @return array associative array with "limit" and "offset" options as keys (known from SQL statements); false on error
	 */
	function getLimit() {
		if (!$this->opo_db->supports($this, "limit")) {
			$this->postError(212, _t("Driver does not support LIMIT"), "DbStatement->getLimit()");
			return false;
		}

		return array("limit" => $this->opn_limit, "offset" => $this->opn_offset);
	}

	/**
	 * Removes current limit and offset
	 *
	 * @return bool success state
	 */
	function removeLimit() {
		return $this->setLimit(0,0);
	}

	/**
	 * Set a common option which is considered (if valid) when your statement is executed.
	 *
	 * @param string $ps_key name of the option
	 * @param string $ps_value option value
	 */
	public function setOption($ps_key, $ps_value) {
		$this->opa_options[$ps_key] = $ps_value;
		return true;
	}

	/**
	 * Fetch an option value
	 *
	 * @param string $ps_key name of the option
	 * @return string option value
	 */
	public function getOption($ps_key) {
		return $this->opa_options[$ps_key];
	}

	/**
	 * Destructor
	 */
	function __destruct() {
		//print "DESTRUCT db statement\n";
	}
}
?>