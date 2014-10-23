<?php
/** ---------------------------------------------------------------------
 * app/lib/core/print/PrintForms/PrintFormTextElement.php : 
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

class PrintFormTextElement extends PrintFormElements {
	# ------------------------------------------------------------------
	var $opo_form;
	
	var $ops_value = null;
	var $opa_options;
	var $opa_element_info;
	
	# ------------------------------------------------------------------
	public function PrintFormTextElement($po_form, $ps_name, $ps_value, $pa_options=null) {
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
					if (!($vn_size = $this->opo_form->getValueInPoints($va_element_info['size']))) {
						$vn_size = $this->opo_form->getValueInPoints($va_style['size']);
					}
					if (!($vs_font = $va_element_info['font'])) {
						$vs_font = $va_style['font'];
					}
					
					$vn_x = $this->opo_form->getValueInPoints($va_element_info['left']);
					$vn_y = $this->opo_form->getFormSetting('pageHeight') - $this->opo_form->getValueInPoints($va_element_info['top']);
				} else {
					return false;
				}
				break;
			case 'PrintSubForms':
				if ($va_element_info = $this->opa_element_info) {					
					$va_style = $this->opo_form->getStyle($va_element_info['style']);
					if (!($vn_size = $this->opo_form->getValueInPoints($va_element_info['size']))) {
						$vn_size = $this->opo_form->getValueInPoints($va_style['size']);
					}
					if (!($vs_font = $va_element_info['font'])) {
						$vs_font = $va_style['font'];
					}
					
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
		
					
		if (!($vn_leading = $this->opo_form->getValueInPoints($va_style['leading']))) {
			$vn_leading = $vn_size;
		}
		$this->opo_form->setFont($po_pdf, $vs_font, $vn_size, (isset($va_style['encoding']) && $va_style['encoding'] != '') ? $va_style['encoding'] : 'auto');
		
		
		// Rotation is only supported by PDFLib
		if ($this->opo_form->getPDFLibrary() == __PDF_LIBRARY_PDFLIB__) {
			$vs_opts = '';
			$vn_rotate = 0;
			if ($va_element_info['rotate']) {
				$vs_opts .= 'rotate='.intval($va_element_info['rotate']);
				$vn_rotate = $va_element_info['rotate'];
			} else {
				if ($va_style['rotate']) {
					$vs_opts .= 'rotate='.intval($va_style['rotate']);
					$vn_rotate = $vn_rotate['rotate'];
				}
			}
			
			$vn_rotate_radians = $vn_rotate/(180/3.1415);
		} else {
			$vn_rotate = 0;
			$vn_rotate_radians = 0;
		}
		
		$vn_width =  $this->opo_form->getValueInPoints($va_element_info['width']);
		$vn_height = $this->opo_form->getValueInPoints($va_element_info['height']);
		
		
		if ($this->opo_form->getPDFLibrary() == __PDF_LIBRARY_PDFLIB__) {
			$vs_text = iconv('UTF-8', 'ISO-8859-1', $this->getValue());		// PDFLIB lite doesn't support Unicode
		} else {
			$vs_text = $this->getValue();
		}
		
		$va_lines = $this->fitText($vs_text, $po_pdf, $vn_width, $vn_height);
		
		if (!$vs_align = $va_element_info['align']) {
			$vs_align = $va_style['align'];
		}
		
		
		if (!($vn_padding =  $va_element_info['padding'])) {
			$vn_padding = $va_style['padding'];
		}
		if (!($vn_border =  $va_element_info['border'])) {
			$vn_border = $va_style['border'];
		}
		if (!$vn_border) {
			if (!($vn_border_left =  $va_element_info['border-left'])) {
				$vn_border_left = $va_style['border-left'];
			}
			if (!($vn_border_right =  $va_element_info['border-right'])) {
				$vn_border_right = $va_style['border-right'];
			}
			if (!($vn_border_top =  $va_element_info['border-top'])) {
				$vn_border_top = $va_style['border-top'];
			}
			if (!($vn_border_bottom =  $va_element_info['border-bottom'])) {
				$vn_border_bottom = $va_style['border-bottom'];
			}
		}
		
		$vn_cur_x = $vn_x;
		$vn_cur_y = $vn_y;
		foreach($va_lines as $vn_line_width => $vs_line) {
			switch($vs_align) {
				case 'center':
					$vn_offset = ($vn_width - $vn_line_width)/2;
					break;
				case 'right':
					$vn_offset = $vn_width - $vn_line_width;
					break;
				default:
					$vn_offset = 0;
					break;
			}
			
			if ($this->opo_form->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
				$po_page = $po_pdf->pages[sizeof($po_pdf->pages)-1];
				$po_page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
				if (($vn_y - $vn_cur_y) >= ($vn_height * cos($vn_rotate_radians)) + ($vn_width * sin($vn_rotate_radians))) {
					break;
				}
				if (($vn_cur_x - $vn_x) >= ($vn_width * cos($vn_rotate_radians)) + ($vn_height * sin($vn_rotate_radians))) {
					break;
				}
				
				$po_page->drawText($vs_line,$vn_cur_x + $vn_offset ,$vn_cur_y, 'utf-8');
				
				
				if ($vn_border > 0) {
					$vn_border = $this->opo_form->getValueInPoints($vn_border);
					$po_page->setLineWidth($vn_border);

					//draw rectangle by drawing 4 lines so we don't have to worry about transparent filling of the whole box

					// top left point
					$vn_tl_x = $vn_x;
					$vn_tl_y = $vn_y + $vn_size;

					// top right
					$vn_tr_x = $vn_x + $vn_width;
					$vn_tr_y = $vn_y + $vn_size;

					// bottom left 
					$vn_bl_x = $vn_x;
					$vn_bl_y = $vn_y - $vn_height + $vn_size;

					// bottom right
					$vn_br_x = $vn_x + $vn_width;
					$vn_br_y = $vn_y - $vn_height + $vn_size;

					$po_page->drawLine($vn_tl_x, $vn_tl_y, $vn_tr_x, $vn_tr_y);
					$po_page->drawLine($vn_tl_x, $vn_tl_y, $vn_bl_x, $vn_bl_y);
					$po_page->drawLine($vn_bl_x, $vn_bl_y, $vn_br_x, $vn_br_y);
					$po_page->drawLine($vn_tr_x, $vn_tr_y, $vn_br_x, $vn_br_y);
					
				} else {
					if ($vn_border_bottom > 0) {
						$vn_border_bottom = $this->opo_form->getValueInPoints($vn_border_bottom);
						$po_page->setLineWidth($vn_border_bottom);
						$po_page->drawLine($vn_x, $vn_y - $vn_height + $vn_size + $vn_padding, $vn_x + $vn_width, $vn_y - $vn_height + $vn_size + $vn_padding);
					}
					if ($vn_border_top > 0) {
						$vn_border_top = $this->opo_form->getValueInPoints($vn_border_top);
						$po_page->setLineWidth($vn_border_top);
						$po_page->drawLine($vn_x, $vn_y + $vn_size - $vn_padding, $vn_x + $vn_width, $vn_y + $vn_size - $vn_padding);
					}
					if ($vn_border_left > 0) {
						$vn_border_left = $this->opo_form->getValueInPoints($vn_border_left);
						$po_page->setLineWidth($vn_border_left);
						$po_page->drawLine($vn_x - $vn_padding, $vn_y + $vn_size, $vn_x - $vn_padding, $vn_y - $vn_height + $vn_size);
					}
					if ($vn_border_right > 0) {
						$vn_border_right = $this->opo_form->getValueInPoints($vn_border_right);
						$po_page->setLineWidth($vn_border_right);
						$po_page->drawLine($vn_x + $vn_width + $vn_padding, $vn_y + $vn_size, $vn_x + $vn_width + $vn_padding, $vn_y - $vn_height + $vn_size);
					}
				}
				
				$vn_cur_x += ($vn_leading * sin($vn_rotate_radians));
				$vn_cur_y -= ($vn_leading * cos($vn_rotate_radians));
			} else {
				if (($vn_y - $vn_cur_y) >= ($vn_height * cos($vn_rotate_radians)) + ($vn_width * sin($vn_rotate_radians))) {
					break;
				}
				if (($vn_cur_x - $vn_x) >= ($vn_width * cos($vn_rotate_radians)) + ($vn_height * sin($vn_rotate_radians))) {
					break;
				}
				
				$po_pdf->fit_textline($vs_line,$vn_cur_x + $vn_offset ,$vn_cur_y, $vs_opts);
				
				
				if ($vn_border > 0) {
					$po_pdf->setlinewidth($vn_border);
					$po_pdf->setdash(0, 0);
					$po_pdf->moveTo($vn_x, $vn_y + $vn_size);
					
					$po_pdf->lineTo($vn_x + $vn_width, $vn_y + $vn_size);
					$po_pdf->lineTo($vn_x + $vn_width, $vn_y - $vn_height + $vn_size);
					$po_pdf->lineTo($vn_x, $vn_y - $vn_height + $vn_size);
					$po_pdf->lineTo($vn_x, $vn_y + $vn_size);
					$po_pdf->stroke();
				} else {
					if ($vn_border_bottom > 0) {
						$po_pdf->setlinewidth($vn_border_bottom);
						$po_pdf->setdash(0, 0);
						$po_pdf->moveTo($vn_x, $vn_y - $vn_height + $vn_size + $vn_padding);
						$po_pdf->lineTo($vn_x + $vn_width, $vn_y - $vn_height + $vn_size + $vn_padding);
						$po_pdf->stroke();
					}
					if ($vn_border_top > 0) {
						$po_pdf->setlinewidth($vn_border_top);
						$po_pdf->setdash(0, 0);
						$po_pdf->moveTo($vn_x, $vn_y + $vn_size - $vn_padding);
						$po_pdf->lineTo($vn_x + $vn_width, $vn_y + $vn_size - $vn_padding);
						$po_pdf->stroke();
					}
					if ($vn_border_left > 0) {
						$po_pdf->setlinewidth($vn_border_left);
						$po_pdf->setdash(0, 0);
						$po_pdf->moveTo($vn_x - $vn_padding, $vn_y + $vn_size);
						$po_pdf->lineTo($vn_x - $vn_padding, $vn_y - $vn_height + $vn_size);
						$po_pdf->stroke();
					}
					if ($vn_border_right > 0) {
						$po_pdf->setlinewidth($vn_border_right);
						$po_pdf->setdash(0, 0);
						$po_pdf->moveTo($vn_x + $vn_width + $vn_padding, $vn_y + $vn_size);
						$po_pdf->lineTo($vn_x + $vn_width + $vn_padding, $vn_y - $vn_height + $vn_size);
						$po_pdf->stroke();
					}
				}
				
				
				$vn_cur_x += ($vn_leading * sin($vn_rotate_radians));
				$vn_cur_y -= ($vn_leading * cos($vn_rotate_radians));
			}
			
		}
		return true;
	}
	# ------------------------------------------------------------------
	public function fitText($ps_text, $po_pdf, $pn_width, $pn_height) {
		$va_words = explode(' ', $ps_text);
		
		$va_line = array();
		$va_line_list = array();
		foreach($va_words as $vs_word) {
			if ($this->opo_form->getPDFLibrary() == __PDF_LIBRARY_ZEND__) {
				$po_page = $po_pdf->pages[sizeof($po_pdf->pages) - 1];
				$vo_font = $po_page->getFont();
				$vn_font_size = $po_page->getFontSize();
				$vn_width = PrintFormTextElement::_ZENDWidthForStringUsingFontSize(join(' ', array_merge($va_line, array($vs_word))),$vo_font, $vn_font_size);
			} else {
				$vn_width = $po_pdf->info_textline(join(' ', array_merge($va_line, array($vs_word))), 'width', '');
			}
			if ($vn_width > $pn_width) {
				$va_line_list[] = join(' ', $va_line);
				$va_line = array();
			}
			
			$va_line[] = $vs_word;
		}
		$va_line_list["{$vn_width}"] = join(' ', $va_line);
		
		return $va_line_list;
	}
	# ------------------------------------------------------------------
	/**
	* Returns the total width in points of the string using the specified font and
	* size.
	*
	* This is not the most efficient way to perform this calculation. I'm 
	* concentrating optimization efforts on the upcoming layout manager class.
	* Similar calculations exist inside the layout manager class, but widths are
	* generally calculated only after determining line fragments.
	*
	* @param string $string
	* @param Zend_Pdf_Resource_Font $font
	* @param float $fontSize Font size in points
	* @return float
	*/
	static function _ZENDWidthForStringUsingFontSize($ps_string, $po_font, $pn_font_size) {#
		$vs_drawing_string = iconv('UTF-8', 'UTF-16BE//IGNORE', $ps_string);
		$vo_characters = array();
		for ($vn_i = 0; $vn_i < strlen($vs_drawing_string); $vn_i++) {
			$vo_characters[] = (ord($vs_drawing_string[$vn_i++]) << 8) | ord($vs_drawing_string[$vn_i]);
		}
		$vo_glyphs = $po_font->glyphNumbersForCharacters($vo_characters);
		$va_widths = $po_font->widthsForGlyphs($vo_glyphs);
		$vn_string_width = (array_sum($va_widths) / $po_font->getUnitsPerEm()) * $pn_font_size;
		return $vn_string_width;	
	}
	# ------------------------------------------------------------------
}
?>