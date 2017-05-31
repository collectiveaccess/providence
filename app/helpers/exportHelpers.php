<?php
/** ---------------------------------------------------------------------
 * app/helpers/exportHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
	require_once(__CA_LIB_DIR__."/core/Print/PDFRenderer.php");
 	require_once(__CA_LIB_DIR__.'/core/Parsers/dompdf/dompdf_config.inc.php');
   
	require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel/IOFactory.php');
	
	\PhpOffice\PhpPresentation\Autoloader::register();
	
   # ----------------------------------------
	/**
	 * 
	 * @param RequestHTTP $po_request
	 * @param BaseModel $pt_subject
	 * @param string $ps_template The name of the template to render
	 * @param string $ps_output_filename
	 * @param array $pa_options
	 * @return bool
	 *
	 * @throws ApplicationException
	 */
	function caExportItemAsPDF($po_request, $pt_subject, $ps_template, $ps_output_filename, $pa_options=null) {
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/');
		
		$pa_access_values = caGetOption('checkAccess', $pa_options, null);
		
		$o_view->setVar('t_subject', $pt_subject);
		
		$vs_template_identifier = null;
		if (substr($ps_template, 0, 5) === '_pdf_') {
			//
			// Template names starting with "_pdf_" are taken to be named summary templates in the printTemplates/summary directory
			//
			$va_template_info = caGetPrintTemplateDetails('summary', substr($ps_template, 5));
		} elseif (substr($ps_template, 0, 9) === '_display_') {
			//
			// Template names starting with "_display_" are taken to be display_ids to be passed to the standard display formatting template
			//
			$t_display = new ca_bundle_displays($vn_display_id = (int)substr($ps_template, 9));
			
			if ($vn_display_id && ($t_display->isLoaded()) && ($t_display->haveAccessToDisplay($po_request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
				$o_view->setVar('t_display', $t_display);
				$o_view->setVar('display_id', $vn_display_id);
			
				$va_display_list = array();
				$va_placements = $t_display->getPlacements(array('settingsOnly' => true));
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
				
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						$va_tmp = caExtractValuesByUserLocale(array($va_settings['label']));
						if ($vs_tmp = array_shift($va_tmp)) { $vs_header = $vs_tmp; }
					}
				
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
				}
				$o_view->setVar('placements', $va_display_list);
			} else {
				throw new ApplicationException(_t("Invalid format %1", $ps_template));
			}
			$va_template_info = caGetPrintTemplateDetails('summary', 'summary');
		} else {
			throw new ApplicationException(_t("Invalid format %1", $ps_template));
		}
		
		//
		// PDF output
		//
			
		caDoTemplateTagSubstitution($o_view, $pt_subject, $va_template_info['path'], ['checkAccess' => $pa_access_values, 'render' => false]);	
		caExportViewAsPDF($o_view, $va_template_info, $ps_output_filename, []);
		$o_controller = AppController::getInstance();
		$o_controller->removeAllPlugins();
		
		return true;
	}
	# ----------------------------------------
	/**
	 * 
	 * @param RequestHTTP $po_request
	 * @param SearchResult $po_result
	 * @param string $ps_template
	 * @param string $ps_output_filename
	 * @param array $pa_options
	 * @return bool
	 *
	 * @throws ApplicationException
	 */
	function caExportResult($po_request, $po_result, $ps_template, $ps_output_filename, $pa_options=null) {
		$o_config = Configuration::load();
		$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/');
		
		
		$o_view->setVar('result', $po_result);
		$o_view->setVar('criteria_summary', caGetOption('criteriaSummary', $pa_options, ''));
		
		$vs_table = $po_result->tableName();
		
		$vs_type = null;
		if (!(bool)$o_config->get('disable_pdf_output') && substr($ps_template, 0, 5) === '_pdf_') {
			$va_template_info = caGetPrintTemplateDetails('results', substr($ps_template, 5));
			$vs_type = 'pdf';
		} elseif (!(bool)$o_config->get('disable_pdf_output') && (substr($ps_template, 0, 9) === '_display_')) {
			$vn_display_id = substr($ps_template, 9);
			$t_display = new ca_bundle_displays($vn_display_id);
			$o_view->setVar('display', $t_display);
			
			if ($vn_display_id && ($t_display->haveAccessToDisplay($po_request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
				$o_view->setVar('display', $t_display);
				
				$va_placements = $t_display->getPlacements(array('settingsOnly' => true));
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
				
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						$va_tmp = caExtractValuesByUserLocale(array($va_settings['label']));
						if ($vs_tmp = array_shift($va_tmp)) { $vs_header = $vs_tmp; }
					}
				
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
				}
				$o_view->setVar('display_list', $va_display_list);
			} else {
				throw new ApplicationException(_t("Invalid format %1", $ps_template));
			}
			$va_template_info = caGetPrintTemplateDetails('results', 'display');
			$vs_type = 'pdf';
		} elseif(!(bool)$o_config->get('disable_export_output')) {
			// Look it up in app.conf export_formats
			$va_export_config = $o_config->getAssoc('export_formats');
			if (is_array($va_export_config) && is_array($va_export_config[$vs_table]) && is_array($va_export_config[$vs_table][$ps_template])) {
				
				switch($va_export_config[$vs_table][$ps_template]['type']) {
					case 'xlsx':
						$vs_type = 'xlsx';
						break;
					case 'pptx':
						$vs_type = 'pptx';
						break;
				}
			} else {
				throw new ApplicationException(_t("Invalid format %1", $ps_template));
			}
		}
		
		if(!$vs_type) { throw new ApplicationException(_t('Invalid export type')); }
		
		switch($vs_type) {
			case 'xlsx':

				$vn_ratio_pixels_to_excel_height = 0.85;
				$vn_ratio_pixels_to_excel_width = 0.135;

				$va_supercol_a_to_z = range('A', 'Z');
				$vs_supercol = '';

				$va_a_to_z = range('A', 'Z');

				$workbook = new PHPExcel();

				// more accurate (but slower) automatic cell size calculation
				PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);

				$o_sheet = $workbook->getActiveSheet();
				// mise en forme
				$columntitlestyle = array(
						'font'=>array(
								'name' => 'Arial',
								'size' => 12,
								'bold' => true),
						'alignment'=>array(
								'horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
								'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
								'wrap' => true,
								'shrinkToFit'=> true),
						'borders' => array(
								'allborders'=>array(
										'style' => PHPExcel_Style_Border::BORDER_THICK)));
				$cellstyle = array(
						'font'=>array(
								'name' => 'Arial',
								'size' => 11,
								'bold' => false),
						'alignment'=>array(
								'horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
								'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
								'wrap' => true,
								'shrinkToFit'=> true),
						'borders' => array(
								'allborders'=>array(
										'style' => PHPExcel_Style_Border::BORDER_THIN)));

				$o_sheet->getDefaultStyle()->applyFromArray($cellstyle);
				$o_sheet->setTitle("CollectiveAccess");

				$vn_line = 1;

				$vs_column = reset($va_a_to_z);

				// Column headers
				$o_sheet->getRowDimension($vn_line)->setRowHeight(30);
				foreach($va_export_config[$vs_table][$ps_template]['columns'] as $vs_title => $vs_template) {
					if($vs_column) {
						$o_sheet->setCellValue($vs_supercol.$vs_column.$vn_line,$vs_title);
						$o_sheet->getStyle($vs_supercol.$vs_column.$vn_line)->applyFromArray($columntitlestyle);
						if (!($vs_column = next($va_a_to_z))) {
							$vs_supercol = array_shift($va_supercol_a_to_z);
							$vs_column = reset($va_a_to_z);
						}
					}
				}


				$vn_line = 2 ;

				while($po_result->nextHit()) {
					$vs_column = reset($va_a_to_z);
	
					$va_supercol_a_to_z = range('A', 'Z');
					$vs_supercol = '';

					// default to automatic row height. works pretty well in Excel but not so much in LibreOffice/OOo :-(
					$o_sheet->getRowDimension($vn_line)->setRowHeight(-1);

					foreach($va_export_config[$vs_table][$ps_template]['columns'] as $vs_title => $va_settings) {

						if (
							(strpos($va_settings['template'], 'ca_object_representations.media') !== false)
							&& 
							preg_match("!ca_object_representations\.media\.([A-Za-z0-9_\-]+)!", $va_settings['template'], $va_matches)
						) {
							$vs_version = $va_matches[1];
							$va_info = $po_result->getMediaInfo('ca_object_representations.media', $vs_version);
			
							if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
			
								if (is_file($vs_path = $po_result->getMediaPath('ca_object_representations.media', $vs_version))) {
									$image = "image".$vs_supercol.$vs_column.$vn_line;
									$drawing = new PHPExcel_Worksheet_Drawing();
									$drawing->setName($image);
									$drawing->setDescription($image);
									$drawing->setPath($vs_path);
									$drawing->setCoordinates($vs_supercol.$vs_column.$vn_line);
									$drawing->setWorksheet($o_sheet);
									$drawing->setOffsetX(10);
									$drawing->setOffsetY(10);
								}

								$vn_width = floor(intval($va_info['PROPERTIES']['width']) * $vn_ratio_pixels_to_excel_width);
								$vn_height = floor(intval($va_info['PROPERTIES']['height']) * $vn_ratio_pixels_to_excel_height);

								// set the calculated withs for the current row and column,
								// but make sure we don't make either smaller than they already are
								if($vn_width > $o_sheet->getColumnDimension($vs_supercol.$vs_column)->getWidth()) {
									$o_sheet->getColumnDimension($vs_supercol.$vs_column)->setWidth($vn_width);	
								}
								if($vn_height > $o_sheet->getRowDimension($vn_line)->getRowHeight()){
									$o_sheet->getRowDimension($vn_line)->setRowHeight($vn_height);
								}

							}
						} elseif ($vs_display_text = $po_result->getWithTemplate($va_settings['template'])) {
			
							$o_sheet->setCellValue($vs_supercol.$vs_column.$vn_line, html_entity_decode(strip_tags(br2nl($vs_display_text)), ENT_QUOTES | ENT_HTML5));
							// We trust the autosizing up to a certain point, but
							// we want column widths to be finite :-).
							// Since Arial is not fixed-with and font rendering
							// is different from system to system, this can get a
							// little dicey. The values come from experimentation.
							if ($o_sheet->getColumnDimension($vs_supercol.$vs_column)->getWidth() == -1) {  // don't overwrite existing settings
								if(strlen($vs_display_text)>55) {
									$o_sheet->getColumnDimension($vs_supercol.$vs_column)->setWidth(50);
								}
							}
						}

						if (!($vs_column = next($va_a_to_z))) {
							$vs_supercol = array_shift($va_supercol_a_to_z);
							$vs_column = reset($va_a_to_z);
						}
					}

					$vn_line++;
				}

				// set column width to auto for all columns where we haven't set width manually yet
				foreach(range('A','Z') as $vs_chr) {
					if ($o_sheet->getColumnDimension($vs_chr)->getWidth() == -1) {
						$o_sheet->getColumnDimension($vs_chr)->setAutoSize(true);	
					}
				}

				$o_writer = new PHPExcel_Writer_Excel2007($workbook);

				header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition:inline;filename=Export.xlsx ');
				$o_writer->save('php://output');
				exit;
				break;
			case 'pptx':
				$ppt = new PhpOffice\PhpPresentation\PhpPresentation();

				$vn_slide = 0;
				while($po_result->nextHit()) {
					if ($vn_slide > 0) {
						$slide = $ppt->createSlide();
					} else {
						$slide = $ppt->getActiveSlide();
					}
			
					foreach($va_export_config[$vs_table][$ps_template]['columns'] as $vs_title => $va_settings) {

						if (
							(strpos($va_settings['template'], 'ca_object_representations.media') !== false)
							&& 
							preg_match("!ca_object_representations\.media\.([A-Za-z0-9_\-]+)!", $va_settings['template'], $va_matches)
						) {
							$vs_version = $va_matches[1];
							$va_info = $po_result->getMediaInfo('ca_object_representations.media', $vs_version);
			
							if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
			
								if (is_file($vs_path = $po_result->getMediaPath('ca_object_representations.media', $vs_version))) {
									$shape = $slide->createDrawingShape();
									$shape->setName($va_info['ORIGINAL_FILENAME'])
										  ->setDescription('Image')
										  ->setPath($vs_path)
										  ->setWidth(caConvertMeasurementToPoints(caGetOption('width', $va_settings, '100px'), array('dpi' => 96)))
										  ->setHeight(caConvertMeasurementToPoints(caGetOption('height', $va_settings, '100px'), array('dpi' => 96)))
										  ->setOffsetX(caConvertMeasurementToPoints(caGetOption('x', $va_settings, '100px'), array('dpi' => 96)))
										  ->setOffsetY(caConvertMeasurementToPoints(caGetOption('y', $va_settings, '100px'), array('dpi' => 96)));
									$shape->getShadow()->setVisible(true)
													   ->setDirection(45)
													   ->setDistance(10);
								}
							}
						} elseif ($vs_display_text = html_entity_decode(strip_tags(br2nl($po_result->getWithTemplate($va_settings['template']))))) {
							switch($vs_align = caGetOption('align', $va_settings, 'center')) {
								case 'center':
									$vs_align = \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER;
									break;
								case 'left':
									$vs_align = \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_LEFT;
									break;
								case 'right':
								default:
									$vs_align = \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_RIGHT;
									break;
							}
			
							$shape = $slide->createRichTextShape()
								  ->setHeight(caConvertMeasurementToPoints(caGetOption('height', $va_settings, '100px'), array('dpi' => 96)))
								  ->setWidth(caConvertMeasurementToPoints(caGetOption('width', $va_settings, '100px'), array('dpi' => 96)))
								  ->setOffsetX(caConvertMeasurementToPoints(caGetOption('x', $va_settings, '100px'), array('dpi' => 96)))
								  ->setOffsetY(caConvertMeasurementToPoints(caGetOption('y', $va_settings, '100px'), array('dpi' => 96)));
							$shape->getActiveParagraph()->getAlignment()->setHorizontal($vs_align);
							$textRun = $shape->createTextRun($vs_display_text);
							$textRun->getFont()->setBold((bool)caGetOption('bold', $va_settings, false))
											   ->setSize(caConvertMeasurementToPoints(caGetOption('size', $va_settings, '36px'), array('dpi' => 96)))
											   ->setColor( new \PhpOffice\PhpPresentation\Style\Color( caGetOption('color', $va_settings, 'cccccc') ) );
						}

					}

					$vn_slide++;
				}

				
				header('Content-type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
				header('Content-Disposition:inline;filename=Export.pptx ');
				
				$o_writer = \PhpOffice\PhpPresentation\IOFactory::createWriter($ppt, 'PowerPoint2007');
				$o_writer->save('php://output');
				return;
				break;
			case 'pdf':
				//
				// PDF output
				//
				caExportViewAsPDF($o_view, $va_template_info, caGetOption('filename', $va_template_info, 'export_results.pdf'), []);
				$o_controller = AppController::getInstance();
				$o_controller->removeAllPlugins();
		
				return;
		}
	}
	# ----------------------------------------
	/**
	 * Export view as PDF using a specified template. It is assumed that all view variables required for 
	 * rendering are already set.
	 * 
	 * @param View $po_view
	 * @param string $ps_template_identifier
	 * @param string $ps_output_filename
	 * @param array $pa_options
	 * @return bool
	 *
	 * @throws ApplicationException
	 */
	function caExportViewAsPDF($po_view, $ps_template_identifier, $ps_output_filename, $pa_options=null) {
		if (is_array($ps_template_identifier)) {
			$pa_template_info = $ps_template_identifier;
		} else {
			$va_template = explode(':', $ps_template_identifier);
			$pa_template_info = caGetPrintTemplateDetails($va_template[0], $va_template[1]);
		}
		if (!is_array($pa_template_info)) { throw new ApplicationException("No template information specified"); }
		$vb_printed_properly = false;
		
		try {
			$o_pdf = new PDFRenderer();
			$po_view->setVar('PDFRenderer', $o_pdf->getCurrentRendererCode());

			$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $pa_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $pa_template_info, 'portrait'));
			$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];
			$po_view->setVar('pageWidth', "{$vn_page_width}mm");
			$po_view->setVar('pageHeight', "{$vn_page_height}mm");
			$po_view->setVar('marginTop', caGetOption('marginTop', $pa_template_info, '0mm'));
			$po_view->setVar('marginRight', caGetOption('marginRight', $pa_template_info, '0mm'));
			$po_view->setVar('marginBottom', caGetOption('marginBottom', $pa_template_info, '0mm'));
			$po_view->setVar('marginLeft', caGetOption('marginLeft', $pa_template_info, '0mm'));
			$po_view->setVar('base_path', $vs_base_path = pathinfo($pa_template_info['path'], PATHINFO_DIRNAME));

			$po_view->addViewPath($vs_base_path."/local");
			$po_view->addViewPath($vs_base_path);
			$vs_content = $po_view->render($pa_template_info['path']);
			
			$vb_printed_properly = caExportContentAsPDF($vs_content, $pa_template_info, $ps_output_filename, $pa_options=null);
		} catch (Exception $e) {
			$vb_printed_properly = false;
			throw new ApplicationException(_t("Could not generate PDF"));
		}
		
		return $vb_printed_properly;
	}
	# ----------------------------------------
	/**
	 * Export content as PDF.
	 * 
	 * @param string $ps_content
	 * @param array $pa_template_info
	 * @param string $ps_output_filename
	 * @param array $pa_options
	 * @return bool
	 *
	 * @throws ApplicationException
	 */
	function caExportContentAsPDF($ps_content, $pa_template_info, $ps_output_filename, $pa_options=null) {
		if (!is_array($pa_template_info)) { throw new ApplicationException("No template information specified"); }
		$vb_printed_properly = false;
		
		try {
			$o_pdf = new PDFRenderer();

			$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $pa_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $pa_template_info, 'portrait'));
			$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];

			$o_pdf->setPage(caGetOption('pageSize', $pa_template_info, 'letter'), caGetOption('pageOrientation', $pa_template_info, 'portrait'), caGetOption('marginTop', $pa_template_info, '0mm'), caGetOption('marginRight', $pa_template_info, '0mm'), caGetOption('marginBottom', $pa_template_info, '0mm'), caGetOption('marginLeft', $pa_template_info, '0mm'));
		
			$ps_output_filename = ($ps_output_filename) ? preg_replace('![^A-Za-z0-9_\-\.]+!', '_', $ps_output_filename) : 'export';

			$o_pdf->render($ps_content, array('stream'=> true, 'filename' => $ps_output_filename));

			$vb_printed_properly = true;
		} catch (Exception $e) {
			$vb_printed_properly = false;
			throw new ApplicationException(_t("Could not generate PDF"));
		}
		
		return $vb_printed_properly;
	}
	# ----------------------------------------
	/**
	 * Generate name for downloadable file. Can take a display template evaluated relative to a provided model instance, a template
	 * evaluated with an array of tag values or static text. (Note: for compatibility reasons if the static text "label" is passed and
	 * a model instance is passed in the 't_subject' option then the preferred label of the instance will be returned).
	 *
	 * The returned value will have all non-alphanumeric characters replaced with underscores, ready for use as a download file name.
	 *
	 * @param string $ps_template A display template or static text used to generate the file name.
	 * @param array $pa_options Options include:
	 * 		t_subject = a model instance to evaluate the filename template relative to. [Default is null]
	 *		values = an array of values, where keys are tag names in the filename template. [Default is null]
	 *
	 * Note that if neither the t_subject or values options are set the template will be evaluated as static text.
	 *
	 * @return string
	 */
	function caGenerateDownloadFileName($ps_template, $pa_options=null) {
		$pt_subject = caGetOption('t_subject', $pa_options, null);
		if ((strpos($ps_template, "^") !== false) && ($pt_subject)) {
			return caProcessTemplateForIDs($ps_template, $pt_subject->tableName(), [$pt_subject->getPrimaryKey()], $pa_options);
		} elseif ((strpos($ps_template, "^") !== false) && is_array($va_values = caGetOption('values', $pa_options, null))) {
			return caProcessTemplate($ps_template, $va_values, $pa_options);
		}
		
		switch(strtolower($ps_template)) {
			case 'label':
				return $pt_subject ? $pt_subject->getLabelForDisplay() : "export";
				break;
		}
		
		return $ps_template;
	}
	# ----------------------------------------