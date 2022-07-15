<?php
/** ---------------------------------------------------------------------
 * MediaInfoDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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

class MediaInfoDataReader extends BaseXMLDataReader {
# -------------------------------------------------------
	/**
	 * Skip root tag when evaluating XPath?
	 *
	 * If set then the XPath used to select data to read can omit the root XML tag
	 */
	protected $opb_register_root_tag = false;
	
	/**
	 * XML namespace URL used by data
	 */
	protected $ops_xml_namespace = 'http://www.pbcore.org/PBCore/PBCoreNamespace.html';
	
	/**
	 * XML namespace prefix to pair with namespace URL
	 * For files that use a namespace this should match that actually used in the file;
	 * For files that don't use a namespace this should be set to *something* – doesn't really matter what
	 */
	protected $ops_xml_namespace_prefix = 'n';
	
	/**
	 * XPath to select data for reading
	 */
	protected $ops_xpath = '//n:pbcoreInstantiationDocument';
	
	/**
	 * 
	 */
	protected $ops_root_tag = 'pbcoreInstantiationDocument';
	
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
	public function __construct($source=null, $options=null){
		parent::__construct($source, $options);
		
		$this->ops_title = _t('MediaInfo data reader');
		$this->ops_display_name = _t('MediaInfo embedded media metadata');
		$this->ops_description = _t('Reads embedded media metadata using MediaInfo');
		
		$this->opa_formats = ['mediainfo'];	// must be all lowercase to allow for case-insensitive matching
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options Options include:
	 *		basePath = 
	 *		fromString = XML string to parse if $ps_source is set to null. [Default is null]
	 *
	 * @return bool
	 */
	public function read($ps_source, $pa_options=null) {		
		$ps_mediainfo_path = caGetExternalApplicationPath('mediainfo');
		if (!caIsValidFilePath($ps_mediainfo_path)) { return false; }
	
		parent::read($ps_source, $pa_options);
		
		if (($ps_base_path = caGetOption('basePath', $pa_options, null)) && (!preg_match("!#XML tree#!", $ps_base_path))) {
			$va_tmp = explode("/", $ps_base_path);
			$this->ops_base_root_tag = array_pop($va_tmp);
			$this->ops_xpath = $this->_convertXPathExpression($ps_base_path);
		}
		
		if (file_exists($ps_source) && (filesize($ps_source) < 1024 * 1024)) { 	
			$d = file_get_contents($ps_source);
			if (preg_match("!<pbcoreInstantiationDocument!", $d)) {
				$d = preg_replace("!</pbcoreInstantiationDocument>.*!s", "</pbcoreInstantiationDocument>", $d);
		
				$pa_options['fromString'] = $d;
				$ps_source = null;
			}	
		}
		
		if (file_exists($ps_source)) { 			
			caExec($ps_mediainfo_path." --Output=PBCore2 ".caEscapeShellArg($ps_source), $va_output, $vn_return);
			if (!is_array($va_output) || !sizeof($va_output)) { return null; }
			$xml = join("\n", $va_output);
			
			if(!($this->opo_xml = @DOMDocument::loadXML($xml))) { return false;}
		} elseif($str = caGetOption('fromString', $pa_options, null))  {
			if(!($this->opo_xml = @DOMDocument::loadXML($str))) { return false;}
		} else {
			return false;
		}
		try {
			$this->opo_xpath = new DOMXPath($this->opo_xml);
		} catch (Exception $e) {
			return false;
		}
		
		if ($this->ops_xml_namespace_prefix && $this->ops_xml_namespace) {
			$this->opo_xpath->registerNamespace($this->ops_xml_namespace_prefix, $this->ops_xml_namespace);
		}
		
		$this->opo_handle = $this->opo_xpath->query($this->ops_xpath, null, $this->opb_register_root_tag);

		$this->opn_current_row = 0;
		return $this->opo_handle ? true : false;
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
