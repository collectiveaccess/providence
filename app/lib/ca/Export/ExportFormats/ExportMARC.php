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

class ExportMARC extends BaseExportFormat {
	# ------------------------------------------------------
	
	# ------------------------------------------------------
	public function __construct(){
		// only require this when the format is actually used
		// otherwise this would probably be slightly annoying
		require_once('File/MARC.php');

		$this->ops_name = 'MARC';
		$this->ops_element_description = _t('Values reference a combination of MARC 21 field tags and associated indicators separated by a forward slash ("/"), e.g. "300/##". For further information on how to create a MARC mapping, please refer to the CollectiveAccess online documentation.');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function processExport($pa_data,$pa_options=array()){
		//caDebug($pa_data,"Data to build MARC from");
		//caDebug($pa_options,"Export format options");

		$o_record = new File_MARC_Record();

		foreach($pa_data as $va_item){
			$vs_element = $va_item['element'];
			if(stripos($vs_element, "/")!==false){ // data field
				$va_split = explode("/", $vs_element);
				$vs_tag = $va_split[0];
				$vs_ind1 = substr($va_split[1], 0, 1);
				$vs_ind2 = substr($va_split[1], 1, 1);
				$va_subfields = array();

				// process sub-fields
				if(is_array($va_item['children'])){
					foreach($va_item['children'] as $va_child){
						$va_subfields[] = new File_MARC_Subfield($va_child['element'], $va_child['text']);
					}	
				}

				$o_field = new File_MARC_Data_field($vs_tag,$va_subfields,$vs_ind1,$vs_ind2);

			} else { // simple control field
				$o_field = new File_MARC_Control_Field($vs_element,$va_item['text']);
			}

			$o_record->appendField($o_field);
		}

		if(isset($pa_options['settings']['MARC_outputFormat'])){
			switch($pa_options['settings']['MARC_outputFormat']){
				case 'raw':
					return $o_record->toRaw();
				case 'xml':
					return $o_record->toXML();
				case 'readable':
				default:
					return $o_record->__toString();
			}
		} else {
			return $o_record->__toString();
		}
	}
	# ------------------------------------------------------
	public function getMappingErrors($t_mapping){
		return array();
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['MARC'] = array(
	'MARC_outputFormat' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => '',
		'options' => array(
			'readable' => 'readable',
			'raw' => 'raw',
			'xml' => 'xml',
		),
		'label' => _t('MARC export output format'),
		'description' => _t('Set output format. Currently supported: human-readable MARC21, raw MARC for saving into MARC files and MARCXML.')
	),
);
