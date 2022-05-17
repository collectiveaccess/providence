<?php
/** ---------------------------------------------------------------------
 * MusearchDataReader.php : 
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

class MusearchDataReader extends BaseXMLDataReader {
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
	 * For files that don't use a namespace this should be set to *something* â€“ doesn't really matter what
	 */
	protected $ops_xml_namespace_prefix = null;
	
	/**
	 * XPath to select data for reading
	 */
	protected $ops_xpath = '/root';
	
	/**
	 * 
	 */
	protected $ops_root_tag = 'root';
	
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
	
	/**
	 * Convert anything that looks like a Musearch int date to ISO?
	 */
	protected $auto_convert_dates = true;
	
	# -------------------------------------------------------
	/**
	 * @param string $ps_source
	 * @param array $pa_options Options include:
	 *		autoconvertDates =
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('Musearch Reader');
		$this->ops_display_name = _t('Musearch');
		$this->ops_description = _t('Imports Musearch data');
		
		$this->auto_convert_dates = caGetOption('autoconvertDates', $pa_options, false);
		
		$this->opa_formats = array('musearch');	// must be all lowercase to allow for case-insensitive matching
	}

	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_spec
	 * @param array $pa_options Options include:
	 *		convertDates = 
	 * @return mixed
	 */
	public function get($ps_spec, $pa_options=null) {
		if ($vm_ret = BaseDataReader::get($ps_spec, $pa_options)) { return $vm_ret; }
		
		$vb_return_as_array = caGetOption('returnAsArray', $pa_options, false);
		$vs_delimiter = caGetOption('delimiter', $pa_options, ';');
		
		
		//$ps_spec = str_replace("/", "", $ps_spec);
		if ($this->opb_tag_names_as_case_insensitive) { $ps_spec = strtolower($ps_spec); }
		if (is_array($this->opa_row_buf) && ($ps_spec) && (isset($this->opa_row_buf[$ps_spec]))) {
			
			if(caGetOption('convertDates', $pa_options, false)) {
				$this->opa_row_buf[$ps_spec] = array_map(function($v) {
					if(is_numeric($v)) {
						return self::convertDate((int)$v);
					}  else {
						return $v;
					}
				}, $this->opa_row_buf[$ps_spec]);
			}
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
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function nextRow() {
		$rc = parent::nextRow();
		foreach($this->opa_row_buf as $spec => $val) {
			if(is_array($val)) {
				$this->opa_row_buf[$spec] = array_map(function($v) { 
					// Convert brain-dead Musemarch dates
					if($this->auto_convert_dates && preg_match('!^[\d]{5,6}$!', $v)) {
						return self::convertDate($v);
					} 
					return $v;
				}, $val);
			}
		}
		return $rc;
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
	/**
	 * Convert brain-dead Musemarch dates to ISO
	 *
	 * @param int $v Musearch not-Excel date format
	 *
	 * @return string ISO 8601 date
	 */
	static public function convertDate(int $v) : string { 
		$ts = ($v - 61728) * 86400;
		return date('Y-m-d', $ts); 
	}
	# -------------------------------------------------------
}