<?php
/** ---------------------------------------------------------------------
 * EHiveDataReader.php : 
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

require_once(__CA_LIB_DIR__.'/Import/DataReaders/BaseXMLDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class EHiveDataReader extends BaseXMLDataReader {
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
	protected $ops_xml_namespace = null;
	
	/**
	 * XML namespace prefix to pair with namespace URL
	 * For files that use a namespace this should match that actually used in the file;
	 * For files that don't use a namespace this should be set to *something* – doesn't really matter what
	 */
	protected $ops_xml_namespace_prefix = null;
	
	/**
	 * XPath to select data for reading
	 */
	protected $ops_xpath = '//eHive/record';
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
		
		$this->ops_title = _t('eHive XML Reader');
		$this->ops_display_name = _t('eHive XML');
		$this->ops_description = _t('Reads eHive XML files');
		
		$this->opa_formats = array('ehive');	// must be all lowercase to allow for case-insensitive matching
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
		try {
			$this->opo_xml = DOMDocument::load($ps_source);
			$this->opo_xpath = new DOMXPath($this->opo_xml);
		} catch (Exception $e) {
			return null;
		}
		
		// get rows
		$this->opo_handle = $this->opo_xpath->query($this->ops_xpath);

		$this->opn_current_row = 0;
		return $this->opo_handle ? true : false;
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
		
		$ps_spec = strtolower($ps_spec);
		if ($this->opb_tag_names_as_case_insensitive) { $ps_spec = strtolower($ps_spec); }
		if (is_array($this->opa_row_buf) && ($ps_spec) && (isset($this->opa_row_buf[$ps_spec]))) {
			if($vb_return_as_array) {
				return is_array($this->opa_row_buf[$ps_spec]) ? $this->opa_row_buf[$ps_spec] : [$this->opa_row_buf[$ps_spec]];
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
	public function nextRow() {
		if (!($o_row = $this->opo_handle->item($this->opn_current_row))) { return false; }
		
		$o_row_clone = $o_row->cloneNode(TRUE);
		$this->opo_handle_xml = new DOMDocument();
		$this->opo_handle_xml->appendChild($this->opo_handle_xml->importNode($o_row_clone,TRUE));

		$this->opo_handle_xpath = new DOMXPath($this->opo_handle_xml);
		
		if ($this->ops_xml_namespace_prefix && $this->ops_xml_namespace) {
			$this->opo_handle_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
		}
		$row_with_labels = [
			'/record_name' => $o_row->getAttribute('name'),
			'/object_record_id' => $o_row->getAttribute('object_record_id'),
			'/perspective' => $o_row->getAttribute('perspective'),
			'/public_access' => $o_row->getAttribute('public_access'),
		];
		
		$cols = $this->opo_handle_xpath->query('fieldSets');
		foreach($cols as $i => $o_node) {
			$fieldSet = $this->opo_handle_xpath->query('fieldSet', $o_node);
			foreach($fieldSet as $j => $o_fieldset) {
				$field_set_name = $o_fieldset->getAttribute('name');
				$fields = $this->opo_handle_xpath->query('field', $o_fieldset);
				
				foreach($fields as $k => $o_field) {
					$field_name = $field_value = null;
					foreach($o_field->childNodes as $c) {
						switch($c->tagName) {
							case 'fieldName':
								$field_name = $c->nodeValue;
								break;
							case 'fieldValue':
								$field_value = $c->nodeValue;
								break;
						}
					}
					if ($field_name) {
						$key = "/{$field_set_name}/{$field_name}";
						$row_with_labels[$key][] = $field_value;
						
						if($field_set_name == $field_name) {
							$row_with_labels["/{$field_name}"][] = $field_value;
						}
					}
				}
			}
		}
		
		$this->opa_row_buf = $row_with_labels;
		$this->opn_current_row++;
		
		if ($this->opn_current_row > $this->numRows()) { return false; }
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
		return is_array($this->opa_row_buf) ? $this->opa_row_buf : null;
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
