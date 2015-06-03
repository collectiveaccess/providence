<?php
/** ---------------------------------------------------------------------
 * ExportXMP.php : defines XMP export format
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
require_once(__CA_LIB_DIR__.'/core/Parsers/MediaMetadata/XMPParser.php');

class ExportXMP extends BaseExportFormat {
	# ------------------------------------------------------
	
	# ------------------------------------------------------
	public function __construct() {
		$this->ops_name = 'XMP';
		$this->ops_element_description = _t('Values are XMP field names without prefix');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function getFileExtension($pa_settings) {
		return 'xmp';
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
		$o_parser = new XMPParser();
		$va_available_fields = $o_parser->getAvailableFields();

		foreach($va_top as $va_item) {
			$t_item = new ca_data_exporter_items($va_item['item_id']);

			$vs_element = $va_item['element'];
			if(!in_array($vs_element, $va_available_fields)) {
				$va_errors[] = _t("Element %1 is not valid for XMP", $vs_element);
			}

			if(sizeof($t_item->getHierarchyChildren())>0) {
				$va_errors[] = _t("XMP exports can't be hierarchical", $vs_element);
			}
		}

		return $va_errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['XMP'] = array();
