<?php
/** ---------------------------------------------------------------------
 * FMPXMLResultReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/ca/Import/DataReaders/BaseXMLDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class FMPXMLResultReader extends BaseXMLDataReader {
	# -------------------------------------------------------
	/**
	 * Skip root tag when evaluating XPath?
	 *
	 * If set then the XPath used to select data to read can omit the root XML tag
	 */
	protected $opb_register_root_tag = true;
	
	/**
	 * XML namespace URL used by data
	 */
	protected $ops_xml_namespace = 'http://www.filemaker.com/fmpxmlresult';
	
	/**
	 * XML namespace prefix to pair with namespace URL
	 * For files that use a namespace this should match that actually used in the file;
	 * For files that don't use a namespace this should be set to *something* – doesn't really matter what
	 */
	protected $ops_xml_namespace_prefix = 'n';
	
	/**
	 * XPath to select data for reading
	 */
	protected $ops_xpath = '//n:RESULTSET/n:ROW';
	
	/**
	 * XPath to select metadata for reading
	 */
	protected $ops_metadata_xpath = '//n:METADATA/n:FIELD';
	
	/**
	 * METADATA tags extracted from XML input
	 */
	protected $opo_metadata = null;
	
	/**
	 * METADATA definitions extracted from XML input
	 */
	protected $opa_metadata = null;
	
	/**
	 * Merge attributes of row-level tag into record as regular values?
	 *
	 * This is useful for formats that encode row_ids as attributes that are more easily
	 * referred to in import mappings as plain old record values
	 */
	protected $opb_use_row_tag_attributes_as_row_level_values = false;

	/**
	 * Treat tag names as case insensitive?
	 *
	 * It's often easier in an import mapping to not worry about case in source specifications
	 * Setting this to true will cause all tag names to be matched without regard to case for the format
	 */
	protected $opb_tag_names_as_case_insensitive = true;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('FMPro XMLResult XML Reader');
		$this->ops_display_name = _t('FMPro XMLResult');
		$this->ops_description = _t('Reads Filemaker Pro XMLResult-format XML files');
		
		$this->opa_formats = array('fmpxml');	// must be all lowercase to allow for case-insensitive matching
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
		$this->opo_xml = DOMDocument::load($ps_source);
		$this->opo_xpath = new DOMXPath($this->opo_xml);
		
		$this->opo_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
		
		// get metadata 
		$this->opo_metadata = $this->opo_xpath->query($this->ops_metadata_xpath);
		
		$vn_index = 0;
		$this->opa_metadata = array();
		foreach($this->opo_metadata as $o_field) {
			$o_name = $o_field->attributes->getNamedItem('NAME');
			
			// Normalize field names by replacing any run of characters that is not a letter, number,
			// underscore, -, #, ?, :, % or & with a single underscore.
			$vs_field_name = preg_replace("![^A-Za-z0-9_\-\:\#\?\%\&]+!", "_", (string)$o_name->nodeValue);
			
			$this->opa_metadata[$vn_index] = $vs_field_name;
			
			$vn_index++;
		}
		
		// get rows
		$this->opo_handle = $this->opo_xpath->query($this->ops_xpath);

		$this->opn_current_row = 0;
		return $this->opo_handle ? true : false;
	}
	# -------------------------------------------------------
	/**
	 * Extract XML values recursively
	 */
	protected function _extractXMLValues($o_row, $ps_base_key='') {
		$vn_l = (int)$o_row->childNodes->length;
		
		$vn_index = 0;
		for($vn_i=0; $vn_i < $vn_l; $vn_i++) {
			$o_node = $o_row->childNodes->item($vn_i);
			if ($o_node->nodeName !== 'COL') { continue; }
			
			$vb_found_data = false;
			$vs_key = $ps_base_key.'/'.$this->opa_metadata[$vn_index];
			
			$vn_lx = $o_node->childNodes->length;
			for($vn_j=0; $vn_j < $vn_lx; $vn_j++) {
				if ((string)$o_node->childNodes->item($vn_j)->nodeName === 'DATA') {
					$o_data = $o_node->childNodes->item($vn_j);
					$vs_val = trim((string)$o_data->nodeValue);
					if (strlen($vs_val) == 0) { // && is_array($this->opa_row_buf[$vs_key]) && sizeof($this->opa_row_buf[$vs_key])) {
						continue;
					}
					
					$this->opa_row_buf[$vs_key][] = $vs_val;
					if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
						$this->opa_row_buf[strtolower($vs_key)][] = $vs_val; 
					}
			
					$vb_found_data = true;
					
					//break;
				}
			}
			if (!$vb_found_data) {
				$this->opa_row_buf[$vs_key][] = '';
				if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
					$this->opa_row_buf[strtolower($vs_key)][] = ''; 
				}
			}
			$vn_index++;
		}
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
		if (!($o_row = $this->opo_handle->item($this->opn_current_row))) { return false; }
		
		$this->opa_row_buf = array();
		$this->_extractXMLValues($o_row);
		
		$this->opn_current_row++;
		if ($this->opn_current_row > $this->numRows()) { return false; }
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
		
		$this->opn_current_row = $pn_row_num;
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
		$vb_return_as_array = caGetOption('returnAsArray', $pa_options, false);
		$vs_delimiter = caGetOption('delimiter', $pa_options, ';');
		
		if ($this->opb_tag_names_as_case_insensitive) { $ps_spec = strtolower($ps_spec); }
		if (is_array($this->opa_row_buf) && ($ps_spec) && (isset($this->opa_row_buf[$ps_spec]))) {
			if($vb_return_as_array) {
				return $this->opa_row_buf[$ps_spec];
			} else {
				return join($vs_delimiter, $this->opa_row_buf[$ps_spec]);
			}
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
		if (is_array($this->opa_row_buf)) {
			$va_row = $this->opa_row_buf;
			foreach($va_row as $vs_k => $vs_v) {
				if ($vs_k[0] == "/") { continue; }
				$va_row[(($vs_k[0] == "/") ? '' : '/').$vs_k] = $vs_v;
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
		return (int)$this->opo_handle->length;
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
?>