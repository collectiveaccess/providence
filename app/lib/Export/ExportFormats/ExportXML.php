<?php
/** ---------------------------------------------------------------------
 * ExportFormatXML.php : defines XML export format
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2024 Whirl-i-Gig
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

class ExportXML extends BaseExportFormat {
	# ------------------------------------------------------
	/**
	 *
	 */
	private $opo_dom;
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct(){
		$this->ops_name = 'XML';
		$this->ops_element_description = _t('Values prefixed with @ reference XML attributes. All other values define XML elements. The usual restrictions and naming conventions for XML elements and attributes apply.');

		$this->opo_dom = new DOMDocument('1.0','utf-8'); // are those settings?
		$this->opo_dom->formatOutput = true;
		$this->opo_dom->preserveWhiteSpace = false;

		parent::__construct();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getFileExtension($settings) {
		return 'xml';
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getContentType($settings) {
		return 'text/xml';
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function processExport($data,$options=array()){
		$single_record = caGetOption('singleRecord', $options);
		//$rdf_mode = caGetOption('rdfMode', $options);
		
		$settings = $options['settings'] ?? [];
		
		$strip_cdata = (bool)caGetOption('stripCDATA', $settings, false);
		$dedupe = (bool)caGetOption('deduplicate', $settings, false);

		//caDebug($data,"Data to build XML from");
		
		$this->log("XML export formatter: Now processing export tree ...");

		// XML exports should usually have only one top-level element (i.e. one root).
		if(sizeof($data)!=1){ return false; }

		$this->processItem(array_pop($data),$this->opo_dom, ['stripCDATA' => $strip_cdata]);
		
		if($dedupe) {
			$this->log("XML export formatter: Deduplicating export tree ...");
			$this->opo_dom = $this->dedupe($this->opo_dom, $this->opo_dom);
		}
		
		$this->log(_t("XML export formatter: Done processing export tree ..."));

		// when dealing with a record set export, we don't want <?xml tags in front of each record
		// that way we can simply dump a sequence of records in a file and have well-formed XML as result
		$return = ($single_record ? $this->opo_dom->saveXML() : $this->opo_dom->saveXML($this->opo_dom->firstChild));

		if($strip_cdata) {
			$return = str_replace('<![CDATA[', '', $return);
			$return = str_replace(']]>','',$return);
		}

		return $return;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function dedupe($doc, $parent) {
		$acc = [];
		$to_remove = [];
		
		$c = $parent->childNodes;
		foreach($c as $n) {
			$t = $doc->saveXML($n);
			$ts = md5($t);
			 if($acc[$ts]) {	// is dupe
			 	array_push($to_remove, $n);
			 } else {
			 	$acc[$ts] = true;
			 	$this->dedupe($doc, $n);
			 }
		}
		foreach($to_remove as $r) {
			$parent->removeChild($r);
		}
		return $doc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function processItem($item, $po_parent, $options=null){
		if(!($po_parent instanceof DOMNode)) return false;

		//caDebug($item,"Data passed to XML item processor");

		$element = $item['element'];
		$text = (isset($item['text']) ? $item['text'] : null);

		$this->log(_t("XML export formatter: Processing element or attribute '%1' with text '%2' and parent element '%3' ...", $element, $text, $po_parent->nodeName));

		$first = substr($element,0,1);

		if($first == "@"){ // attribute
			// attributes are only valid for DOMElement, not for DOMDocument
			if(!($po_parent instanceof DOMElement)) return false;

			$rest = substr($element,1);
			$po_parent->setAttribute($rest, $text);
			$vo_new_element = $po_parent; // attributes shouldn't have children, but still ...
		} else { // element
			$escaped_text = caEscapeForXML($text);

			if(strlen($text)>0){

				if($escaped_text != $text) { // sth was escaped by caEscapeForXML -> wrap in CDATA
					if(caGetOption('stripCDATA', $options, false)) {
						$vo_new_element = $this->opo_dom->createElement($element, $escaped_text);
					} else {
						$vo_new_element = $this->opo_dom->createElement($element);
						$vo_new_element->appendChild(new DOMCdataSection($text));
					}
				}  else { // add text as-is using DOMDocument
					$vo_new_element = $this->opo_dom->createElement($element,$text);
				}
				
			} else {
				$vo_new_element = $this->opo_dom->createElement($element);
			}
			$po_parent->appendChild($vo_new_element);
		}

		if(is_array($item['children'])){
			foreach($item['children'] as $child){
				if(!empty($child)){
					$this->processItem($child,$vo_new_element, $options);
				}
			}
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getMappingErrors($t_mapping){
		$errors = array();

		$top = $t_mapping->getTopLevelItems(array('dontIncludeVariables' => true));
		if(sizeof($top)!==1){
			$errors[] = _t("XML documents must have exactly one root element. Found %1.", sizeof($top));
		}

		foreach($top as $item){
			$errors = array_merge($errors,$this->getMappingErrorsForItem($item));
		}

		return $errors;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	private function getMappingErrorsForItem($item){
		$errors = array();
		$t_item = new ca_data_exporter_items($item['item_id']);
		
		// check if element is attribute and if so, if it's valid and if it has a non-attribute parent it belongs to
		$element = $t_item->get('element');
		$first = substr($element,0,1);
		if($first == "@"){
			$attribute_name = substr($element,1);
			if(!preg_match("/^[_:A-Za-z][-._:A-Za-z0-9]*$/",$attribute_name)){
				$errors[] = _t("Invalid XML attribute name '%1'",$attribute_name);
			}

			$t_parent = new ca_data_exporter_items($t_item->get('parent_id'));
			$parent_first = substr($t_parent->get('element'),0,1);
			if($parent_first == "@" || !$t_parent->get('element')){
				$errors[] = _t("XML attribute '%1' doesn't have a valid parent element",$attribute_name);	
			}
		} else { // plain old XML element -> check for naming convention
			if(!preg_match("/^[_:A-Za-z][-._:A-Za-z0-9]*$/",$element)){
				$errors[] = _t("Invalid XML element name '%1'",$element);
			}			
		}

		foreach($t_item->getHierarchyChildren() as $child){
			$errors = array_merge($errors,$this->getMappingErrorsForItem($child));
		}

		return $errors;
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['XML'] = array(
	'stripCDATA' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t("Strip CDATA"),
		'description' => _t("By default the exporter wraps field content that contains invalid XML in CDATA sections to make sure the XML is valid regardless of the field content. If this option is set, the exporter explicitly strips the CDATA tags before returning the XML text. Use only if you know what you're doing!")
	),
	'deduplicate' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'default' => 0,
		'width' => 1, 'height' => 1,
		'label' => _t("Remove duplicated tags?"),
		'description' => _t("Remove duplicate tags when sharing a parent.")
	),
);
