<?php
/** ---------------------------------------------------------------------
 * SqliteDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__.'/Import/BaseDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class SqliteDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_row_buf = array();
	private $opn_current_row = -1;
	
	private $db;
	private $count;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($source=null, $options=null){
		parent::__construct($source, $options);
		
		$this->ops_title = _t('Sqlite data reader');
		$this->ops_display_name = _t('Sqlite data reader');
		$this->ops_description = _t('Reads data from Sqlite databases');
		
		$this->opa_formats = array('sqlite');	// must be all lowercase to allow for case-insensitive matching
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $source
	 * @param array $options
	 * @return bool
	 */
	public function read($source, $options=null) {
		parent::read($source, $options);
		
		if(!class_exists('SQLite3')) {
			throw new ApplicationException(_t('SQLite PHP extension is not installed'));
		}
		if(!($this->db = new SQLite3($source))) {
			throw new ApplicationException(_t('Could not open %1', $source));
		}
		
		$base_path = caGetOption('basePath', $options, []);
		
		
		if(!($this->opo_handle = $this->db->query("SELECT * FROM {$base_path}"))) {
			throw new ApplicationException(_t('Sqlite error on initial query for table %1', $base_path));
		}
		
		$c = $this->db->query("SELECT count(*) c FROM {$base_path}");
		$this->count = 0;
		if($crow = $c->fetchArray()) {
			$this->count = $crow['c'];
		}
		
		$this->opn_current_row = -1;
		$this->opa_row_buf = [];
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $source
	 * @param array $options
	 * @return bool
	 */
	public function nextRow() {
		$this->opn_current_row++;
		if($row = $this->opo_handle->fetchArray(SQLITE3_ASSOC)) {
			foreach($row as $k => $v) {
				unset($row[$k]);
				$row[strtolower($k)] = iconv('ISO-8859-1', 'UTF-8', $v);	// make case-insensitive
			}
		
			$this->opa_row_buf = $row;
			return true;
		}
		
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $source
	 * @param array $options
	 * @return bool
	 */
	public function seek($pn_row_num) {
		// TODO
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $field
	 * @param array $options
	 * @return mixed
	 */
	public function get($field, $options=null) {
		if ($vm_ret = parent::get($field, $options)) { return $vm_ret; }
		
		if(!is_array($this->opa_row_buf)) { return null; }
		
		$f = explode('::', $field);
		
		$val = caGetOption($f[0], $this->opa_row_buf, null);
		
		if(sizeof($f) == 3) {
			$rel_table = strtolower($f[1]);
			$rel_field = strtolower($f[2]);
			
			$qrel = $this->db->query("SELECT {$rel_field} FROM {$rel_table}");
			if($rdata = $qrel->fetchArray()) {
				foreach($rdata as $k => $v) {
					unset($rdata[$k]);
					$rdata[strtolower($k)] = $v;	// make case-insensitive
				}
				$val = $rdata[$rel_field];
			}
		}
		
		if (caGetOption('returnAsArray', $options, false)) {
			return is_array($val) ? $val : array($val);
		}
		
		return $val;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($options=null) {
		if (is_array($this->opa_row_buf)) {
			return $this->opa_row_buf;
		}
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function numRows() {
		return $this->count;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function currentRow() {
		return $this->opn_current_row;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function getInputType() {
		return __CA_DATA_READER_INPUT_FILE__;
	}
	
	# -------------------------------------------------------
	/**
	 * Values can repeat for XML files
	 * 
	 * @return bool
	 */
	public function valuesCanRepeat() {
		return true;
	}
	# -------------------------------------------------------
}