<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/ElasticSearchResult.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
include_once(__CA_LIB_DIR__.'/Datamodel.php');
include_once(__CA_LIB_DIR__.'/Plugins/WLPlug.php');
include_once(__CA_LIB_DIR__.'/Plugins/IWLPlugSearchEngineResult.php');

class WLPlugSearchEngineElasticSearchResult extends WLPlug implements IWLPlugSearchEngineResult {
	# -------------------------------------------------------
	private $opa_hits;
	private $opn_current_row;
	private $opn_subject_tablenum;
	private $ops_subject_primary_key;
	private $opo_subject_instance;
	private $ops_subject_table_name;
	# -------------------------------------------------------
	public function __construct($pa_hits, $result_desc, $pn_table_num) {
		parent::__construct();

		$this->opn_subject_tablenum = $pn_table_num;
		$this->setHits($pa_hits);
	}
	# -------------------------------------------------------
	public function setHits($pa_hits) {
		$this->opa_hits = $pa_hits;
		$this->opn_current_row = -1;
		
		if (sizeof($this->opa_hits)) {
			$this->opo_subject_instance = Datamodel::getInstanceByTableNum($this->opn_subject_tablenum, true);
			$this->ops_subject_primary_key = $this->opo_subject_instance->primaryKey();
			$this->ops_subject_table_name = $this->opo_subject_instance->tableName();
		}
	}
	# -------------------------------------------------------
	public function getHits() {
		return $this->opa_hits;
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
	public function currentRow() {
		return $this->opn_current_row;
	}
	# -------------------------------------------------------
	public function get($ps_field, $pa_options=null) {
		// the only thing get() pulls directly from the index is the primary key ...
		// everything else is handled in SearchResult::get() using prefetched database queries.
		if($ps_field == $this->ops_subject_primary_key || $ps_field == $this->ops_subject_table_name.'.'.$this->ops_subject_primary_key) {
			return $this->opa_hits[$this->opn_current_row]['_id'];
		}

		return false;
	}
	# -------------------------------------------------------
	public function getPrimaryKeyValues($vn_limit=null) {
		if(!$vn_limit) {$vn_limit = null; }
		if(!is_array($this->opa_hits)) { return array(); }
		// primary key
		$va_ids = array();
		
		$vn_c = 0;
		foreach($this->opa_hits as $vn_i => $va_row) {
			$va_ids[] = $va_row['_id'];
			$vn_c++;
			if (!is_null($vn_limit) && ($vn_c >= $vn_limit)) { break; }
		}
		return $va_ids;
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
}
