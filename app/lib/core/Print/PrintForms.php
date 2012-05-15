<?php
/** ---------------------------------------------------------------------
 * app/lib/core/print/PrintForms.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2011 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintSubForms.php');
require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintFormTextElement.php');
require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintFormImageElement.php');
require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintFormQRCodeElement.php');
require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintFormBarcodeElement.php');
require_once(__CA_LIB_DIR__.'/core/Print/PrintForms/PrintFormRectangleElement.php');

define("__PDF_LIBRARY_PDFLIB__", 0);
define("__PDF_LIBRARY_ZEND__", 1);

class PrintForms extends BaseObject {
	# ------------------------------------------------------------------
	var $opn_pdf_library = __PDF_LIBRARY_PDFLIB__;		// PDF library to use
	
	var $ops_form;			// name of current form
	var $opa_form_settings;	// current form settings
	
	var $opa_form_page_elements;
	
	var $opo_config;
	var $opo_print_forms;
	var $opo_print_form_element_styles;
	
	var $opo_subforms;
	
	var $opa_fonts;
	var $opa_images;
	
	var $opa_starting_subform = 0;
	
	var $opa_errors;			# Array of error objects
	var $opb_error_output=false;      # If true, on error error message is printed and execution halted
	
	# ------------------------------------------------------------------
	public function PrintForms($ps_form_config) {
		$this->opa_errors = array();
		
		$this->setFormConfig($ps_form_config);
		$this->opo_config = Configuration::load();
		
		$this->clearForm();
		$this->opa_fonts = array();
		
		if (function_exists("PDF_begin_page_ext")) {
			$this->setPDFLibrary(__PDF_LIBRARY_PDFLIB__);
		} else {
			require_once(__CA_LIB_DIR__.'/core/Zend/Pdf.php');
			$this->setPDFLibrary(__PDF_LIBRARY_ZEND__);
		}
	}
	# ------------------------------------------------------------------
	public function setFormConfig($ps_form_config) {
		if(!$ps_form_config) { return false; }
		if (file_exists($ps_form_config)) {
			$this->opo_print_forms = Configuration::load($ps_form_config);
			if (file_exists($vs_form_styles_config = $this->opo_print_forms->get("print_form_element_styles"))) {
				$this->opo_print_form_element_styles = Configuration::load($vs_form_styles_config);
				return true;
			} else {
				$this->postError(2210, _t("Form styles configuration file '%1' does not exist", $vs_form_styles_config), "PrintForms->setFormConfig()");
			}
		} else {
			$this->postError(2200, _t("Form configuration file '%1' does not exist", $ps_form_config), "PrintForms->setFormConfig()");
		}
		return false;
	}
	# ------------------------------------------------------------------
	# Settings
	# ------------------------------------------------------------------
	public function setPDFLibrary($pn_lib) {
		$pn_lib = intval($pn_lib);
		if (($pn_lib >= 0) && ($pn_lib <= 1)) {
			$this->opn_pdf_library = $pn_lib;
		}
	}
	# ------------------------------------------------------------------
	public function getPDFLibrary() {
		return $this->opn_pdf_library;
	}
	# ------------------------------------------------------------------
	public function setForm($ps_form) {
		if (!$this->opo_print_forms) { return null; }
		if ($va_form_settings = $this->opo_print_forms->getAssoc($ps_form)) {
			$this->opa_form_settings = $va_form_settings;
			
			$this->ops_form = $ps_form;
			
			$this->clearForm();
			return true;
		} else {
			// form doesn't exist
			$this->postError(2220, _t("Form '%1' does not exist", $ps_form), "PrintForms->setForm()");
			return false;
		}
	}
	# ------------------------------------------------------------------
	static public function getAvailableForms($ps_form_config) {
		$o_print_forms = Configuration::load($ps_form_config);
		$va_form_settings = array();
		
		if (is_array($va_forms = $o_print_forms->getAssocKeys())) {
			foreach($va_forms as $vs_form_id) {
				if ($vs_form_id == 'print_form_element_styles') { continue; }
				
				$va_form_settings[$vs_form_id] = $o_print_forms->getAssoc($vs_form_id);
				$va_form_settings[$vs_form_id]['id'] = $vs_form_id;
			}
		}
		
		return $va_form_settings;
	}
	# ------------------------------------------------------------------
	public function getForm() {
		return $this->ops_form;
	}
	# ------------------------------------------------------------------
	public function clearForm() {
		$this->opa_form_page_elements = array();
		$this->opo_subforms = array();
	}
	# ------------------------------------------------------------------
	public function setStartingSubform($pn_start) {
		if ($pn_start < 0) { $pn_start = 0; }
		$this->opa_starting_subform = intval($pn_start);
	}
	# ------------------------------------------------------------------
	public function getStartingSubform() {
		return $this->opa_starting_subform;
	}
	# ------------------------------------------------------------------
	#
	# ------------------------------------------------------------------
	public function &getPageElements() {
		return $this->getFormSetting('pageElements');
	}
	# ------------------------------------------------------------------
	public function hasElement($ps_name) {
		$va_elements = $this->getPageElements();
		if ($va_element = $va_elements[$ps_name]) {
			return $va_element;
		} else {
			return false;
		}
	}
	# ------------------------------------------------------------------
	public function getNewPageElement($ps_name) {
		if ($va_element = $this->hasElement($ps_name)) {
			switch($va_element['type']) {
				case 'text':
				case 'pagenumber':
					$o_element = new PrintFormTextElement($this, $ps_name, null, $va_element);
					return $o_element;
					break;
				case 'image':
					$o_element = new PrintFormImageElement($this, $ps_name, null, $va_element);
					return $o_element;
					break;
				case 'barcode':
					$o_element = new PrintFormBarcodeElement($this, $ps_name, null, $va_element);
					return $o_element;
					break;
				case 'qrcode':
					$o_element = new PrintFormQRCodeElement($this, $ps_name, null, $va_element);
					return $o_element;
					break;
				case 'rectangle':
					$o_element = new PrintFormRectangleElement($this, $ps_name, null, $va_element);
					return $o_element;
					break;
				default:
					$this->postError(2230, _t("Invalid page element type '%1'", $va_element['type']), "PrintForms->getNewPageElement()");
					break;
			}
		} else {
			$this->postError(2250, _t("Page element '%1' does not exist", $ps_name), "PrintForms->getNewPageElement()");
		}
		return null;
	}
	# ------------------------------------------------------------------
	public function setPageElement($ps_name, $ps_value) {
		if (!$this->hasElement($ps_name)) {
			return false;
		}
		
		if (!($o_element = $this->getNewPageElement($ps_name))) {
			return false;
		}	
		$o_element->setValue($ps_value);
		$this->opa_form_page_elements[$ps_name] = $o_element;
		return true;
	}
	# ------------------------------------------------------------------
	# Sub-forms
	# ------------------------------------------------------------------
	public function &getSubFormInfo() {
		if (!($va_layout = $this->getFormSetting('subFormLayout'))) { return null; }
		return array(
			'layout' => 	$va_layout,
			'width' => 		$this->getFormSetting('subFormWidth'),
			'height' => 	$this->getFormSetting('subFormHeight')
		);
	}
	# ------------------------------------------------------------------
	public function getSubFormDimensions() {
		return array(
			'width' => 		$this->getFormSetting('subFormWidth'),
			'height' => 	$this->getFormSetting('subFormHeight')
		);
	}
	# ------------------------------------------------------------------
	public function &getSubFormLayout() {
		if (!($va_layout = $this->getFormSetting('subFormLayout'))) { return null; }
		return $va_layout;
	}
	# ------------------------------------------------------------------
	public function getSubFormFields() {
		if (!($va_elements = $this->getSubFormLayout())) { return array(); }
		$va_fields = array();
		foreach($va_elements as $va_element) {
			$va_fields[$va_element['field']] = true;
		}
		return array_keys($va_fields);
	}
	# ------------------------------------------------------------------
	public function getSubFormElementNames() {
		if (!($va_elements = $this->getSubFormLayout())) { return array(); }
		return array_keys($va_elements);
	}
	# ------------------------------------------------------------------
	public function getNewSubForm($pn_outline_width=0, $pn_outline_dash=0) {
		$o_subform = new PrintSubForms($this);
		$o_subform->setOutline($pn_outline_width, $pn_outline_dash);
		return $o_subform;
	}
	# ------------------------------------------------------------------
	public function addSubForm($po_subform) {
		$this->opo_subforms[] = $po_subform;
	}
	# ------------------------------------------------------------------
	public function addNewSubForm($pa_values, $pn_outline_width=0, $pn_outline_dash=0) {
		$o_subform = $this->getNewSubForm($pn_outline_width, $pn_outline_dash);
		$o_subform->setElements($pa_values);
		$this->addSubForm($o_subform);
		
		return true;
	}
	# ------------------------------------------------------------------
	#
	# ------------------------------------------------------------------
	public function getPDF() {
		return $this->render();
	}
	# ------------------------------------------------------------------
	public function render() {
		if (!$this->ops_form) { return "No form to render"; }
		$vn_margin_left_px = 		$this->getFormSetting('marginLeft');
		$vn_margin_top_px = 		$this->getFormSetting('marginTop');
		$vn_margin_right_px = 		$this->getFormSetting('marginRight');
		$vn_margin_bottom_px = 		$this->getFormSetting('marginBottom');
		
		$vn_page_width_px = 		$this->getFormSetting('pageWidth');
		$vn_page_height_px = 		$this->getFormSetting('pageHeight');
		
		$vn_subform_width_px = 		$this->getFormSetting('subFormWidth');
		$vn_subform_height_px = 	$this->getFormSetting('subFormHeight');
		$vn_subform_hgutter_px = 	$this->getFormSetting('horizontalGutter');
		$vn_subform_vgutter_px = 	$this->getFormSetting('verticalGutter');
		
		$vb_subform_use_border =	(bool)$this->getFormSetting('useBorder');
		$vn_subform_border_dash =	(int)$this->getFormSetting('borderDash');
		
		$this->opa_images = array(); 		// cache of images loaded into new PDF
		$this->opa_fonts = array();			// cache of fonts used in new PDF
		
		if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
			$o_pdf = new Zend_Pdf();
			$o_pdf->pages[] = $o_pdf->newPage($vn_page_width_px, $vn_page_height_px);
		} else {
			$o_pdf = new PDFlib();
			$o_pdf->set_parameter("errorpolicy", "return");
			
			$o_pdf->begin_document("", "openmode=none");
			
			$o_pdf->begin_page_ext($vn_page_width_px, $vn_page_height_px, "");
		}
		
		$this->setFont($o_pdf, "Helvetica", 12);	// default
		
		
		// Render page elements
		foreach($this->opa_form_page_elements as $vs_name => $vo_element) {
			$vo_element->render($o_pdf);
		}
		
		// Render sub-forms
		// 
		$this->renderPageElements($o_pdf,0);
		
		$vn_x = $vn_margin_left_px;
		$vn_y = $vn_page_height_px - $vn_margin_top_px;
		
		if ($this->opa_starting_subform > 0) {
			for($vn_i=0; $vn_i < $this->opa_starting_subform; $vn_i++) {
				$vn_x += ($vn_subform_vgutter_px + $vn_subform_width_px);
				if (($vn_x + $vn_subform_width_px) > $vn_page_width_px) {
					$vn_x = $vn_margin_left_px;
					$vn_y -= ($vn_subform_hgutter_px + $vn_subform_height_px);
					
					if ((($vn_y - $vn_subform_height_px) < $vn_margin_bottom_px) && ($vn_i < $vn_num_subforms - 1))  {
						# need new page!
						$vn_x = $vn_margin_left_px;
						$vn_y = $vn_page_height_px - $vn_margin_top_px;
					}
				}
			}
		}
		
		
		$vn_num_subforms = sizeof($this->opo_subforms);
		
		$vn_cur_page = 0;
		for($vn_i=0; $vn_i < $vn_num_subforms; $vn_i++) {
			$o_subform = $this->opo_subforms[$vn_i];
			$o_subform->setOutline($vb_subform_use_border ? 1 : 0, $vn_subform_border_dash);
			$o_subform->render($o_pdf, $vn_x, $vn_y);
			
			$vn_x += ($vn_subform_vgutter_px + $vn_subform_width_px);
			if (($vn_x + $vn_subform_width_px) > $vn_page_width_px) {
				$vn_x = $vn_margin_left_px;
				
				$vn_y -= ($vn_subform_hgutter_px + $vn_subform_height_px);
				
				if (
					(
						(($vn_y - $vn_subform_height_px) < $vn_margin_bottom_px) && ($vn_i < $vn_num_subforms - 1)
					)
				)  {
					# need new page!
					$vn_cur_page++;
					
					if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
						$o_pdf->pages[] = $o_pdf->newPage($vn_page_width_px, $vn_page_height_px);
					} else {
						$o_pdf->end_page_ext("");
						$o_pdf->begin_page_ext($vn_page_width_px, $vn_page_height_px, "");
					}
					$this->setFont($o_pdf, "Helvetica", 12);	// default
					
					$this->renderPageElements($o_pdf, $vn_cur_page);
					
					$vn_x = $vn_margin_left_px;
					$vn_y = $vn_page_height_px - $vn_margin_top_px;
				}
			}
		}
		
		if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
			return $o_pdf->render();
		} else {
			$o_pdf->end_page_ext("");
			foreach($this->opa_images as $vs_path => $vn_image_ref) {
				$o_pdf->close_image($vn_image_ref);
			}
			
			$o_pdf->end_document("");
			
			return $o_pdf->get_buffer();
		}
	}
	# ------------------------------------------------------------------
	public function renderPageElements($po_pdf, $pn_page_number) {
		foreach($this->getPageElements() as $vs_name => $va_info) {
			if (!($vo_element = $this->opa_form_page_elements[$vs_name])) {
				$vo_element = $this->getNewPageElement($vs_name);
				
				if ($va_info['type'] == 'pagenumber') {
					$vo_element->setValue($va_info['default'].($pn_page_number + 1));
				}
			}
			$vo_element->render($po_pdf);
		}
	}
	# ------------------------------------------------------------------
	# Font settings
	# ------------------------------------------------------------------
	public function getFont($po_pdf, $ps_fontname, $ps_encoding='auto') {
		
		if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
			if (isset($this->opa_fonts[$ps_fontname])) { return $this->opa_fonts[$ps_fontname]; }
			
			$o_font = null;
			
			// try loading it from configured font dir
			if ($vs_font_dir = $this->opo_config->get('fonts_directory')) {
				if (file_exists($vs_font_dir.'/'.$ps_fontname)) {
					try {
						$o_font = Zend_Pdf_Font::fontWithPath($vs_font_dir.'/'.$ps_fontname);
					} catch (Exception $e) {
						//noop
					}	
				}
			}
			
			if (!$o_font) {
				try {
					$o_font = Zend_Pdf_Font::fontWithName($ps_fontname);
				} catch (Exception $e) {
					$o_font = Zend_Pdf_Font::fontWithName('Courier');
				}
				$this->opa_fonts[$ps_fontname] = $o_font;
			}
			return $o_font;
		} else{ 
			if (isset($this->opa_fonts[$ps_fontname])) { return $this->opa_fonts[$ps_fontname]; }
			if ($vn_font = $po_pdf->load_font($ps_fontname, 'winansi', "errorpolicy=return")) {
				return $this->opa_fonts[$ps_fontname] = $vn_font;
			}
			// try loading it from configured font dir
			if ($vs_font_dir = $this->opo_config->get('fonts_directory')) {
				$po_pdf->set_parameter('FontOutline', $ps_fontname.'='.$vs_font_dir.'/'.$ps_fontname);
				$va_tmp = explode('.', $ps_fontname);
				array_pop($va_tmp);
				$vs_afm_name = join('.', $va_tmp).'.afm';
				$po_pdf->set_parameter('FontAFM', $ps_fontname.'='.$vs_font_dir.'/'.$vs_afm_name);
				if ($vn_font = $po_pdf->load_font($ps_fontname, 'winansi', "errorpolicy=return")) {
					return $this->opa_fonts[$ps_fontname] = $vn_font;
				}
			}
		}
		$this->postError(2260, _t("Could not load font '%1'", $ps_fontname), "PrintForms->getFont()");
		return null;
	}
	# ------------------------------------------------------------------
	public function setFont($po_pdf, $ps_fontname, $pn_size, $ps_encoding='auto') {
		if (!($vn_font = $this->getFont($po_pdf, $ps_fontname, $ps_encoding))) {
			$vn_font = $this->getFont($po_pdf, "Courier");
		}
		
		if ($vn_font) {
			if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
				$po_pdf->pages[sizeof($po_pdf->pages)-1]->setFont($vn_font, floatval($pn_size));
			} else {
				$po_pdf->setFont($vn_font, $pn_size);
			}
			return true;
		} 
		return false;
	}
	# ------------------------------------------------------------------
	# Image handling
	# ------------------------------------------------------------------
	public function loadImage($po_pdf, $ps_image_path) {
		if (!$ps_image_path) { return null; }
		if ($this->opa_images[$ps_image_path]) {
			return $this->opa_images[$ps_image_path];
		}
		if ($this->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
			try {
				return $this->opa_images[$ps_image_path] = Zend_Pdf_Image::imageWithPath($ps_image_path);	
			} catch (Exception $e) {
				return false;
			}
		} else {
			if ($vn_image_ref = $po_pdf->load_image("auto", $ps_image_path, "")) {
				return $this->opa_images[$ps_image_path] = $vn_image_ref;	
			}
		}
		$this->postError(2270, _t("Could not load image at '%1'", $ps_image_path), "PrintForms->loadImage()");
		return null;
	}
	# ------------------------------------------------------------------
	# Form styles
	# ------------------------------------------------------------------
	public function getStyle($ps_style) {
		$va_style = $this->opo_print_form_element_styles->getAssoc($ps_style);
		return $va_style ? $va_style : null;
	}
	# ------------------------------------------------------------------
	# Form settings
	# ------------------------------------------------------------------
	public function getFormSetting($vs_setting) {
		if(isset($this->opa_form_settings[$vs_setting])) {
			return $this->getValueInPoints($this->opa_form_settings[$vs_setting]);
		}
		return null;
	}
	# ------------------------------------------------------------------
	public function getValueInPoints($ps_value) {
		global $_Weblib_PrintForms_ValueCache;
		if (!is_array($_Weblib_PrintForms_ValueCache)) { $_Weblib_PrintForms_ValueCache = array(); }
		
		if (is_array($ps_value)) { return $ps_value; }
		
		if (isset($_Weblib_PrintForms_ValueCache[$ps_value])) { return $_Weblib_PrintForms_ValueCache[$ps_value]; }
		
		if (!preg_match("/^([\d\.]+)[ ]*([A-Za-z]*)$/", $ps_value, $va_matches)) {
			$_Weblib_PrintForms_ValueCache[$ps_value] = $ps_value;
			return $ps_value;
		}
		
		switch(strtolower($va_matches[2])) {
			case 'in':
				$ps_value_in_points = $va_matches[1] * 72;
				break;
			case 'cm':
				$ps_value_in_points = $va_matches[1] * 28.346;
				break;
			case 'mm':
				$ps_value_in_points = $va_matches[1] * 2.8346;
				break;
			case '':
			case 'px':
			case 'p':
				$ps_value_in_points = $va_matches[1];
				break;
			default:
				$ps_value_in_points = $ps_value;
				break;
		}
		
		return $_Weblib_PrintForms_ValueCache[$ps_value] = $ps_value_in_points;
	}
	# --------------------------------------------------------------------------------------------
}
?>