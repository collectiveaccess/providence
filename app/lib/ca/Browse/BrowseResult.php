<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Browse/BrowseResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 * @subpackage Browse
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 include_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 include_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
 include_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngineResult.php');

class WLPlugSearchEngineBrowseEngine extends WLPlug implements IWLPlugSearchEngineResult {
	# -------------------------------------------------------
	private $opo_config;
	
	private $opa_hits;
	private $opn_current_row;
	protected $opo_subject_instance;
	
	# -------------------------------------------------------
	public function __construct($pa_hits, $pn_table_num) {
		$this->opo_datamodel = Datamodel::load();
		$this->opn_subject_tablenum = $pn_table_num;
		
		$this->setHits($pa_hits);
	}
	# -------------------------------------------------------
	public function setHits($pa_hits) {
		$this->opa_hits = $pa_hits;
		$this->opn_current_row = -1;
		if (sizeof($this->opa_hits)) {
			$this->opo_subject_instance = $this->opo_datamodel->getInstanceByTableNum($this->opn_subject_tablenum, true);
			$this->ops_subject_primary_key = $this->opo_subject_instance->primaryKey();
			$this->ops_subject_table_name = $this->opo_subject_instance->tableName();
		}
	}
	# -------------------------------------------------------
	public function getHits() {
		return $this->opa_hits;
	}
	# -------------------------------------------------------
	public function seek($pn_index) {
		if (($pn_index >= 0) && ($pn_index < sizeof($this->opa_hits))) {
			$this->opn_current_row = $pn_index - 1;
			return true;
		} 
		return false;
	}
	# -------------------------------------------------------
	public function currentRow() {
		return $this->opn_current_row;
	}
	# -------------------------------------------------------
	public function numHits() {
		return is_array($this->opa_hits) ? sizeof($this->opa_hits) : 0;
	}
	# -------------------------------------------------------
	public function nextHit() {
		if ($this->opn_current_row < sizeof($this->opa_hits) - 1) {
			$this->opn_current_row++;
			return true;
		} 
		return false;
	}
	# -------------------------------------------------------
	public function get($ps_field, $pa_options=null) {

		if (($ps_field == $this->ops_subject_primary_key) || ($ps_field == ($this->ops_subject_table_name.'.'.$this->ops_subject_primary_key))) {
			$va_tmp = explode('.', $ps_field);
			$vs_fieldname = array_pop($va_tmp);
			
			if (is_array($vs_val = $this->opa_hits[$this->opn_current_row])) {
				return ($vs_val = $this->opa_hits[$this->opn_current_row][$vs_fieldname]) ? $vs_val : false;	
			} else {
				return $this->opa_hits[$this->opn_current_row];
			}
		}
		// false=can't get value; signals to SearchResult::get() that it should try to load the field if it can
		return false;
	}
	# -------------------------------------------------------
}
?>