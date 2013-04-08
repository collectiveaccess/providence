<?php
/** ---------------------------------------------------------------------
 * FMPDSOResultReader.php : 
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

require_once(__CA_LIB_DIR__.'/ca/Import/DataReaders/BaseXMLDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class FMPDSOResultReader extends BaseXMLDataReader {
	# -------------------------------------------------------
	/**
	 * XPath to select
	 */
	protected $ops_xml_namespace = 'http://www.filemaker.com/fmpdsoresult';
	
	
	/**
	 * XPath to select
	 */
	protected $ops_xml_namespace_prefix = 'n';
	
	
	/**
	 * XPath to select
	 */
	protected $ops_xpath = '/n:FMPDSORESULT/n:ROW';
	
	
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
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->ops_title = _t('FMPro DSOResult XML Reader');
		$this->ops_display_name = _t('FMPro DSOResult');
		$this->ops_description = _t('Reads Filemaker Pro DSOResult-format XML files');
		
		$this->opa_formats = array('fmpdso');	// must be all lowercase to allow for case-insensitive matching
	}
	# -------------------------------------------------------
}
