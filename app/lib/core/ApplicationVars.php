<?php
/** ---------------------------------------------------------------------
 * app/lib/core/ApplicationVars.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2010 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
  *
  */
  
include_once(__CA_LIB_DIR__."/core/BaseObject.php");
include_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
include_once(__CA_LIB_DIR__."/core/Db.php");

class ApplicationVars extends BaseObject {
	# ----------------------------------------
	# --- Properties
	# ----------------------------------------
	private $opa_app_vars;
	private $o_db;
	
	# ----------------------------------------
	# --- Constructor
	# ----------------------------------------
	public function __construct ($po_db=null) {
		if (!is_object($po_db)) { $po_db = new Db(); }
		$this->o_db = $po_db;
		
		$qr_res = $po_db->query("SELECT * FROM ca_application_vars LIMIT 1");
		if (!$qr_res->nextRow()) {
			# -- create app var record
			$this->opa_app_vars = array("last_modified_on" => time());
			$this->o_db->query("INSERT INTO ca_application_vars (vars) VALUES (?)", caSerializeForDatabase($this->opa_app_vars));
			
			if ($this->o_db->numErrors()) {
				# error
				$this->postError(300, join(';', $this->o_db->getErrors()), "ApplicationVars->load()");
				return false;
			}
			return true;
		}
		
		if (!is_array($this->opa_app_vars = caUnserializeForDatabase($qr_res->get('vars')))) {
			$this->opa_app_vars = array();
		}
	}
	# ----------------------------------------
	# --- Methods
	# ----------------------------------------
	# Set application variable
	# User var may be number, string or array
	#
	public function setVar ($ps_key, $pm_val) {
		if ($ps_key) {
			$this->opa_app_vars[$ps_key] = $pm_val;
			return true;
		}
		return false;
	}
	# ----------------------------------------
	# Get application variable
	#
	public function getVar($ps_key) {
		return $this->opa_app_vars[$ps_key];
	}
	# ----------------------------------------
	#
	# Use save() to save regular variables - things that you are not worried about race conditions for
	public function save() {
		$this->o_db->query("UPDATE ca_application_vars SET vars = ?", caSerializeForDatabase($this->opa_app_vars));
		
		if ($this->o_db->numErrors()) {
			$this->postError(305, join(';', $this->o_db->getErrors()), "ApplicationVars->save()");
			return false;
		}
		return true;
	}
	# ----------------------------------------
}
?>