<?php
/** ---------------------------------------------------------------------
 * app/lib/core/print/PrintForms/PrintFormImageElement.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2009 Whirl-i-Gig
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
 * @subpackage Print
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__.'/core/Configuration.php');
require_once(__CA_LIB_DIR__.'/core/Error.php');
require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintFormElements.php');


class PrintFormImageElement extends PrintFormElements {
	# ------------------------------------------------------------------
	var $opo_form;
	
	var $ops_value;
	var $opa_options;
	var $opa_element_info;
	
	# ------------------------------------------------------------------
	function PrintFormImageElement($po_form, $ps_name, $ps_value, $pa_options=null) {
		parent::PrintFormElements($po_form, $ps_name);
		
		$this->setValue($ps_value, $pa_options);
		
		$this->opa_element_info = $this->opo_form->hasElement($this->getName());
	}
	# ------------------------------------------------------------------
	function setValue($ps_value, $pa_options=null) {
		$this->ops_value = $ps_value;
		$this->opa_options = $pa_options;
	}
	# ------------------------------------------------------------------
	function getValue() {
		return $this->ops_value ? $this->ops_value : $this->opa_element_info['default'];
	}
	# ------------------------------------------------------------------
	function render($po_pdf, $pn_x=null, $pn_y=null) {
		switch(get_class($this->opo_form)) {
			case 'PrintForms':
				if ($va_element_info = $this->opa_element_info) {
					$vn_x = $this->opo_form->getValueInPoints($va_element_info['left']);
					$vn_y = $this->opo_form->getFormSetting('pageHeight') - $this->opo_form->getValueInPoints($va_element_info['top']);
					
				}
				break;
			case 'PrintSubForms':
				if ($va_element_info = $this->opa_element_info) {
					$vn_x = $pn_x + $this->opo_form->getValueInPoints($va_element_info['left']);
					$vn_y = $pn_y - $this->opo_form->getValueInPoints($va_element_info['top']);
				}
				break;
			default:
				$this->opo_form->postError(2275, _t("Invalid class '%1'", get_class($this->opo_form)), "PrintFormImageElement->render()");
				break;
		}
		
		$va_style = $this->opo_form->getStyle($va_element_info['style']);
		$vn_w = $this->opo_form->getValueInPoints($va_element_info['width']);
		$vn_h = $this->opo_form->getValueInPoints($va_element_info['height']);
		
		if ($this->opo_form->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
			$o_media = new Media();
			if ($o_media->read($this->getValue())) {
				$va_properties = $o_media->getProperties();
				
				$vn_image_width = $va_properties['width'];
				$vn_image_height = $va_properties['height'];
				if ($vn_image_width > $vn_image_height) {
					$vn_r = $vn_w/$vn_image_width;
				} else {
					$vn_r = $vn_h/$vn_image_height;
				}
				$vn_image_width *= $vn_r;
				$vn_image_height *= $vn_r;
				
				$po_page = $po_pdf->pages[sizeof($po_pdf->pages) - 1];
				try {
					if ($vo_image = $this->opo_form->loadImage($po_pdf, $this->getValue())) {
						$po_page->drawImage($vo_image, $vn_x, $vn_y - $vn_h, $vn_x + $vn_image_width, $vn_y - $vn_h + $vn_image_height);
					} else {
						return false;
					}
				} catch (Exception $e) {
					return false;
				}
			}
		} else {
			if ($vn_image_ref = $this->opo_form->loadImage($po_pdf, $this->getValue())) {
				if(!($vs_display = $va_element_info['display'])) {
					$vs_display = $va_style['display'];
				}
				if (!in_array($vs_display, array('nofit', 'clip', 'meet', 'auto', 'slice', 'entire'))) {
					$vs_display = 'meet';
				}
				$vs_opts = "fitmethod=$vs_display boxsize={".$vn_w." ".$vn_h."}";
				
				if ($va_element_info['rotate']) {
					$vs_opts .= ' rotate='.intval($va_element_info['rotate']);
				} else {
					if ($va_style['rotate']) {
						$vs_opts .= ' rotate='.intval($va_style['rotate']);
					}
				}
				$po_pdf->fit_image($vn_image_ref, $vn_x, $vn_y - $vn_h, $vs_opts);
			}
		}
		return true;
	}
	# ------------------------------------------------------------------
}
?>