<?php
/** ---------------------------------------------------------------------
 * app/lib/core/print/PrintForms/PrintSubForms.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2010 Whirl-i-Gig
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

class PrintSubForms extends BaseObject {
	# ------------------------------------------------------------------
	var $opo_form;
	var $opo_form_elements;
	
	var $opn_width;
	var $opn_height;
	
	var $opn_outline_width = 0;
	var $opn_outline_dash = 0;
	
	var $opa_errors;
	var $opb_error_output = false;
	# ------------------------------------------------------------------
	function PrintSubForms($po_form) {
		$this->opa_errors = array();
		$this->opo_form = $po_form;
		$this->clear();
		
		$va_dim = $this->opo_form->getSubFormDimensions();
		$this->opn_width = $this->opo_form->getValueInPoints($va_dim['width']);
		$this->opn_height = $this->opo_form->getValueInPoints($va_dim['height']);
		
	}
	# ------------------------------------------------------------------
	function setOutline($pn_width, $pn_dash=0) {
		if ($pn_width >= 0) {
			$this->opn_outline_width = $pn_width;
			if ($pn_dash < 0) { $pn_dash = 0; }
			$this->opn_outline_dash = $pn_dash;
			
			return true;
		}
		$this->postError(2280, _t("Outline settings are invalid"), "PrintSubForms->setOutline()");
		return false;
	}
	# ------------------------------------------------------------------
	function clear() {
		$this->opo_form_elements = array();
	}
	# ------------------------------------------------------------------
	function getElements() {
		return $this->opo_form->getSubFormLayout();
	}
	# ------------------------------------------------------------------
	function hasElement($ps_name) {
		$va_layout = $this->getElements();
		return $va_layout[$ps_name] ? $va_layout[$ps_name] : false;
	}
	# ------------------------------------------------------------------
	function setElement($ps_name, $ps_value) {
		if ($va_element = $this->hasElement($ps_name)) {
			if (!is_object($this->opo_form_elements[$ps_name])) {
				switch($va_element['type']) {
					case 'text':
						$this->opo_form_elements[$ps_name] = new PrintFormTextElement($this, $ps_name, $ps_value);
						break;
					case 'image':
						$this->opo_form_elements[$ps_name] = new PrintFormImageElement($this, $ps_name, $ps_value);
						break;
					case 'barcode':
						$this->opo_form_elements[$ps_name] = new PrintFormBarcodeElement($this, $ps_name, $ps_value);
						break;
					case 'qrcode':
						$this->opo_form_elements[$ps_name] = new PrintFormQRCodeElement($this, $ps_name, $ps_value);
						break;
					case 'rectangle':
						$this->opo_form_elements[$ps_name] = new PrintFormRectangleElement($this, $ps_name, $ps_value);
						break;
					default:
						$this->postError(2240, _t("Invalid subform element type '%1'", $va_element['type']), "PrintSubForms->setElement()");
						return false;
						break;
				}
			} else {
				$this->opo_form_elements[$ps_name]->setValue($ps_value);
			}
			return $this->opo_form_elements[$ps_name];
		}
		$this->postError(2255, _t("Subform element '%1' does not exist", $ps_name), "PrintSubForms->setElement()");
		return false;
	}
	# ------------------------------------------------------------------
	function setElements($pa_values) {
		foreach($pa_values as $vs_name => $vs_value) {
			$this->setElement($vs_name, $vs_value);
		}
		return true;
	}
	# ------------------------------------------------------------------
	function render($po_pdf, $pn_x, $pn_y) {
		foreach($this->getElements() as $vs_name => $va_info) {
			if (!($o_form_element = $this->opo_form_elements[$vs_name])) {
				$o_form_element = $this->setElement($vs_name, null);
			}
			$o_form_element->render($po_pdf, $pn_x, $pn_y);

			if ($this->opn_outline_width > 0) {
				if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
					$po_page = $po_pdf->pages[sizeof($po_pdf->pages) - 1];
					if ($this->opn_outline_dash > 0) {
						$po_page->setLineDashingPattern(array($this->opn_outline_dash, $this->opn_outline_dash));
					}
				} else {
					if ($this->opn_outline_dash > 0) {
						$po_pdf->setDash($this->opn_outline_dash, $this->opn_outline_dash);
					}
				}
					
				if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
					
					$po_page->setLineWidth($this->opn_outline_width);
					$po_page->drawRectangle($pn_x, $pn_y, $pn_x + $this->opn_width, $pn_y  - $this->opn_height, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
				} else {
					$po_pdf->setlinewidth($this->opn_outline_width);
					
					$po_pdf->moveTo($pn_x, $pn_y);
					
					$po_pdf->lineTo($pn_x + $this->opn_width, $pn_y);
					$po_pdf->lineTo($pn_x + $this->opn_width, $pn_y - $this->opn_height);
					$po_pdf->lineTo($pn_x, $pn_y - $this->opn_height);
					$po_pdf->lineTo($pn_x, $pn_y);
					$po_pdf->stroke();
				}
			}
		}
		return true;
	}
	# ------------------------------------------------------------------
	function setFont($po_pdf, $ps_fontname, $pn_size) {
		return $this->opo_form->setFont($po_pdf, $ps_fontname, $pn_size);
	}
	# ------------------------------------------------------------------
	function getStyle($ps_style) {
		return $this->opo_form->getStyle($ps_style);
	}
	# ------------------------------------------------------------------
	# Image handling
	# ------------------------------------------------------------------
	function loadImage($po_pdf, $ps_image_path) {
		return $this->opo_form->loadImage($po_pdf, $ps_image_path);
	}
	# ------------------------------------------------------------------
	function getValueInPoints($ps_value) {
		return $this->opo_form->getValueInPoints($ps_value);
	}
	# ------------------------------------------------------------------
	function getPDFLibrary() {
		return $this->opo_form->getPDFLibrary();
	}
	# --------------------------------------------------------------------------------------------
}
?>