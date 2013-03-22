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
	 * Name of XML namespace. Set to null if no namespace is used.
	 */
	protected $ops_namespace = null;
	
	/**
	 * Name of top-level tag in XML format
	 */
	protected $ops_top_level_tag = null;
	
	/**
	 * Name of row-level tag – the tag that encloses each row to be read – in the XML format
	 * It is assumed that this tag is a direct child of the top level tag
	 */
	protected $ops_row_level_tag = null;
	
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
		//die("read source $ps_source");
		$this->opo_xml = simplexml_load_file($ps_source);
		
		$vs_path = "//".($this->ops_namespace ? $this->ops_namespace.':' : '').$this->ops_top_level_tag."/".($this->ops_namespace ? $this->ops_namespace.':' : '').$this->ops_row_level_tag;

		$this->opo_handle = $this->opo_xml->xpath($vs_path);
	
		$this->opn_current_row = 0;
		return $this->opo_handle ? true : false;
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
		
		if (!($o_row = $this->opo_handle[$this->opn_current_row])) { return false; }
		
		$this->opa_row_buf = array();
		foreach($o_row->children($this->ops_namespace, $this->ops_namespace ? true : false) as $vs_name => $o_tag) {
			$vs_key = $vs_name;
			$this->opa_row_buf[$vs_key] = (string)$o_tag;
			if ($this->opb_tag_names_as_case_insensitive) { 
				$vs_key = strtolower($vs_key);
				$this->opa_row_buf[$vs_key] = (string)$o_tag;
			}
		}

		if ($this->opb_use_row_tag_attributes_as_row_level_values) {
			foreach($o_row->attributes() as $vs_name => $vs_val) {
				$this->opa_row_buf[$vs_name] = (string)$vs_val;
				if ($this->opb_tag_names_as_case_insensitive) { 
					$vs_name = strtolower($vs_name);
					$this->opa_row_buf[$vs_name] = (string)$vs_val;
				}
			}
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
		$ps_spec = str_replace("/", "", $ps_spec);
		if ($this->opb_tag_names_as_case_insensitive) { $ps_spec = strtolower($ps_spec); }
		if (is_array($this->opa_row_buf) && ($ps_spec) && (isset($this->opa_row_buf[$ps_spec]))) {
			return $this->opa_row_buf[$ps_spec];
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
		return sizeof($this->opo_handle);
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
