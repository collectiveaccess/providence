<?php
/** ---------------------------------------------------------------------
 * ExportFormatXML.php : defines XML export format
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
require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
require_once(__CA_MODELS_DIR__.'/ca_data_exporter_items.php');

class ExportXML extends BaseExportFormat {
	# ------------------------------------------------------
	private $opo_dom;
	# ------------------------------------------------------
	public function __construct(){
		$this->ops_name = 'XML';
		$this->ops_element_description = _t('Values prefixed with @ reference XML attributes. All other values define XML elements. The usual restrictions and naming conventions for XML elements and attributes apply.');

		$this->opo_dom = new DOMDocument('1.0','utf-8'); // are those settings?

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
	public function processExport($pa_data,$pa_options=array()){
		$pb_single_record = (isset($pa_options['singleRecord']) && $pa_options['singleRecord']);

		//caDebug($pa_data,"Data to build XML from");

		// XML exports should usually have only one top-level element (i.e. one root).
		if(sizeof($pa_data)!=1){ return false; }

		$this->processItem(array_pop($pa_data),$this->opo_dom);
		
		// format, then reload as DOMDocument
		// unfortunately DOMDocument is still unable to format
		// arbitrary documents so we have to pre-format them
		$vs_xml = caFormatXML($this->opo_dom->saveXML());
		$vo_doc = new DOMDocument();
		$vo_doc->formatOutput = true;
		$vo_doc->preserveWhiteSpace = false;
		$vo_doc->loadXML($vs_xml);

		// when dealing with a record set export, we don't want <?xml tags in front so
		// that we can simply dump each record in a file and have valid XML as result
		return ($pb_single_record ? $vo_doc->saveXML() : $vo_doc->saveXML($vo_doc->firstChild));
	}
	# ------------------------------------------------------
	private function processItem($pa_item,$po_parent){
		if(!($po_parent instanceof DOMNode)) return false;

		//caDebug($pa_item,"Data passed to XML item processor");

		$vs_element = $pa_item['element'];
		$vs_text = (isset($pa_item['text']) ? $pa_item['text'] : null);
		$vs_first = substr($vs_element,0,1);

		if($vs_first == "@"){ // attribute
			// attributes are only valid for DOMElement, not for DOMDocument
			if(!($po_parent instanceof DOMElement)) return false;

			$vs_rest = substr($vs_element,1);
			$po_parent->setAttribute($vs_rest, $vs_text);
		} else { // element
			$vs_text = trim(caEscapeForXML($vs_text));
			if(strlen($vs_text)>0){
				$vo_new_element = $this->opo_dom->createElement($vs_element,$vs_text);
			} else {
				$vo_new_element = $this->opo_dom->createElement($vs_element);
			}
			$po_parent->appendChild($vo_new_element);
		}

		if(is_array($pa_item['children'])){
			foreach($pa_item['children'] as $va_child){
				if(!empty($va_child)){
					$this->processItem($va_child,$vo_new_element);
				}
			}
		}
	}
	# ------------------------------------------------------
	public function getMappingErrors($t_mapping){
		$va_errors = array();

		$va_top = $t_mapping->getTopLevelItems();
		if(sizeof($va_top)!==1){
			$va_errors[] = _t("XML documents must have exactly one root element");
		}

		foreach($va_top as $va_item){
			$va_errors = array_merge($va_errors,$this->getMappingErrorsForItem($va_item));
		}

		return $va_errors;
	}
	# ------------------------------------------------------
	private function getMappingErrorsForItem($pa_item){
		$va_errors = array();
		$t_item = new ca_data_exporter_items($pa_item['item_id']);


		// check if element is attribute and if so, if it's valid and if it has a non-attribute parent it belongs to
		$vs_element = $t_item->get('element');
		$vs_first = substr($vs_element,0,1);
		if($vs_first == "@"){
			$vs_attribute_name = substr($vs_element,1);
			if(!preg_match("/^[_:A-Za-z][-._:A-Za-z0-9]*$/",$vs_attribute_name)){
				$va_errors[] = _t("Invalid XML attribute name '%1'",$vs_attribute_name);
			}

			$t_parent = new ca_data_exporter_items($t_item->get('parent_id'));
			$vs_parent_first = substr($t_parent->get('element'),0,1);
			if($vs_parent_first == "@" || !$t_parent->get('element')){
				$va_errors[] = _t("XML attribute '%1' doesn't have a valid parent element",$vs_attribute_name);	
			}
		} else { // plain old XML element -> check for naming convention
			if(!preg_match("/^[_:A-Za-z][-._:A-Za-z0-9]*$/",$vs_element)){
				$va_errors[] = _t("Invalid XML element name '%1'",$vs_element);
			}			
		}

		foreach($t_item->getHierarchyChildren() as $va_child){
			$va_errors = array_merge($va_errors,$this->getMappingErrorsForItem($va_child));
		}

		return $va_errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['XML'] = array();

