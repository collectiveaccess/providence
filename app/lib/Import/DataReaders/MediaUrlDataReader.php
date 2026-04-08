<?php
/** ---------------------------------------------------------------------
 * MediaUrlDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Import/BaseDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class MediaUrlDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_row_buf = array();
	private $opn_current_row = -1;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($source=null, $options=null){
		parent::__construct($source, $options);
		
		$this->ops_title = _t('MediaUrl data reader');
		$this->ops_display_name = _t('MediaUrl asset data');
		$this->ops_description = _t('Reads MediaUrl asset metadata');
		
		$this->opa_formats = ['mediaurl'];	// must be all lowercase to allow for case-insensitive matching
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
		
		$this->opn_current_row = -1;
		$this->opa_row_buf = json_decode($source, true);
		if (!is_array($this->opa_row_buf)) { return false; }
		
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
		if ($this->opn_current_row > 0) { return false; }
		$this->opn_current_row++;
		if ($this->opn_current_row == 0) { return true; }
		
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
	public function seek($row_num) {
		if ($row_num == 0) { 
			$this->opn_current_row = 0; 
			return true;
		}
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
		
		if ($this->opn_current_row !== 0) { return null; }
		if(!is_array($this->opa_row_buf)) { return null; }
		
		$keys = explode("/", $field);
		
		$ptr =& $this->opa_row_buf;
		foreach($keys as $key) {
			if ($key = trim(strtolower($key))) {
				if (!isset($ptr[$key])) { return null; }
				$ptr =& $ptr[$key];
			}
		}
		
		if (caGetOption('returnAsArray', $options, false)) {
			return is_array($ptr) ? $ptr : array($ptr);
		}
		
		return $ptr;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($options=null) {
		if ($this->opn_current_row !== 0) { return null; }
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
		return 1;
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
		return __CA_DATA_READER_INPUT_TEXT__;
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
