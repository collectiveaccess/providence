<?php
/** ---------------------------------------------------------------------
 * ExportFormatMARC.php : defines MARC export format
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
 * @subpackage Export
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/Export/BaseExportFormat.php');

class ExportJSON extends BaseExportFormat {
	# ------------------------------------------------------

	# ------------------------------------------------------
	public function __construct(){
		$this->ops_name = 'JSON';
		$this->ops_element_description = _t('Values are field names.');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function getFileExtension($pa_settings) {
		return 'json';
	}
	# ------------------------------------------------------
	public function getContentType($pa_settings) {
		return 'application/json';
	}
	# ------------------------------------------------------
	public function processExport($pa_data,$pa_options=array()){
		$va_json = array();

        //TODO
        // Add ability for nested arrays?
		foreach($pa_data as $pa_item){
			$vs_column = $pa_item['element'];
			$va_json[$vs_column] = $pa_item['text'];
		}

		if(!sizeof($va_json)) { return ''; }
        # print json_encode($va_json)."\n";
		return json_encode($va_json);
	}
	# ------------------------------------------------------
	public function getMappingErrors($t_mapping){
		$va_errors = array();
		$va_top = $t_mapping->getTopLevelItems();

		foreach($va_top as $va_item){
            // No errors at this time
		}

		return $va_errors;
	}
	# ------------------------------------------------------
}
