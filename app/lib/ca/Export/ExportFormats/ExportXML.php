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

class ExportXML extends BaseExportFormat {
	# ------------------------------------------------------
	private $opo_dom;
	# ------------------------------------------------------
	public function __construct(){
		$this->ops_name = 'XML';
		$this->ops_element_description = _t('Values prefixed with @ reference XML attributes. All other values define XML elements. The usual restrictions and naming conventions for XML elements and attributes apply.');

		$this->opo_dom = new DOMDocument('1.0', 'utf-8'); // we might wanna put all this settings?

		parent::__construct();
	}
	# ------------------------------------------------------
	public function processExport($pa_data){
		// XML exports should usually have only one top-level element (i.e. one root).
		if(sizeof($pa_data)!=1){ return false; }

		$this->processItem(array_pop($pa_data),$this->opo_dom);

		/* hack for decent formatting */
		$vs_string = $this->opo_dom->saveXML();
		
		$vo_dom = new DOMDocument('1.0', 'utf-8');
		$vo_dom->preserveWhiteSpace = false;
		$vo_dom->loadXML($vs_string);
		$vo_dom->formatOutput = true;
		return $vo_dom->saveXML();
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
			$vo_new_element = $this->opo_dom->createElement($vs_element,caEscapeForXML($vs_text));
			$po_parent->appendChild($vo_new_element);
		}

		if(is_array($pa_item['children'])){
			foreach($pa_item['children'] as $va_child){
				$this->processItem($va_child,$vo_new_element);
			}
		}
	}
	# ------------------------------------------------------
}

BaseExportFormat::$s_format_settings['XML'] = array(
	// do we need this? will see ...
);

