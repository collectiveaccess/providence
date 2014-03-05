<?php
/** ---------------------------------------------------------------------
 * BaseXMLDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2014 Whirl-i-Gig
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
	 * XPath to select data for reading
	 */
	protected $ops_xpath = null;
	
	/**
	 * Skip root tag when evaluating XPath?
	 *
	 * If set then the XPath used to select data to read can omit the root XML tag
	 */
	protected $opb_register_root_tag = true;
	
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
	
	/**
	 * Selected nodes for current row
	 */
	protected $opo_handle = null;
	
	/**
	 * 
	 */
	protected $ops_root_tag = null;
	
	/**
	 * Parsed XML
	 */
	protected $opo_xml = null;
	
	/**
	 * XPath object, used to select data for reading
	 */
	protected $opo_xpath = null;
	
	/**
	 * Array containing data for current row
	 */
	protected $opa_row_buf = array();
	
	/**
	 * Index of current row
	 */
	protected $opn_current_row = 0;
	
	/**
	 * Per-row XML handle
	 */ 
	protected $opo_handle_xml = null;
	
	/**
	 * Per-row XPath object
	 */
	protected $opo_handle_xpath = null;
	
	/**
	 * Root tag to use when basePath is set
	 */
	protected $ops_base_root_tag = null;
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
		if ($ps_base_path = caGetOption('basePath', $pa_options, null)) {
			$va_tmp = explode("/", $ps_base_path);
			$this->ops_base_root_tag = array_pop($va_tmp);
			$this->ops_xpath = $this->_convertXPathExpression($ps_base_path);
		}
		
		$this->opo_xml = DOMDocument::load($ps_source);
		$this->opo_xpath = new DOMXPath($this->opo_xml);
		
		if ($this->ops_xml_namespace_prefix && $this->ops_xml_namespace) {
			$this->opo_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
		}
		
		$this->opo_handle = $this->opo_xpath->query($this->ops_xpath, null, $this->opb_register_root_tag);

		$this->opn_current_row = 0;
		return $this->opo_handle ? true : false;
	}
	# -------------------------------------------------------
	/**
	 * Extract XML values recursively
	 */
	protected function _extractXMLValues($o_row, $ps_base_key='') {
		$vn_l = (int)$o_row->childNodes->length;
		
		for($vn_i=0; $vn_i < $vn_l; $vn_i++) {
			$o_node = $o_row->childNodes->item($vn_i);
			if ($o_node->nodeName === '#text') { continue; }
			$vs_key = $ps_base_key.'/'.$o_node->nodeName;
			$this->opa_row_buf[$vs_key][] = (string)$o_node->nodeValue;
			if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
				$this->opa_row_buf[strtolower($vs_key)][] = (string)$o_node->nodeValue; 
			}
			
			if ($o_node->hasChildNodes()) {
				$this->_extractXMLValues($o_node, $vs_key);
			}
		}

		 if ($o_row->hasAttributes()) {
		 	$o_attributes = $o_row->attributes;
		 	$vn_l = $o_attributes->length;
		 	
			for($vn_i=0; $vn_i < $vn_l; $vn_i++) {
				$o_node = $o_attributes->item($vn_i);
				$vs_key = $ps_base_key.'/@'.$o_node->nodeName;
 				$this->opa_row_buf[$vs_key] = (string)$o_node->nodeValue;
 				if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
 					$this->opa_row_buf[strtolower($vs_key)] = (string)$o_node->nodeValue;
 				}
 				
 				if ($this->opb_use_row_tag_attributes_as_row_level_values) {
 					$vs_key = $ps_base_key.'/'.$o_node->nodeName;
					$this->opa_row_buf[$vs_key] = (string)$o_node->nodeValue;
					if ($this->opb_tag_names_as_case_insensitive && (strtolower($vs_key) != $vs_key)) { 
						$this->opa_row_buf[strtolower($vs_key)] = (string)$o_node->nodeValue;
					}
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
		
		$o_row_clone = $o_row->cloneNode(TRUE);
		$this->opo_handle_xml = new DOMDocument();
		$this->opo_handle_xml->appendChild($this->opo_handle_xml->importNode($o_row_clone,TRUE));

		$this->opo_handle_xpath = new DOMXPath($this->opo_handle_xml);
		
		if ($this->ops_xml_namespace_prefix && $this->ops_xml_namespace) {
			$this->opo_handle_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
		}
		
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
		
		// Recondition the spec for Xpath
		$ps_spec = $this->_convertXPathExpression($ps_spec, array('useRootTag' => $this->ops_base_root_tag));
		$o_node_list = $this->opo_handle_xpath->query($ps_spec);
		
		$va_values = array();
		foreach($o_node_list as $o_node) {
			$va_values[] = $o_node->nodeValue;
		}
		
		if ($vb_return_as_array) { return $va_values; }
		return join($vs_delimiter, $va_values);
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
	/**
	 * Make default namespace explicit and do other adjustments such that reasonable
	 * XPaths are agreeable to DomXPath
	 */
	public function _convertXPathExpression($ps_spec, $pa_options=null) {
		$vb_add_root_tag = caGetOption('addRootTag', $pa_options, true);
		$vs_use_root_tag = caGetOption('useRootTag', $pa_options, null);
		
		$vb_double_slash = (substr($ps_spec, 0, 2) == '//') ? true : false;
		$va_tmp = explode("/", $ps_spec);
		while(sizeof($va_tmp) && !$va_tmp[0]) { array_shift($va_tmp);}
		if ($vb_add_root_tag && !$vb_double_slash && ($va_tmp[0] != $this->ops_root_tag)) { array_unshift($va_tmp, $this->ops_xml_namespace_prefix.':'.($vs_use_root_tag ? $vs_use_root_tag : $this->ops_root_tag)); }
		foreach($va_tmp as $vn_i => $vs_spec_element) {
			if(!$vs_spec_element) { continue; }
			if (
				(strpos($vs_spec_element, ":") === false)
				&&
				(strpos($vs_spec_element, "@") !== 0)
			) {
				$va_tmp[$vn_i]= $this->ops_xml_namespace_prefix.":{$vs_spec_element}";
			}
		}
		$ps_spec = ($vb_double_slash ? '//' : '/').join("/", $va_tmp);
		
		return $ps_spec;
	}
	# -------------------------------------------------------
}
?>