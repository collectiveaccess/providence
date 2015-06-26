<?php
/** ---------------------------------------------------------------------
 * ExportExifTool.php : defines ExifTool export format
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @subpackage Export
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportFormat.php');

class ExportExifTool extends BaseExportFormat {
	# ------------------------------------------------------
	
	# ------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'ExifTool';
		$this->ops_element_description = _t('Values are ExifTool XML element names. See http://www.sno.phy.queensu.ca/~phil/exiftool/metafiles.html#xml');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function getFileExtension($pa_settings) {
		return 'xml';
	}
	# ------------------------------------------------------
	public function getContentType($pa_settings) {
		return 'text/xml';
	}
	# ------------------------------------------------------
	public function processExport($pa_data,$pa_options=array()) {
		var_dump($pa_data);

		return 'testtest';
	}
	# ------------------------------------------------------
	public function getMappingErrors($t_mapping) {
		$va_errors = array();
		$va_top = $t_mapping->getTopLevelItems();

		foreach($va_top as $va_item) {

		}

		return $va_errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['ExifTool'] = array();
