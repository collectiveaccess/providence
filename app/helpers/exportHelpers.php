<?php
/** ---------------------------------------------------------------------
 * app/helpers/exportHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Print/PDFRenderer.php");

# ----------------------------------------
/**
 *
 */
function caExportFormatForTemplate($table, $template) {
	if (substr($template, 0, 5) === '_pdf_') {
		return 'PDF';
	} else {		
		$o_config = Configuration::load();
		$export_config = $o_config->getAssoc('export_formats');
		
		if (is_array($export_config) && is_array($export_config[$table]) && is_array($export_config[$table][$template])) {
			
			switch($export_config[$table][$template]['type']) {
				case 'xlsx':
					return 'Excel';
					break;
				case 'pptx':
					return 'Powerpoint';
					break;
			}
		}
	}
	return null;
}
# ----------------------------------------
/**
 * Export instance as PDF using template
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
	$o_controller = AppController::getInstance();
	$o_controller->removeAllPlugins();
	
	$pa_options['writeFile'] = $cache_path = __CA_BASE_DIR__.'/export/'.$pt_subject->tableName().'_'.$pt_subject->getPrimaryKey().'.pdf';
	if(!caGetOption('dontCache', $pa_options, false)) {
		
		if(file_exists($cache_path)) {
			header("Cache-Control: private");
   			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=".$ps_output_filename);
			$fp = @fopen($cache_path, "rb");
			while(is_resource($fp) && !feof($fp)) {
				print(@fread($fp, 1024*8));
				ob_flush();
				flush();
			}
			return true;
		}
	}
		
	caDoTemplateTagSubstitution($o_view, $pt_subject, $va_template_info['path'], ['checkAccess' => $pa_access_values, 'render' => false]);	
	caExportViewAsPDF($o_view, $va_template_info, $ps_output_filename, $pa_options);
	
	return true;
}
# ----------------------------------------
/**
 * Export search result set as a PDF, XLSX or PPTX file.
 * 
 * @param RequestHTTP $po_request
 * @param SearchResult $po_result
 * @param string $ps_template
 * @param string $output_filename
 * @param array $pa_options Options include:
 *		output = where to output data. Values may be FILE (write to file) or STREAM. [Default is stream]
 *
 * @return ?array|bool If output is FILE, path to file or null if file could not be generated. If output is STREAM null returned on error; true returned on success
 *
 * @throws ApplicationException
 */
