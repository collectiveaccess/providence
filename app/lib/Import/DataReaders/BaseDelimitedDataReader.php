<?php
/** ---------------------------------------------------------------------
 * BaseDelimitedDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Parsers/DelimitedDataParser.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class BaseDelimitedDataReader extends BaseDataReader {
	# -------------------------------------------------------	
	/**
	 * Delimited data parser
	 */
	protected $opo_parser = null;
	
	/**
	 * Delimiter
	 */
	protected $ops_delimiter = null;
	
	/**
	 * Array containing data for current row
	 */
	protected $opa_row_buf = array();
	
	/**
	 * Index of current row
	 */
	protected $opn_current_row = 0;
	
	/**
	 * Number of rows in currently loaded file
	 */
	protected $opn_num_rows = 0;
	
	/**
	 * Path of last read file
	 */
	protected $ops_source = null;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('Base Delimited data reader');
		$this->ops_display_name = _t('Base delimited data reader');
		$this->ops_description = _t('Provides basic functions for all delimited data readers');
		
		$this->opa_formats = array();
		
		$this->opa_properties['delimiter'] = $this->ops_delimiter;
		
		$this->opo_parser = new DelimitedDataParser($this->ops_delimiter);
		
		
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function read($ps_source, $pa_options=null) {
		parent::read($ps_source, $pa_options);
		
		$this->opn_current_row = 0;
		
		if($this->opo_parser->parse($ps_source)) {
			$r_f = fopen($ps_source, 'rb');
			$this->opn_num_rows = 0;
			while (!feof($r_f)) {
				$vs_buf = fread($r_f, 8192);
				$this->opn_num_rows += (substr_count($vs_buf, "\n") + (substr_count($vs_buf, "\r")));
			}
			fclose($r_f);
			
			$this->ops_source = $ps_source;
			return true;
		}
		
		
		$this->ops_source = null;
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
	public function nextRow() {
		if (!$this->opo_parser->nextRow()) { return false; }
		
		$this->opa_row_buf = $this->opo_parser->getRow();
		$this->opn_current_row++;
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
	public function seek($pn_row_num) {
		if ($pn_row_num > $this->numRows()) { return false; }
		
		if (!$this->read($ps_source)) { return false; }
		
		while($pn_row_num > 0) {
			$this->nextRow();
			$pn_row_num--;
		}
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_spec
	 * @param array $pa_options
	 * @return mixed
	 */
	public function get($ps_spec, $pa_options=null) {
		if ($vm_ret = parent::get($ps_spec, $pa_options)) { return $vm_ret; }
		
		$vb_return_as_array = caGetOption('returnAsArray', $pa_options, false);
		$vs_delimiter = caGetOption('delimiter', $pa_options, ';');
		
		$vs_value = $this->opo_parser->getRowValue($ps_spec);
	
		if ($vb_return_as_array) { return array($vs_value); }
		return $vs_value;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
		if (is_array($va_row = $this->opo_parser->getRow())) {
			// Make returned array 1-based to match delimiter data parser style (column numbers begin with 1)
			array_unshift($va_row, null);
			unset($va_row[0]);
			return $va_row;
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
		return (int)$this->opn_num_rows;
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
		return false;
	}
	# -------------------------------------------------------
}
