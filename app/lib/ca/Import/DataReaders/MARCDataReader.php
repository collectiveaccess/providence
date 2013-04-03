<?php
/** ---------------------------------------------------------------------
 * MARCDataReader.php : 
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

require_once(__CA_LIB_DIR__.'/core/Parsers/File_MARC/MARC.php');

require_once(__CA_LIB_DIR__.'/ca/Import/BaseDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class MARCDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $opa_rows = null;
	private $opn_current_row = 0;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('MARC data reader');
		$this->ops_display_name = _t('MARC');
		$this->ops_description = _t('Reads MARC files');
		
		$this->opa_formats = array('marc');	// must be all lowercase to allow for case-insensitive matching
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
		try {
			$this->opo_handle = new File_MARC($ps_source, File_MARC::SOURCE_FILE);
			$this->opn_current_row = -1;
			
			$this->opa_rows = array();
			while($o_row = $this->opo_handle->next()) {
				$this->opa_rows[] = $o_row;
			}
		} catch (Exception $e) {
			print $e->getMessage();
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
		if (!$this->opo_handle) { return false; }
		if (!is_array($this->opa_rows) || !sizeof($this->opa_rows)) { return false; }
		
		$this->opn_current_row++;
		
		return isset($this->opa_rows[$this->opn_current_row]) ? $this->opa_rows[$this->opn_current_row] : false;
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
		if (!$this->opo_handle) { return false; }
		if (!is_array($this->opa_rows) || !sizeof($this->opa_rows)) { return false; }
		if ($pn_row_num < 0) { return false; }
		if ($pn_row_num >= sizeof($this->opa_rows)) { return false; }
		
		$this->opn_current_row = $pn_row_num;
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param mixed $ps_spec MARC code/MARC subcode/indicators
	 * @param array $pa_options
	 *		delimiter = 
	 * @return mixed
	 */
	public function get($ps_spec, $pa_options=null) {
		list($ps_code, $ps_subcode, $ps_indicators) = explode('/', $ps_spec);
		if (!isset($this->opa_rows[$this->opn_current_row])) { return null; }
		
		$vs_delimiter = isset($pa_options['delimiter']) ? $pa_options['delimiter'] : ';'; 
		$vs_ind1 = substr($ps_indicators, 0, 1);
		$vs_ind2 = substr($ps_indicators, 0, 2);
		
		$o_record = $this->opa_rows[$this->opn_current_row];
		
		if ($o_fields = $o_record->getFields($ps_code)) {
			$va_content = array();
			foreach($o_fields as $o_field) {
				switch($vs_class = get_class($o_field)) {
					case 'File_MARC_Control_Field':
						continue;
						break;
					default:
						if (strlen($vs_ind1) && ($vs_ind1 != $o_field->getIndicator(1))) { continue; }
						if (strlen($vs_ind2) && ($vs_ind2 != $o_field->getIndicator(2))) { continue; }
			
						$o_subfield = $o_field->getSubfield($ps_subcode);
						$va_content[] = is_object($o_subfield) ? $o_subfield->getData() : '';
						break;
				}
			}
			return join($vs_delimiter, $va_content);
		}
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
		if (!isset($this->opa_rows[$this->opn_current_row])) { return null; }
		$o_record = $this->opa_rows[$this->opn_current_row];
	
		if ($o_fields = $o_record->getFields()) {
			$va_row = array();
			foreach($o_fields as $o_field) {
				switch(get_class($o_field)) {
					case 'File_MARC_Control_Field':
						continue;
						break;
					default:
						 $o_subfields = $o_field->getSubfields();
						 foreach($o_subfields as $o_subfield) {
					
							if (!($vs_ind1 = $o_field->getIndicator(1))) { $vs_ind1 = "#"; }
							if (!($vs_ind2 = $o_field->getIndicator(2))) { $vs_ind2 = "#"; }
					
							$va_row[$o_field->getTag().'/'.$o_subfield->getCode()] = $va_row[$o_field->getTag().'/'.$o_subfield->getCode().'/'.$vs_ind1.$vs_ind2] = is_object($o_subfield) ? $o_subfield->getData() : '';
						 }
						 break;
				}
			}
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
		return is_array($this->opa_rows) ? sizeof($this->opa_rows) : 0;
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
}
?>