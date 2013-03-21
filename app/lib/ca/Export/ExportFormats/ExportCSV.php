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

require_once(__CA_LIB_DIR__.'/ca/Export/BaseExportFormat.php');

class ExportCSV extends BaseExportFormat {
	# ------------------------------------------------------
	
	# ------------------------------------------------------
	public function __construct(){
		$this->ops_name = 'CSV';
		$this->ops_element_description = _t('Values are column numbers (indexing starts at 1).');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function getFileExtension($pa_settings) {
		return 'csv';
	}
	# ------------------------------------------------------
	public function getContentType($pa_settings) {
		return 'text/csv';
	}
	# ------------------------------------------------------
	public function processExport($pa_data,$pa_options=array()){
		//caDebug($pa_data,"Data to build CSV from");
		//caDebug($pa_options,"Export format options");
		$va_csv = array();
		
		foreach($pa_data as $pa_item){
			$vn_column = intval($pa_item['element']);
			$va_csv[$vn_column] = $pa_item['text'];
		}

		$vn_rightmost_column = max(array_keys($va_csv));

		// fill empty indexes to allow more mapping flexibility
		for($i=1; $i<=$vn_rightmost_column; $i++){
			if(!isset($va_csv[$i])){
				$va_csv[$i] = null;
			}
		}

		ksort($va_csv);

		$vs_delimiter = (isset($pa_options['settings']['CSV_delimiter']) ? $pa_options['settings']['CSV_delimiter'] : ',');
		$vs_enclosure = (isset($pa_options['settings']['CSV_enclosure']) ? $pa_options['settings']['CSV_enclosure'] : '"');

		//caDebug($va_csv);

		return $vs_enclosure . join($vs_enclosure.$vs_delimiter.$vs_enclosure,$va_csv) . $vs_enclosure;
	}
	# ------------------------------------------------------
	public function getMappingErrors($t_mapping){
		$va_errors = array();
		$va_top = $t_mapping->getTopLevelItems();

		foreach($va_top as $va_item){
			$t_item = new ca_data_exporter_items($va_item['item_id']);

			$vs_element = $va_item['element'];
			if(!is_numeric($vs_element)){
				$va_errors[] = _t("Element %1 is not numeric",$vs_element);
			}
			if(intval($vs_element) <= 0){
				$va_errors[] = _t("Element %1 is not a positive number",$vs_element);	
			}

			if(sizeof($t_item->getHierarchyChildren())>0){
				$va_errors[] = _t("CSV exports can't be hierarchical",$vs_element);
			}
		}

		return $va_errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['CSV'] = array(
	'CSV_delimiter' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => ',',
		'label' => _t('Delimiter'),
		'description' => _t('Character used to separate values. Typical values include commas, semicolons or tabs.')
	),
	'CSV_enclosure' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => '"',
		'label' => _t('Enclosure'),
		'description' => _t('Character used to enclose the text content in the export. Typical values are single or double quotes.')
	),
);
