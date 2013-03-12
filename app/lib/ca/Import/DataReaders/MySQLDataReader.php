<?php
/** ---------------------------------------------------------------------
 * MySQLDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel/IOFactory.php');
require_once(__CA_LIB_DIR__.'/ca/Import/BaseDataReader.php');


class MySQLDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opo_rows = null;
	private $opa_row_buf = array();
	private $opn_current_row = 0;
	
	private $ops_table = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('MySQL data reader');
		$this->ops_display_name = _t('MySQL database');
		$this->ops_description = _t('Reads data from MySQL databases');
		
		$this->opa_formats = array('mysql');	// must be all lowercase to allow for case-insensitive matching
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source MySQL URL
	 * @param array $pa_options
	 * @return bool
	 */
	public function read($ps_source, $pa_options=null) {
		# mysql://username:password@localhost/database?table=tablename
		$va_url = parse_url($ps_source);
		
		try {
			$vs_db = substr($va_url['path'], 1);
			$this->opo_handle = new Db(null, array(
				"username" => 	$va_url['user'],
				"password" => 	$va_url['pass'],
				"host" =>	 	$va_url['host'],
				"database" =>	$vs_db,
				"type" =>		'mysql'
			));
			$this->opn_current_row = 0;
			
			parse_str($va_url['query'], $va_path);
			$this->ops_table = $va_path['table'];
			if (!$this->ops_table) { 
				return false;
			}
			
			$this->opo_rows = $this->opo_handle->query("SELECT * FROM {$this->ops_table}");
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function nextRow() {
		if (!$this->opo_rows) { return false; }
		
		if($o_row = $this->opo_rows->nextRow()) {
			$this->opn_current_row++;
			
			$this->opa_row_buf = $this->opo_rows->getRow();
		
			
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function seek($pn_row_num) {
		$this->opn_current_row = $pn_row_num;
		return $this->opo_rows->seek($pn_row_num + 1);
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param mixed $pn_col
	 * @param array $pa_options
	 * @return mixed
	 */
	public function get($ps_col, $pa_options=null) {
		$va_col = explode(".", $ps_col);
		if (sizeof($va_col) == 1) {
			return isset($this->opa_row_buf[$ps_col]) ? $this->opa_row_buf[$ps_col] : null;
		}
		if ((sizeof($va_col) > 1) && ($va_col[0] == $this->ops_table)) {
			return isset($this->opa_row_buf[$va_col[1]]) ? $this->opa_row_buf[$va_col[1]] : null;
		}
		
		// TODO: pull related rows?
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
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
		return $this->opo_rows->numRows();
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function inputType() {
		return $this->opo_rows->numRows();
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function getInputType() {
		return __CA_DATA_READER_INPUT_URL__;
	}
	# -------------------------------------------------------
}
