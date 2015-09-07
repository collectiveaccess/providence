<?php
/** ---------------------------------------------------------------------
 * ExifDataReader.php : 
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

require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel/IOFactory.php');
require_once(__CA_LIB_DIR__.'/ca/Import/BaseDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class ExifDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_row_buf = array();
	private $opn_current_row = -1;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('EXIF data reader');
		$this->ops_display_name = _t('Embedded EXIF media metadata');
		$this->ops_description = _t('Reads Embedded EXIF Media Metadata');
		
		$this->opa_formats = array('exif');	// must be all lowercase to allow for case-insensitive matching
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
		
		$vs_path_to_exif_tool = caGetExternalApplicationPath("exiftool");
	
		$this->opn_current_row = -1;
		$this->opa_row_buf = caMakeArrayKeysLowercase(caExtractMetadataWithExifTool($ps_source));
		if (!is_array($this->opa_row_buf)) { return false; }
		
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
		if ($this->opn_current_row > 0) { return false; }
		$this->opn_current_row++;
		if ($this->opn_current_row == 0) { return true; }
		
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
		if ($pn_row_num == 0) { 
			$this->opn_current_row = 0; 
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_field
	 * @param array $pa_options
	 * @return mixed
	 */
	public function get($ps_field, $pa_options=null) {
		if ($vm_ret = parent::get($ps_field, $pa_options)) { return $vm_ret; }
		
		if ($this->opn_current_row !== 0) { return null; }
		if(!is_array($this->opa_row_buf)) { return null; }
		
		$va_keys = explode("/", $ps_field);
		
		$va_ptr =& $this->opa_row_buf;
		foreach($va_keys as $vs_key) {
			if ($vs_key = trim(strtolower($vs_key))) {
				if (!isset($va_ptr[$vs_key])) { return null; }
				$va_ptr =& $va_ptr[$vs_key];
			}
		}
		
		if (caGetOption('returnAsArray', $pa_options, false)) {
			return is_array($va_ptr) ? $va_ptr : array($va_ptr);
		}
		
		return $va_ptr;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
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