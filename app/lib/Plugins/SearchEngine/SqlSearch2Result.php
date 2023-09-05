<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/SqlSearch2Result.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 
 /**
  *
  */
 
 include_once(__CA_LIB_DIR__.'/Datamodel.php');
 include_once(__CA_LIB_DIR__.'/Plugins/WLPlug.php');
 include_once(__CA_LIB_DIR__.'/Plugins/IWLPlugSearchEngineResult.php');

class WLPlugSearchEngineSqlSearch2Result extends WLPlug implements IWLPlugSearchEngineResult {
	# -------------------------------------------------------
	private $opo_config;

	/**
	 * @var SearchQuery
	 */
	private SearchQuery $hits;

	private int $limit = 1_000_000;
	private int $current_row;
	private BaseModel $opo_subject_instance;
	
	# -------------------------------------------------------
	private string $ops_subject_primary_key;
	private string $ops_subject_table_name;
	/**
	 * @var mixed
	 */
	private int $opn_subject_tablenum;

	public function __construct($pa_hits, $pn_table_num) {
		$this->opn_subject_tablenum = $pn_table_num;

		$this->setHits($pa_hits);
		parent::__construct();
	}
	# -------------------------------------------------------
	public function setHits(SearchQuery $pa_hits) {
		$pa_hits->rewind();
		$this->hits = $pa_hits;

		$this->current_row = $pa_hits->key();
		if (sizeof($this->hits)) {
			
			$this->opo_subject_instance = Datamodel::getInstanceByTableNum($this->opn_subject_tablenum, true);
			$this->ops_subject_primary_key = $this->opo_subject_instance->primaryKey();
			$this->ops_subject_table_name = $this->opo_subject_instance->tableName();
		}
	}
	# -------------------------------------------------------
	public function seek($pn_index) {
		return $this->hits->seek($pn_index);
	}
	# -------------------------------------------------------
	public function currentRow() {
		return $this->current_row;
	}
	# -------------------------------------------------------
	public function numHits() {
		return $this->hits->getNumRecords();
	}
	# -------------------------------------------------------
	public function nextHit() {
		return $this->hits->next();
	}
	# -------------------------------------------------------
	public function get($ps_field, $pa_options=null) {
		// primary key
		if (($ps_field === $this->ops_subject_primary_key) || ($ps_field === ($this->ops_subject_table_name.'.'.$this->ops_subject_primary_key))) {
			return $this->hits->key();
		}
		return false;	// false=SqlSearch can't get value; signals to SearchResult::get() that it should try to load the field if it can
	}
	# -------------------------------------------------------
	public function getPrimaryKeyValues($vn_limit=null) {
		$rows = [];
		if (!$vn_limit) {
			$vn_limit = $this->getLimit();
		}
		$i = 0;
		$this->hits->rewind();
		while ($this->hits->next() && ( ! $vn_limit || $i < $vn_limit )) {
			$row = $this->hits->current();
			$id = $row[$this->ops_subject_primary_key];
			$rows[$id] = $id;
			$i++;
		}

		return $rows;
	}
	# -------------------------------------------------------
	public function __destruct() {
		unset($this->opo_subject_instance);
		unset($this->opo_config);
	}
	# -------------------------------------------------------
	public function getLimit(): int {
		return $this->limit;
	}
}
