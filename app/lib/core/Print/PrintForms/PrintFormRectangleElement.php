<?php
/** ---------------------------------------------------------------------
 * app/lib/core/print/PrintForms/PrintFormRectangleElement.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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

class PrintFormRectangleElement extends PrintFormElements {
	# ------------------------------------------------------------------
	var $opo_form;
	
	var $ops_value = null;
	var $opa_options;
	var $opa_element_info;
	
	# ------------------------------------------------------------------
	public function PrintFormRectangleElement($po_form, $ps_name, $ps_value, $pa_options=null) {
		parent::PrintFormElements($po_form, $ps_name);
		$this->ops_value = null;
		$this->setValue($ps_value, $pa_options);
		
		$this->opa_element_info = $this->opo_form->hasElement($this->getName());
	}
	# ------------------------------------------------------------------
	public function setValue($ps_value, $pa_options=null) {
		if (!isset($ps_value)) { $ps_value = null; }
		$this->ops_value = $ps_value;
		$this->opa_options = $pa_options;
	}
	# ------------------------------------------------------------------
	public function getValue() {
		return (!is_null($this->ops_value)) ? $this->ops_value : $this->opa_element_info['default'];
	}
	# ------------------------------------------------------------------
	public function render($po_pdf, $pn_x=null, $pn_y=null) {
		switch(get_class($this->opo_form)) {
			case 'PrintForms':
				if ($va_element_info = $this->opa_element_info) {					
					$va_style = $this->opo_form->getStyle($va_element_info['style']);
					$vn_size = $this->opo_form->getValueInPoints($va_style['size']);
					
					$vn_x = $this->opo_form->getValueInPoints($va_element_info['left']);
					$vn_y = $this->opo_form->getFormSetting('pageHeight') - $this->opo_form->getValueInPoints($va_element_info['top']);
				} else {
					return false;
				}
				break;
			case 'PrintSubForms':
				if ($va_element_info = $this->opa_element_info) {					
					$va_style = $this->opo_form->getStyle($va_element_info['style']);
					$vn_size = $this->opo_form->getValueInPoints($va_style['size']);
					
					$vn_x = $pn_x + $this->opo_form->getValueInPoints($va_element_info['left']);
					$vn_y = $pn_y - $this->opo_form->getValueInPoints($va_element_info['top']);
				} else {
					return false;
				}
				break;
			default:
				$this->opo_form->postError(2275, _t("Invalid class '%1'", get_class($this->opo_form)), "PrintFormTextElement->render()");
				break;
		}
		
		
		$vn_width =  $this->opo_form->getValueInPoints($va_element_info['width']);
		$vn_height = $this->opo_form->getValueInPoints($va_element_info['height']);
		
		if (!$vs_align = $va_element_info['align']) {
			$vs_align = $va_style['align'];
		}
		
		
		if (!($vn_padding =  $va_element_info['padding'])) {
			$vn_padding = $va_style['padding'];
		}
		if (!($vn_border =  $va_element_info['border'])) {
			$vn_border = $va_style['border'];
		}
		if (!$vn_border) { $vn_border = 0; }
		
		if (!($vs_background =  $va_element_info['background'])) {
			$vs_background = $va_style['background'];
		}
		if (!$vs_background) {
			$vs_background = 'FFFFFF';
		}
		
		$vn_cur_x = $vn_x;
		$vn_cur_y = $vn_y;
			
		if ($this->opo_form->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
			$po_page = $po_pdf->pages[sizeof($po_pdf->pages)-1];
			if (($vn_y - $vn_cur_y) >= ($vn_height * cos($vn_rotate_radians)) + ($vn_width * sin($vn_rotate_radians))) {
				break;
			}
			if (($vn_cur_x - $vn_x) >= ($vn_width * cos($vn_rotate_radians)) + ($vn_height * sin($vn_rotate_radians))) {
				break;
			}
			
			$po_page->setFillColor(new Zend_Pdf_Color_Html($vs_background));
			$po_page->setLineWidth(floatval($vn_border));
			
			$po_page->drawRectangle($vn_x, $vn_y, $vn_x + $vn_width, $vn_y - $vn_height + $vn_size, (floatval($vn_border) > 0) ? Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE : Zend_Pdf_Page::SHAPE_DRAW_FILL);
			
			$vn_cur_x += ($vn_leading * sin($vn_rotate_radians));
			$vn_cur_y -= ($vn_leading * cos($vn_rotate_radians));
		} else {
			if (($vn_y - $vn_cur_y) >= ($vn_height * cos($vn_rotate_radians)) + ($vn_width * sin($vn_rotate_radians))) {
				break;
			}
			if (($vn_cur_x - $vn_x) >= ($vn_width * cos($vn_rotate_radians)) + ($vn_height * sin($vn_rotate_radians))) {
				break;
			}
			
			if ($vn_border > 0) {
				$po_pdf->setlinewidth($vn_border);
			}
			$po_pdf->setdash(0, 0);
			$po_pdf->moveTo($vn_x, $vn_y + $vn_size);
			
			$po_pdf->lineTo($vn_x + $vn_width, $vn_y + $vn_size);
			$po_pdf->lineTo($vn_x + $vn_width, $vn_y - $vn_height + $vn_size);
			$po_pdf->lineTo($vn_x, $vn_y - $vn_height + $vn_size);
			$po_pdf->lineTo($vn_x, $vn_y + $vn_size);
			$po_pdf->stroke();
			
			$vn_cur_x += ($vn_leading * sin($vn_rotate_radians));
			$vn_cur_y -= ($vn_leading * cos($vn_rotate_radians));
		}
			
		return true;
	}
	# ------------------------------------------------------------------
}
?>