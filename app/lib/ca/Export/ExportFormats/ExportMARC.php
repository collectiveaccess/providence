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
		require_once(__CA_LIB_DIR__.'/core/Parsers/File_MARC/MARC.php');

		$this->ops_name = 'MARC';
		$this->ops_element_description = _t('Values reference a combination of MARC 21 field tags and associated indicators separated by a forward slash ("/"), e.g. "300/##". For further information on how to create a MARC mapping, please refer to the CollectiveAccess online documentation.');
		parent::__construct();
	}
	# ------------------------------------------------------
	public function getFileExtension($pa_settings) {
		if(!($vs_format = $pa_settings['MARC_outputFormat'])) { return 'txt'; }
		
		switch($vs_format){
			case 'raw':
				return 'mrc';
			case 'xml':
				return 'xml';
			case 'readable':
			default:
				return 'txt';
		}
	}
	# ------------------------------------------------------
	public function getContentType($pa_settings) {
		if(!($vs_format = $pa_settings['MARC_outputFormat'])) { return 'text/plain'; }
		
		switch($vs_format){
			case 'raw':
				return 'application/marc';
			case 'xml':
				return 'text/xml';
			case 'readable':
			default:
				return 'text/plain';
		}
	}
	# ------------------------------------------------------
	public function processExport($pa_data,$pa_options=array()){
		$pb_single_record = (isset($pa_options['singleRecord']) && $pa_options['singleRecord']);

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
					$vs_string = $o_record->toXML();
		
					$vo_dom = new DOMDocument('1.0', 'utf-8');
					$vo_dom->preserveWhiteSpace = false;
					$vo_dom->loadXML($vs_string);
					$vo_dom->formatOutput = true;
					
					// when dealing with a record set export, we don't want <?xml tags in front so
					// that we can simply dump each record in a file and have valid XML as result
					return ($pb_single_record ? $vo_dom->saveXML() : $vo_dom->saveXML($vo_dom->firstChild));
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
		$va_errors = array();

		$va_top = $t_mapping->getTopLevelItems();

		foreach($va_top as $va_item){
			$t_item = new ca_data_exporter_items($va_item['item_id']);

			$vs_element = $va_item['element'];
			if(stripos($vs_element,"/") == 3){ // data field in format 300/##

				$va_split = explode("/",$vs_element);
				if(count($va_split) != 2){
					$va_errors[] = _t("Invalid MARC element definition %1",$vs_element);
					continue;
				}

				$vs_tag = $va_split[0];
				$vs_indicators = $va_split[1];

				if((!is_numeric($vs_tag)) || strlen($vs_tag)!=3) {
					// ideally we would check if the tag is valid for MARC21 but that may be a bit excessive
					$va_errors[] = _t("Invalid tag for MARC data field definition %1",$vs_element);
				}
				if((strlen($vs_indicators) != 2) || (!preg_match("/^[a-z0-9\#]{2,2}$/",$vs_indicators))){
					$va_errors[] = _t("Invalid indicator definition for MARC field %1",$vs_element);
				}

				// subfields
				$va_errors = array_merge($va_errors,$this->getSubfieldErrors($t_item,true));

			} else if(is_numeric($vs_element)) { // control field, e.g. 001
				if(strlen($vs_element)!=3) {
					// ideally we would check if the tag is valid for MARC21 but that may be a bit excessive
					$va_errors[] = _t("Invalid tag for MARC control field definition %1",$vs_element);
				}

				$va_errors = array_merge($va_errors,$this->getSubfieldErrors($t_item,false));
			} else { // error, top level only allows data and control fields
				$va_errors[] = _t("Invalid top-level MARC element definition %1",$vs_element);

				$va_errors = array_merge($va_errors,$this->getSubfieldErrors($t_item,false));
			}
		}

		return $va_errors;
	}
	# ------------------------------------------------------
	private function getSubfieldErrors($t_item,$pb_subfields_allowed){
		$va_errors = array();

		$va_children = $t_item->getHierarchyChildren();
		if((!$pb_subfields_allowed) && (count($va_children)>0)){
			$va_errors[] = _t("Mapping element %1 can't have subfields",$t_item->get("element"));
			return $va_errors;
		}

		foreach($t_item->getHierarchyChildren() as $va_child){
			$vs_element = $va_child['element'];
			$t_child = new ca_data_exporter_items($va_child['item_id']);

			if(!preg_match("/^[a-z0-9]{1,1}$/",$vs_element)){
				$va_errors[] = _t("Subfield definition %1 is invalid",$vs_element);
			}

			// subfields can't have more subfields
			$va_errors = array_merge($va_errors,$this->getSubfieldErrors($t_child,false));
		}

		return $va_errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['MARC'] = array(
	'MARC_outputFormat' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 'readable',
		'options' => array(
			'readable' => 'readable',
			'raw' => 'raw',
			'xml' => 'xml',
		),
		'label' => _t('MARC export output format'),
		'description' => _t('Set output format. Currently supported: human-readable MARC21, raw MARC for saving into MARC files and MARCXML.')
	),
);
