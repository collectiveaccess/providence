<?php
/** ---------------------------------------------------------------------
 * BaseXMLDataReader.php : 
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

require_once(__CA_LIB_DIR__.'/ca/Import/BaseDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class BaseXMLDataReader extends BaseDataReader {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected $ops_xpath = null;
	
	/**
	 * Merge attributes of row-level tag into record as regular values?
	 *
	 * This is useful for formats that encode row_ids as attributes that are more easily
	 * referred to in import mappings as plain old record values
	 */
	protected $opb_use_row_tag_attributes_as_row_level_values = true;
	
	/**
	 * Treat tag names as case insensitive?
	 *
	 * It's often easier in an import mapping to not worry about case in source specifications
	 * Setting this to true will cause all tag names to be matched without regard to case for the format
	 */
	protected $opb_tag_names_as_case_insensitive = true;
	
	private $opo_handle = null;
	private $opo_xml = null;
	private $opo_xpath = null;
	private $opa_row_buf = array();
	private $opn_current_row = 0;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('Base XML data reader');
		$this->ops_display_name = _t('Base XML data reader');
		$this->ops_description = _t('Provides basic XML functions for all XML-format data readers');
		
		$this->opa_formats = array();
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
		
		if ($this->ops_xml_namespace_prefix && $this->ops_xml_namespace) {
			$this->opo_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
		}
		$this->opo_handle = $this->opo_xpath->query($this->ops_xpath);

		$this->opn_current_row = 0;
		return $this->opo_handle ? true : false;
	}
	# -------------------------------------------------------
	/**
	 * Extract XML values recursively
	 */
	private function _extractXMLValues($o_row, $ps_base_key='') {
		$vn_l = (int)$o_row->childNodes->length;
		
		for($vn_i=0; $vn_i < $vn_l; $vn_i++) {
			$o_node = $o_row->childNodes->item($vn_i);
			$vs_key = $ps_base_key.'/'.$o_node->nodeName;
			$this->opa_row_buf[$vs_key][] = (string)$o_node->nodeValue;
			if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
				$this->opa_row_buf[strtolower($vs_key)][] = (string)$o_node->nodeValue; 
			}
			
			if ($o_node->hasChildNodes()) {
				$this->_extractXMLValues($o_node, $vs_key);
			}
		}

		 if ($this->opb_use_row_tag_attributes_as_row_level_values && $o_row->hasAttributes()) {
		 	$o_attributes = $o_row->attributes;
		 	$vn_l = $o_attributes->length;
		 	
			for($vn_i=0; $vn_i < $vn_l; $vn_i++) {
				$o_node = $o_attributes->item($vn_i);
				$vs_key = $ps_base_key.'/'.$o_node->nodeName;
 				$this->opa_row_buf[$vs_key] = (string)$o_node->nodeValue;
 				if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
 					$this->opa_row_buf[strtolower($vs_key)][] = (string)$o_node->nodeValue;
 				}
 			}
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
		
		//$ps_spec = str_replace("/", "", $ps_spec);
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
				$va_row["/{$vs_k}"] = $vs_v;
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