function caExportResult($request, $result, $template, $output_filename, $options=null) {
	$output = caGetOption('output', $options, 'STREAM');
	
	$o_config = Configuration::load();
	$o_view = new View($request, $request->getViewsDirectoryPath().'/');
	
	$o_view->setVar('result', $result);
	$o_view->setVar('t_set', caGetOption('set', $options, null));
	$o_view->setVar('criteria_summary', caGetOption('criteriaSummary', $options, ''));
	
	$table = $result->tableName();
	
	$type = null;
	$export_config = $template_info = null;
	
	if (!(bool)$o_config->get('disable_pdf_output') && substr($template, 0, 5) === '_pdf_') {
		$template_info = caGetPrintTemplateDetails(caGetOption('printTemplateType', $options, 'results'), substr($template, 5));
		$type = 'pdf';
	} elseif (!(bool)$o_config->get('disable_pdf_output') && (substr($template, 0, 9) === '_display_')) {
		$display_id = substr($template, 9);
		$t_display = new ca_bundle_displays($display_id);
		$o_view->setVar('display', $t_display);
		
		if ($display_id && ($t_display->haveAccessToDisplay($request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
			$o_view->setVar('display', $t_display);
			
			$placements = $t_display->getPlacements(array('settingsOnly' => true));
			$o_view->setVar('display_list', $placements);
			foreach($placements as $placement_id => $display_item) {
				$settings = caUnserializeForDatabase($display_item['settings']);
			
				// get column header text
				$header = $display_item['display'];
				if (isset($settings['label']) && is_array($settings['label'])) {
					$tmp = caExtractValuesByUserLocale(array($settings['label']));
					if ($tmp = array_shift($tmp)) { $header = $tmp; }
				}
			
				$display_list[$placement_id] = array(
					'placement_id' => $placement_id,
					'bundle_name' => $display_item['bundle_name'],
					'display' => $header,
					'settings' => $settings
				);
			}
			$o_view->setVar('display_list', $display_list);
		} else {
			throw new ApplicationException(_t("Invalid format %1", $template));
		}
		$template_info = caGetPrintTemplateDetails(caGetOption('printTemplateType', $options, 'results'), 'display');
		$type = 'pdf';
	} elseif(!(bool)$o_config->get('disable_export_output')) {
		// Look it up in app.conf export_formats
		$export_config = $o_config->getAssoc('export_formats');
		if (is_array($export_config) && is_array($export_config[$table]) && is_array($export_config[$table][$template])) {
			
			switch($export_config[$table][$template]['type']) {
				case 'xlsx':
					$type = 'xlsx';
					break;
				case 'pptx':
					$type = 'pptx';
					break;
			}
		} else {
			throw new ApplicationException(_t("Invalid format %1", $template));
		}
	}
	
	if(!$type) { throw new ApplicationException(_t('Invalid export type')); }
	
	if(!($filename_stub = caGetOption('filename', $options, null))) { 
		if(is_array($template_info)) {
			$filename_stub = caGetOption('filename', $template_info, 'export_results').'_'.date("Y-m-d");
		} elseif(is_array($export_config)) {
			$filename_stub = caGetOption('filename', $export_config[$table][$template], 'export_results').'_'.date("Y-m-d");
		} else {
			$filename_stub = 'export_results'.'_'.date("Y-m-d");
		}
	}
	$filename_stub = preg_replace('![^A-Za-z0-9_\-\.]+!', '_', $filename_stub);
	
	switch($type) {
		case 'xlsx':

			$ratio_pixels_to_excel_height = 0.85;
			$ratio_pixels_to_excel_width = 0.135;

			$supercol_a_to_z = range('A', 'Z');
			$supercol = '';

			$a_to_z = range('A', 'Z');

			$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

			$o_sheet = $workbook->getActiveSheet();
			// mise en forme
			$columntitlestyle = array(
					'font'=>array(
							'name' => 'Arial',
							'size' => 12,
							'bold' => true),
					'alignment'=>array(
							'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
							'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
							'wrap' => true,
							'shrinkToFit'=> true),
					'borders' => array(
							'allborders'=>array(
									'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)));
			$cellstyle = array(
					'font'=>array(
							'name' => 'Arial',
							'size' => 11,
							'bold' => false),
					'alignment'=>array(
							'horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
							'vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
							'wrap' => true,
							'shrinkToFit'=> true),
					'borders' => array(
							'allborders'=>array(
									'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)));

			$o_sheet->getParent()->getDefaultStyle()->applyFromArray($cellstyle);
			$o_sheet->setTitle("CollectiveAccess");

			$line = 1;

			$column = reset($a_to_z);

			// Column headers
			$o_sheet->getRowDimension($line)->setRowHeight(30);
			foreach($export_config[$table][$template]['columns'] as $title => $template) {
				if($column) {
					$o_sheet->setCellValue($supercol.$column.$line,$title);
					$o_sheet->getStyle($supercol.$column.$line)->applyFromArray($columntitlestyle);
					if (!($column = next($a_to_z))) {
						$supercol = array_shift($supercol_a_to_z);
						$column = reset($a_to_z);
					}
				}
			}


			$line = 2 ;

			while($result->nextHit()) {
				$column = reset($a_to_z);

				$supercol_a_to_z = range('A', 'Z');
				$supercol = '';

				// default to automatic row height. works pretty well in Excel but not so much in LibreOffice/OOo :-(
				$o_sheet->getRowDimension($line)->setRowHeight(-1);

				foreach($export_config[$table][$template]['columns'] as $title => $settings) {

					if (
						(strpos($settings['template'], 'ca_object_representations.media') !== false)
						&& 
						preg_match("!ca_object_representations\.media\.([A-Za-z0-9_\-]+)!", $settings['template'], $matches)
					) {
						$version = $matches[1];
						$info = $result->getMediaInfo('ca_object_representations.media', $version);
		
						if($info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
		
							if (is_file($path = $result->getMediaPath('ca_object_representations.media', $version))) {
								$image = "image".$supercol.$column.$line;
								$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
								$drawing->setName($image);
								$drawing->setDescription($image);
								$drawing->setPath($path);
								$drawing->setCoordinates($supercol.$column.$line);
								$drawing->setWorksheet($o_sheet);
								$drawing->setOffsetX(10);
								$drawing->setOffsetY(10);
							}

							$width = floor(intval($info['PROPERTIES']['width']) * $ratio_pixels_to_excel_width);
							$height = floor(intval($info['PROPERTIES']['height']) * $ratio_pixels_to_excel_height);

							// set the calculated withs for the current row and column,
							// but make sure we don't make either smaller than they already are
							if($width > $o_sheet->getColumnDimension($supercol.$column)->getWidth()) {
								$o_sheet->getColumnDimension($supercol.$column)->setWidth($width);	
							}
							if($height > $o_sheet->getRowDimension($line)->getRowHeight()){
								$o_sheet->getRowDimension($line)->setRowHeight($height);
							}

						}
					} elseif ($display_text = $result->getWithTemplate($settings['template'])) {
		
						$o_sheet->setCellValue($supercol.$column.$line, html_entity_decode(strip_tags(br2nl($display_text)), ENT_QUOTES | ENT_HTML5));
						// We trust the autosizing up to a certain point, but
						// we want column widths to be finite :-).
						// Since Arial is not fixed-with and font rendering
						// is different from system to system, this can get a
						// little dicey. The values come from experimentation.
						if ($o_sheet->getColumnDimension($supercol.$column)->getWidth() == -1) {  // don't overwrite existing settings
							if(strlen($display_text)>55) {
								$o_sheet->getColumnDimension($supercol.$column)->setWidth(50);
							}
						}
					}

					if (!($column = next($a_to_z))) {
						$supercol = array_shift($supercol_a_to_z);
						$column = reset($a_to_z);
					}
				}

				$line++;
			}

			// set column width to auto for all columns where we haven't set width manually yet
			foreach(range('A','Z') as $chr) {
				if ($o_sheet->getColumnDimension($chr)->getWidth() == -1) {
					$o_sheet->getColumnDimension($chr)->setAutoSize(true);	
				}
			}

			$o_writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);
			
			if($output === 'STREAM') {
				header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition:inline;filename='.$filename_stub.'.xlsx');
				$o_writer->save('php://output');
				exit;
			} else {
				$o_writer->save($path = ($output_filename ? $output_filename : './output.xlsx'));
				return [
					'mimetype' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'path' => $path
				];
			}
			break;
		case 'pptx':
			$ppt = new PhpOffice\PhpPresentation\PhpPresentation();

			$slide = 0;
			while($result->nextHit()) {
				if ($slide > 0) {
					$slide = $ppt->createSlide();
				} else {
					$slide = $ppt->getActiveSlide();
				}
		
				foreach($export_config[$table][$template]['columns'] as $title => $settings) {

					if (
						(strpos($settings['template'], 'ca_object_representations.media') !== false)
						&& 
						preg_match("!ca_object_representations\.media\.([A-Za-z0-9_\-]+)!", $settings['template'], $matches)
					) {
						$version = $matches[1];
						$info = $result->getMediaInfo('ca_object_representations.media', $version);
		
						if($info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
		
							if (is_file($path = $result->getMediaPath('ca_object_representations.media', $version))) {
								$shape = $slide->createDrawingShape();
								$shape->setName($info['ORIGINAL_FILENAME'])
									  ->setDescription('Image')
									  ->setPath($path)
									  ->setWidth(caConvertMeasurementToPoints(caGetOption('width', $settings, '100px'), array('dpi' => 96)))
									  ->setHeight(caConvertMeasurementToPoints(caGetOption('height', $settings, '100px'), array('dpi' => 96)))
									  ->setOffsetX(caConvertMeasurementToPoints(caGetOption('x', $settings, '100px'), array('dpi' => 96)))
									  ->setOffsetY(caConvertMeasurementToPoints(caGetOption('y', $settings, '100px'), array('dpi' => 96)));
								$shape->getShadow()->setVisible(true)
												   ->setDirection(45)
												   ->setDistance(10);
							}
						}
					} elseif ($display_text = html_entity_decode(strip_tags(br2nl($result->getWithTemplate($settings['template']))))) {
						switch($align = caGetOption('align', $settings, 'center')) {
							case 'center':
								$align = \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER;
								break;
							case 'left':
								$align = \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_LEFT;
								break;
							case 'right':
							default:
								$align = \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_RIGHT;
								break;
						}
		
						$shape = $slide->createRichTextShape()
							  ->setHeight(caConvertMeasurementToPoints(caGetOption('height', $settings, '100px'), array('dpi' => 96)))
							  ->setWidth(caConvertMeasurementToPoints(caGetOption('width', $settings, '100px'), array('dpi' => 96)))
							  ->setOffsetX(caConvertMeasurementToPoints(caGetOption('x', $settings, '100px'), array('dpi' => 96)))
							  ->setOffsetY(caConvertMeasurementToPoints(caGetOption('y', $settings, '100px'), array('dpi' => 96)));
						$shape->getActiveParagraph()->getAlignment()->setHorizontal($align);
						$textRun = $shape->createTextRun($display_text);
						$textRun->getFont()->setBold((bool)caGetOption('bold', $settings, false))
										   ->setSize(caConvertMeasurementToPoints(caGetOption('size', $settings, '36px'), array('dpi' => 96)))
										   ->setColor( new \PhpOffice\PhpPresentation\Style\Color( caGetOption('color', $settings, 'cccccc') ) );
					}

				}

				$slide++;
			}

			
			$filename = caGetOption('filename', $export_config[$table][$template], 'export_results');
			$filename = preg_replace('![^A-Za-z0-9_\-\.]+!', '_', $filename);
			
			$o_writer = \PhpOffice\PhpPresentation\IOFactory::createWriter($ppt, 'PowerPoint2007');
			if($output === 'STREAM') { 
				header('Content-type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
				header('Content-Disposition:inline;filename='.$filename_stub.'.pptx');
			
				$o_writer->save($filepath = caGetTempFileName('caPPT', 'pptx'));
			
				set_time_limit(0);
				$o_fp = @fopen($filepath, "rb");
				while(is_resource($o_fp) && !feof($o_fp)) {
					print(@fread($o_fp, 1024*8));
					ob_flush();
					flush();
				}
				@unlink($filepath);
				exit;
			} else {
				$o_writer->save($path = ($output_filename ? $output_filename : "./ppt_output.pptx"));
				
				return [
					'mimetype' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
					'path' => $path
				];
			}
			break;
		case 'pdf':
			//
			// PDF output
			//
			if(!($filename = $output_filename)) {
				if(!($filename = $o_view->getVar('filename'))) { 
					$filename = caGetOption('filename', $template_info, 'export_results');
				}
				$filename .= '_'.date("Y-m-d").'.pdf';
			}
			
			if($output === 'STREAM') { 
				caExportViewAsPDF($o_view, $template_info, $filename, $options);
				$o_controller = AppController::getInstance();
				$o_controller->removeAllPlugins();
				exit;
			} else {
				$tmp_filename = caGetTempFileName('caExportResult', '');
				if(!caExportViewAsPDF($o_view, $template_info, $filename, ['writeToFile' => $tmp_filename])) {
					return null;
				}
				return [
					'mimetype' => 'application/pdf', 
					'path' => $tmp_filename
				];
			}
			break;
	}
	
	return null;
}
# ----------------------------------------
/**
 * Export view as PDF using a specified template. It is assumed that all view variables required for 
 * rendering are already set.
 * 
 * @param View $po_view
 * @param string $ps_template_identifier
 * @param string $ps_output_filename
 * @param array $pa_options Options include:
 * 		returnFile = return file content instead of streaming to browser -> passed through to caExportContentAsPDF
 *		writeToFile = write content to filepath
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
		
		// Copy download-time user parameters into view
		if(is_array($pa_template_info) && is_array($pa_template_info['params'])) {
			$values = [];
			foreach($pa_template_info['params'] as $n => $p) {
				if((bool)$p['multiple'] ?? false) {
					$po_view->setVar("param_{$n}", $values[$n] = $po_view->request->getParameter($n, pArray));
				} else {
					$po_view->setVar("param_{$n}", $values[$n] = $po_view->request->getParameter($n, pString));
				}
			}
			if($template_type = caGetOption('printTemplateType', $pa_options, null)) {
				// Set defaults for form
				$values = Session::setVar("print_{$template_type}_options_".pathinfo($pa_template_info['path'] ?? null, PATHINFO_FILENAME), $values);
			}
		}
		$vs_content = $po_view->render($pa_template_info['path']);
		$vb_printed_properly = caExportContentAsPDF($vs_content, $pa_template_info, $ps_output_filename, $pa_options);
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
 * @param array $pa_options Options include:
 * 		returnFile = return file content instead of streaming to browser. [Default is false]
 *		writeToFile = path to file to write output. [Default is null] 
 *		stream = stream output to browser. [Default is true unless writeFile is set]
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
	
		$ps_output_filename = (($ps_output_filename) ? preg_replace('![^A-Za-z0-9_\-\.]+!', '_', $ps_output_filename) : 'export').".pdf";

		if(caGetOption('returnFile', $pa_options, false)){
			return $o_pdf->render($ps_content, ['stream' => false]);
		} else {
			$path = caGetOption('writeToFile', $pa_options, null);
			$rendered_content = $o_pdf->render($ps_content, ['stream'=> caGetOption('stream', $pa_options, !(bool)$path), 'filename' => $ps_output_filename]);
			if($path) {
				file_put_contents($path, $rendered_content);
			}
			$vb_printed_properly = true;
		}
	} catch (Exception $e) {
		$vb_printed_properly = false;
		throw new ApplicationException(_t("Could not generate PDF: ".$e->getMessage()));
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
