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
function caExportFormatForTemplate(string $table, string $template) : ?string {
	switch(substr($template, 0, 5)) {
		case '_pdf_':
			return 'PDF';
		case '_tab_':
			return 'TAB';
		case '_csv_':
			return 'CSV';
	}
	switch(substr($template, 0, 6)) {
		case '_xlsx_':
			return 'Excel';
		case '_docx_':
			return 'Word';
	}

	$config = Configuration::load();
	$export_config = $config->getAssoc('export_formats');
	
	if (is_array($export_config) && is_array($export_config[$table]) && is_array($export_config[$table][$template])) {
		
		switch($export_config[$table][$template]['type']) {
			case 'xlsx':
				return 'Excel';
				break;
				break;
			case 'csv':
				return 'CSV';
				break;
			case 'tab':
				return 'TAB';
				break;
		}
	}
	return null;
}
# ----------------------------------------
/**
 * Export instance as PDF using template
 * 
 * @param RequestHTTP $request
 * @param BaseModel $pt_subject
 * @param string $ps_template The name of the template to render
 * @param string $ps_output_filename
 * @param array $options
 * @return bool
 *
 * @throws ApplicationException
 */
function caExportItemAsPDF($request, $pt_subject, $ps_template, $ps_output_filename, $options=null) {
	$view = new View($request, $request->getViewsDirectoryPath().'/');
	
	$pa_access_values = caGetOption('checkAccess', $options, null);
	
	$view->setVar('t_subject', $pt_subject);
	
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
		
		if ($vn_display_id && ($t_display->isLoaded()) && ($t_display->haveAccessToDisplay($request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
			$view->setVar('t_display', $t_display);
			$view->setVar('display_id', $vn_display_id);
		
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
			$view->setVar('placements', $va_display_list);
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
	$request->isDownload(true);
	
	$options['writeFile'] = $cache_path = __CA_BASE_DIR__.'/export/'.$pt_subject->tableName().'_'.$pt_subject->getPrimaryKey().'.pdf';
	if(!caGetOption('dontCache', $options, false)) {
		
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
		
	caDoTemplateTagSubstitution($view, $pt_subject, $va_template_info['path'], ['checkAccess' => $pa_access_values, 'render' => false]);	
	caExportViewAsPDF($view, $va_template_info, $ps_output_filename, $options);
	
	return true;
}
# ----------------------------------------
/**
 * Export view as PDF using a specified template. It is assumed that all view variables required for 
 * rendering are already set.
 * 
 * @param View $view
 * @param string $ps_template_identifier
 * @param string $ps_output_filename
 * @param array $options Options include:
 * 		returnFile = return file content instead of streaming to browser -> passed through to caExportContentAsPDF
 *		writeToFile = write content to filepath
 * @return bool
 *
 * @throws ApplicationException
 */
function caExportViewAsPDF($view, $template_identifier, $output_filename, $options=null) {
	if (is_array($template_identifier)) {
		$template_info = $template_identifier;
	} else {
		$va_template = explode(':', $template_identifier);
		$template_info = caGetPrintTemplateDetails($va_template[0], $va_template[1]);
	}
	if (!is_array($template_info)) { throw new ApplicationException("No template information specified"); }
	$vb_printed_properly = false;
	
	$include_header_footer = caGetOption('includeHeaderFooter', $template_info, false);
	
	try {
		$o_pdf = new PDFRenderer();
		$view->setVar('PDFRenderer', $o_pdf->getCurrentRendererCode());

		$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $template_info, 'letter'), 'mm', caGetOption('pageOrientation', $template_info, 'portrait'));
		$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];
		$view->setVar('pageWidth', "{$vn_page_width}mm");
		$view->setVar('pageHeight', "{$vn_page_height}mm");
		$view->setVar('marginTop', caGetOption('marginTop', $template_info, '0mm'));
		$view->setVar('marginRight', caGetOption('marginRight', $template_info, '0mm'));
		$view->setVar('marginBottom', caGetOption('marginBottom', $template_info, '0mm'));
		$view->setVar('marginLeft', caGetOption('marginLeft', $template_info, '0mm'));
		$view->setVar('base_path', $vs_base_path = pathinfo($template_info['path'], PATHINFO_DIRNAME));

		$view->addViewPath($vs_base_path."/local");
		$view->addViewPath($vs_base_path);
		
		// Copy download-time user parameters into view
		$template_type = caGetOption('printTemplateType', $options, null);
		$values = $template_type ? caGetPrintTemplateParameters($template_type, $template_info['identifier'], ['view' => $view, 'request' => $view->request]) : [];
		
		$template_dir = pathinfo($template_info['path'], PATHINFO_DIRNAME);
		$vs_content = '';
		if($include_header_footer) {
			$vs_content .= $view->render("{$template_dir}/pdfStart.php").$view->render("{$template_dir}/header.php");
		}	
		$vs_content .= $view->render($template_info['path']);

		if($include_header_footer) {
			$vs_content .= $view->render("{$template_dir}/footer.php").$view->render("{$template_dir}/pdfEnd.php");
		}
		$vb_printed_properly = caExportContentAsPDF($vs_content, $template_info, $output_filename, $options);
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
 * @param array $options Options include:
 * 		returnFile = return file content instead of streaming to browser. [Default is false]
 *		writeToFile = path to file to write output. [Default is null] 
 *		stream = stream output to browser. [Default is true unless writeFile is set]
 * @return bool
 *
 * @throws ApplicationException
 */
function caExportContentAsPDF($ps_content, $pa_template_info, $ps_output_filename, $options=null) {
	if (!is_array($pa_template_info)) { throw new ApplicationException("No template information specified"); }
	$vb_printed_properly = false;
	
	try {
		$o_pdf = new PDFRenderer();

		$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $pa_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $pa_template_info, 'portrait'));
		$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];

		$o_pdf->setPage(caGetOption('pageSize', $pa_template_info, 'letter'), caGetOption('pageOrientation', $pa_template_info, 'portrait'), caGetOption('marginTop', $pa_template_info, '0mm'), caGetOption('marginRight', $pa_template_info, '0mm'), caGetOption('marginBottom', $pa_template_info, '0mm'), caGetOption('marginLeft', $pa_template_info, '0mm'));
	
		$ps_output_filename = (($ps_output_filename) ? preg_replace('![^A-Za-z0-9_\-\.]+!', '_', $ps_output_filename) : 'export').".pdf";

		if(caGetOption('returnFile', $options, false)){
			return $o_pdf->render($ps_content, ['stream' => false]);
		} else {
			$path = caGetOption('writeToFile', $options, null);
			$rendered_content = $o_pdf->render($ps_content, ['stream'=> caGetOption('stream', $options, !(bool)$path), 'filename' => $ps_output_filename]);
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
 * @param array $options Options include:
 * 		t_subject = a model instance to evaluate the filename template relative to. [Default is null]
 *		values = an array of values, where keys are tag names in the filename template. [Default is null]
 *
 * Note that if neither the t_subject or values options are set the template will be evaluated as static text.
 *
 * @return string
 */
function caGenerateDownloadFileName(string $ps_template, ?array $options=null) : string {
	$pt_subject = caGetOption('t_subject', $options, null);
	if ((strpos($ps_template, "^") !== false) && ($pt_subject)) {
		return caProcessTemplateForIDs($ps_template, $pt_subject->tableName(), [$pt_subject->getPrimaryKey()], $options);
	} elseif ((strpos($ps_template, "^") !== false) && is_array($va_values = caGetOption('values', $options, null))) {
		return caProcessTemplate($ps_template, $va_values, $options);
	}
	
	switch(strtolower($ps_template)) {
		case 'label':
			return $pt_subject ? $pt_subject->getLabelForDisplay() : "export";
			break;
	}
	
	return $ps_template;
}
# ----------------------------------------
/**
 * Export search result set as a PDF, XLSX, DOCX, TAB or CSV file.
 * 
 * @param RequestHTTP $request
 * @param SearchResult $result
 * @param string $ps_template
 * @param string $output_filename
 * @param array $options Options include:
 *		output = where to output data. Values may be FILE (write to file) or STREAM. [Default is stream]
 *
 * @return ?array|bool If output is FILE, path to file or null if file could not be generated. If output is STREAM null returned on error; true returned on success
 *
 * @throws ApplicationException
 */
function caExportResult(RequestHTTP $request, $result, string $template, string $output_filename, ?array $options=null) {
	$output = caGetOption('output', $options, 'STREAM');
	
	$config = Configuration::load();
	$view = new View($request, $request->getViewsDirectoryPath().'/');
	
	$view->setVar('result', $result);
	$view->setVar('t_set', caGetOption('set', $options, null));
	$view->setVar('criteria_summary', caGetOption('criteriaSummary', $options, ''));
	
	$table = $result->tableName();
	
	$type = $display_id = null;
	$export_config = $template_info = null;
	
	if (!(bool)$config->get('disable_pdf_output') && substr($template, 0, 5) === '_pdf_') {
		$template_info = caGetPrintTemplateDetails(caGetOption('printTemplateType', $options, 'results'), substr($template, 5));
		$type = 'pdf';
	} elseif (!(bool)$config->get('disable_pdf_output') && (substr($template, 0, 9) === '_display_')) {
		$display_id = substr($template, 9);
		
		if ($display_id && ($t_display->haveAccessToDisplay($request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
			$view->setVar('display', $t_display);
			
			$placements = $t_display->getPlacements(array('settingsOnly' => true));
			$view->setVar('display_list', $placements);
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
			$view->setVar('display_list', $display_list);
		} else {
			throw new ApplicationException(_t("Invalid format %1", $template));
		}
		$template_info = caGetPrintTemplateDetails(caGetOption('printTemplateType', $options, 'results'), 'display');
		$type = 'pdf';
	} elseif(!(bool)$config->get('disable_export_output') && preg_match('!^_([a-z]+)_!', $template, $m)) {
		switch($m[1]) {
			case 'csv':
			case 'tab':
				$type = $m[1];
				$display_id = substr($template, 5);
				break;
			case 'xlsx':
			case 'docx':
				$type = $m[1];
				$display_id = substr($template, 6);
				break;
			default:
				// Look it up in app.conf export_formats
				$export_config = $config->getAssoc('export_formats');
				if (is_array($export_config) && is_array($export_config[$table]) && is_array($export_config[$table][$template])) {
			
					switch($export_config[$table][$template]['type']) {
						case 'xlsx':
							$type = 'xlsx';
							break;
						case 'csv':
							$type = 'csv';
							break;
						case 'tab':
							$type = 'tab';
							break;
					}
				} else {
					throw new ApplicationException(_t("Invalid format %1", $template));
				}
			}
	}
	
	$t_display = new ca_bundle_displays();
	if ($display_id && ($t_display->load($display_id)) && ($t_display->haveAccessToDisplay($request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
		$placements = $t_display->getPlacements(['settingsOnly' => true]);
		$view->setVar('display_list', $placements);
		$view->setVar('display', $t_display);
		
		foreach($placements as $placement_id => $display_item) {
			$settings = caUnserializeForDatabase($display_item['settings']);
		
			// get column header text
			$header = $display_item['display'];
			if (isset($settings['label']) && is_array($settings['label'])) {
				$tmp = caExtractValuesByUserLocale(array($settings['label']));
				if ($tmp = array_shift($tmp)) { $header = $tmp; }
			}
		
			$display_list[$placement_id] = [
				'placement_id' => $placement_id,
				'bundle_name' => $display_item['bundle_name'],
				'display' => $header,
				'settings' => $settings
			];
		}
		$view->setVar('display_list', $display_list);
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
		case 'tab':
		case 'csv':
			$delimiter = ($type === 'tab') ? "\t" : ",";
			$mimetype = ($type === 'tab') ? "text/tab-separated-values" : "text/csv";
			$extension = ($type === 'tab') ? "tsv" : "csv";
			
			$display_list = $view->getVar('display_list');
			$rows = $row = [];
			
			// Header
			foreach($display_list as $display_item) {
				$row[] = $display_item['display'];
			}
			$rows[] = join($delimiter, $row);
		
			$result->seek(0);
		
			$t_display = $view->getVar('display');
			while($result->nextHit()) {
				$row = [];
				foreach($display_list as $placement_id => $display_item) {
					$value = html_entity_decode($t_display->getDisplayValue($result, $placement_id, ['convert_codes_to_display_text' => true, 'convertLineBreaks' => false]), ENT_QUOTES, 'UTF-8');
					$value = preg_replace("![\r\n\t]+!", " ", $value);
					
					// quote values as required
					if (preg_match("![^A-Za-z0-9 .;]+!", $value)) {
						$value = '"'.str_replace('"', '""', $value).'"';
					}
					$row[] = $value;
				}
				$rows[] = join($delimiter, $row);
			}
			
			if($output === 'STREAM') { 
				$request->isDownload(true);				
				header("Content-Disposition: attachment; filename=export_{$output_filename}.{$extension}");
				header("Content-type: {$mimetype}");
				print join("\n", $rows);
				return;
			} else {
				$tmp_filename = caGetTempFileName('caExportResult', '');
				// write file to $tmp_filename
				file_put_contents($tmp_filename, join("\n", $rows));
				return [
					'mimetype' =>  $mimetype, 
					'path' => $tmp_filename,
					'extension' =>  $extension
				];
			}
			break;
		case 'xlsx':

			$precision = ini_get('precision');
			ini_set('precision', 12);
	
			$t_display				= $view->getVar('display');
			$va_display_list 		= $view->getVar('display_list');

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
							'shrinkToFit'=> false),
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
							'shrinkToFit'=> false),
					'borders' => array(
							'allborders'=>array(
									'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)));

			$o_sheet->getParent()->getDefaultStyle()->applyFromArray($cellstyle);
			$o_sheet->setTitle("CollectiveAccess");
	
			$line = 1;

			$column = reset($a_to_z);
		
			// Add default reps
			if (
				($version = $config->get($result->tableName()."_always_include_primary_representation_media_in_xlsx_output")) 
				&& 
				!sizeof(array_filter($display_list, function($v) { return preg_match('!^ca_object_representations.media!', $v['bundle']); }))
			) {
				array_unshift($display_list, [
					'display' => _t('Media'),
					'bundle' => "ca_object_representations.media.{$version}",
					'bundle_name' => "ca_object_representations.media.{$version}",
				]);
			}
	
			// Column headers
			$o_sheet->getRowDimension($line)->setRowHeight(30);
			foreach($display_list as $placement_id => $info) {
				if($column) {
					$o_sheet->setCellValue($supercol.$column.$line, $info['display']);
					$o_sheet->getStyle($supercol.$column.$line)->applyFromArray($columntitlestyle);
					if (!($column = next($a_to_z))) {
						$supercol = array_shift($supercol_a_to_z);
						$column = reset($a_to_z);
					}
				}
			}
	
			$line = 2 ;

			// Other lines
			while($result->nextHit()) {
				if(!is_array($media_versions = $result->getMediaVersions('ca_object_representations.media'))) { $media_versions = []; }
		
				$column = reset($a_to_z);
		
				$supercol_a_to_z = range('A', 'Z');
				$supercol = '';

				// default to automatic row height. works pretty well in Excel but not so much in LibreOffice/OOo :-(
				$o_sheet->getRowDimension($line)->setRowHeight(-1);

				foreach($display_list as $info) {
					$placement_id = $info['placement_id'];
			
					if (is_array($info['settings']) && isset($info['settings']['format']) && ($tags = array_filter(caGetTemplateTags($info['settings']['format']), function($v) { return preg_match("!^ca_object_representations.media.!", $v); }))) {
						// Transform bundle with template including media into a media bundle as that's the only way to show media within an XLSX
						$info['bundle_name'] = $tags[0];
					}
					if (
						(preg_match('!^ca_object_representations.media!', $info['bundle_name']))
						&&
						!strlen($info['settings']['format'])
						&&
						(!isset($info['settings']['display_mode']) || ($info['settings']['display_mode'] !== 'url'))
					) {
						$bits = explode(".", $info['bundle_name']);
						$version = array_pop($bits);
				
						if (!in_array($version, $media_versions)) { $version = $media_versions[0]; }
	
						$info = $result->getMediaInfo('ca_object_representations.media', $version);
				
						if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
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
					} elseif ($display_text = $t_display->getDisplayValue($result, $placement_id, array_merge(['request' => $request, 'purify' => true], is_array($info['settings']) ? $info['settings'] : []))) {
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
	
			if($request && ($config->get('excel_report_header_enabled') || $config->get('excel_report_footer_enabled'))){
				$o_sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
				$o_sheet->getPageMargins()->setTop(1);
				$o_sheet->getPageMargins()->setRight(0.75);
				$o_sheet->getPageMargins()->setLeft(0.75);
				$o_sheet->getPageMargins()->setBottom(1);
				$o_sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,1);
		
				if($request && $config->get('excel_report_header_enabled')){
					if(file_exists($request->getThemeDirectoryPath()."/graphics/logos/".$request->config->get('report_img'))){
						$logo_path = $request->getThemeDirectoryPath().'/graphics/logos/'.$request->config->get('report_img');
					}
					$objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing();
					$objDrawing->setName('Image');
					$objDrawing->setPath($vs_logo_path);
					$objDrawing->setHeight(36);
					$o_sheet->getHeaderFooter()->addImage($objDrawing, \PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter::IMAGE_HEADER_LEFT);
					$criteria_summary = str_replace("&", "+", strip_tags(html_entity_decode($criteria_summary)));
					$criteria_summary = (strlen($vs_criteria_summary) > 90) ? mb_substr($criteria_summary, 0, 90)."..." : $criteria_summary;
					$criteria_summary = wordwrap($vs_criteria_summary, 50, "\n", true);
					$o_sheet->getHeaderFooter()->setOddHeader('&L&G& '.(($config->get('excel_report_show_search_term')) ? '&R&B&12 '.$vs_criteria_summary : ''));
			
				}
				if(!$request || $config->get('excel_report_footer_enabled')){
					$t_instance = Datamodel::getInstanceByTableName($result->tableName(), true);
					$o_sheet->getHeaderFooter()->setOddFooter('&L&10'.ucfirst($t_instance->getProperty('NAME_SINGULAR').' report').' &C&10Page &P of &N &R&10 '.date("m/t/y"));
				}
			}
	
			$o_writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);

			ini_set('precision', $precision);
			
			if($output === 'STREAM') {
				$request->isDownload(true);
				header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition:inline;filename='.$filename_stub.'.xlsx');
				$o_writer->save('php://output');
				exit;
			} else {
				$o_writer->save($path = ($output_filename ? $output_filename : './output.xlsx'));
				return [
					'mimetype' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'path' => $path,
					'extension' => 'xlsx'
				];
			}
			break;
		case 'docx':
			// For easier calculation
			// 1 cm = 1440/2.54 = 566.93 twips
			$cmToTwips = 567;

			$phpWord = new \PhpOffice\PhpWord\PhpWord();

			// Every element you want to append to the word document is placed in a section.

			// New portrait section
			$sectionStyle = array(
				'orientation' => 'portrait',
				'marginTop' => 2 * $cmToTwips,
				'marginBottom' => 2 * $cmToTwips,
				'marginLeft' => 2 * $cmToTwips,
				'marginRight' => 2 * $cmToTwips,
				'headerHeight' => 1 * $cmToTwips,
				'footerHeight' => 1 * $cmToTwips,
				'colsNum' => 1,
				'pageSizeW' => 8.5 * 1440,
				'pageSizeH' => 11 * 1440
	
			);
			$section = $phpWord->addSection($sectionStyle);

			// Add header for all pages
			$header = $section->addHeader();

			$headerimage =  ($request && $config->get('report_img')) ? $request->getThemeDirectoryPath()."/graphics/logos/".$request->config->get('report_img') : '';
			if(file_exists($headerimage)){
				$header->addImage($headerimage,array('height' => 30,'wrappingStyle' => 'inline'));
			}

			// Add footer
			$footer = $section->addFooter();
			$footer->addPreserveText('{PAGE}/{NUMPAGES}', null, array('align' => 'right'));

			// Defining font style for headers
			$phpWord->addFontStyle('headerStyle',array(
				'name'=>'Verdana', 
				'size'=>12, 
				'color'=>'444477'
			));


			// Defining font style for display values
			$phpWord->addFontStyle('displayValueStyle',array(
				'name'=>'Verdana', 
				'size'=>14, 
				'color'=>'000000'
			));
			$styleHeaderFont = array('bold'=>true, 'size'=>13, 'name'=>'Calibri');
			$styleBundleNameFont = array('bold'=>false, 'underline'=>'single', 'color'=>'666666', 'size'=>11, 'name'=>'Calibri');
			$styleContentFont = array('bold'=>false, 'size'=>11, 'name'=>'Calibri');
	
			// Define table style arrays
			$styleTable = array('borderSize'=>0, 'borderColor'=>'ffffff', 'cellMargin'=>80);
			$styleFirstRow = array('borderBottomSize'=>18, 'borderBottomColor'=>'CCCCCC');

			// Define cell style arrays
			$styleCell = array('valign'=>'center');
			$styleCellBTLR = array('valign'=>'center');

			// Define font style for first row
			$fontStyle = array('bold'=>true, 'align'=>'center');

			// Add table style
			$phpWord->addTableStyle('myOwnTableStyle', $styleTable, $styleFirstRow);


			while($result->nextHit()) {
				$table = $section->addTable('myOwnTableStyle');
				$table->addRow();
				$list = $display_list;
	
				$info = $result->getMediaInfo('ca_object_representations.media',"medium");
				$path = $result->getMediaPath('ca_object_representations.media',"medium");

				$media_added = false;
				if(($info['MIMETYPE'] === 'image/jpeg') && $path) { // don't try to insert anything non-jpeg into an Excel file		
					if (is_file($path)) {
						// First column : media
						$mediaCell = $table->addCell( 5 * $cmToTwips);
						$mediaCell->addImage(
							$path,
							array(
								'width' => 195,
								'wrappingStyle' => 'inline'
							)
						);
						$media_added = true;
					}
				}


				// Second column : bundles
				$contentCell = $table->addCell(($media_added ? 12 : 17) * $cmToTwips);

				$contentCell->addText(
					caEscapeForXML(html_entity_decode(strip_tags(br2nl($result->get('preferred_labels'))), ENT_QUOTES | ENT_HTML5)),
					$styleHeaderFont
				);

				foreach($list as $placement_id => $info) {
					if (
						(strpos($info['bundle_name'], 'ca_object_representations.media') !== false)
						&&
						($info['settings']['display_mode'] == 'media') // make sure that for the 'url' mode we don't insert the image here
					) {
						// Inserting bundle name on one line
						$contentCell->addText(caEscapeForXML($info['display']).': ', $styleBundleNameFont);

						// Fetching version asked & corresponding file
						$version = str_replace("ca_object_representations.media.", "", $info['bundle_name']);
						$info = $result->getMediaInfo('ca_object_representations.media',$version);
			
						// If it's a JPEG, print it (basic filter to avoid non handled media version)
						if($info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
							$path = $result->getMediaPath('ca_object_representations.media',$version);
							if (is_file($path)) {
								$contentCell->addImage(
									$path
								);
							}
						}

					} elseif ($display_text = $t_display->getDisplayValue($result, $placement_id, array_merge(array('request' => $request, 'purify' => true), is_array($info['settings']) ? $info['settings'] : array()))) {

						$textrun = $contentCell->createTextRun();
			
						if ($request && $config->get('report_include_labels_in_docx_output')) {
							$textrun->addText(caEscapeForXML($info['display']).': ', $styleBundleNameFont);
						}
						$textrun->addText(
							preg_replace("![\n\r]!", "<w:br/>", caEscapeForXML(html_entity_decode(strip_tags(br2nl($display_text)), ENT_QUOTES | ENT_HTML5))),
							$styleContentFont
						);

					}}
				$line++;
				// Two text break
				$section->addTextBreak(2);
			}

			// Finally, write the document:
			$o_writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
			if($output === 'STREAM') {
				$request->isDownload(true);
				header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
				header('Content-Disposition: inline;filename='.$filename_stub.'.docx');
				$o_writer->save('php://output');
				return;
			} else {
				$o_writer->save($path = ($output_filename ? $output_filename : './output.xlsx'));
				return [
					'mimetype' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'path' => $path,
					'extension' => 'docx'
				];
			}
			break;
		case 'pdf':
			//
			// PDF output
			//
			if(!($filename = $output_filename)) {
				if(!($filename = $view->getVar('filename'))) { 
					$filename = caGetOption('filename', $template_info, 'export_results');
				}
				$filename .= '_'.date("Y-m-d").'.pdf';
			}
			
			if($output === 'STREAM') { 
				$request->isDownload(true);
				caExportViewAsPDF($view, $template_info, $filename, array_merge($options, ['printTemplateType' => 'results']));
			} else {
				$tmp_filename = caGetTempFileName('caExportResult', '');
				if(!caExportViewAsPDF($view, $template_info, $filename, ['writeToFile' => $tmp_filename, 'printTemplateType' => 'results'])) {
					return null;
				}
				return [
					'mimetype' => 'application/pdf', 
					'path' => $tmp_filename,
					'extension' => 'pdf'
				];
			}
			break;
	}
	
	return null;
}
# ----------------------------------------
/**
 *
 */
function caExportAsLabels($request, SearchResult $result, string $label_code, string $output_filename, ?string $title=null, ?array $options=null) {
	$view = new View($request, $request->getViewsDirectoryPath().'/');
	$output = caGetOption('output', $options, 'STREAM');
	$config = Configuration::load();
	
	$show_borders = (bool)$config->get('add_print_label_borders');
	
	//
	// PDF output
	//
	$tinfo = caGetPrintTemplateDetails('labels', substr($label_code, 5));
	if (!is_array($tinfo)) {
		//$this->postError(3110, _t("Could not find view for PDF"),"BaseFindController->_genLabels()");
		return;
	}
	
	$view->setVar('template_info', $tinfo);
	if(is_array($tinfo) && is_array($tinfo['params'])) {
		$values = [];
		foreach($tinfo['params'] as $n => $p) {
			if($n == 'add_print_label_borders') {
				$show_borders = (bool)$request->getParameter($n, pString);
			}
			if((bool)$p['multiple'] ?? false) {
				$view->setVar("param_{$n}", $values[$n] = $request->getParameter($n, pArray));
			} else {
				$view->setVar("param_{$n}", $values[$n] = $request->getParameter($n, pString));
			}
		}
		Session::setVar("print_labels_options_{$m[2]}", $values);
	}
	
	$border = ($show_borders) ? "border: 1px dotted #000000; " : "";
	
	try {
		$view->setVar('title', $title);
		$view->setVar('result', $result);
		
		$view->setVar('base_path', $base_path = pathinfo($tinfo['path'], PATHINFO_DIRNAME).'/');
		$view->addViewPath([$base_path, "{$base_path}/local"]);
	
		$o_pdf = new PDFRenderer();
		$view->setVar('PDFRenderer', $renderer = $o_pdf->getCurrentRendererCode());
		
		// render labels
		$width = 				caConvertMeasurement(caGetOption('labelWidth', $tinfo, null), 'mm');
		$height = 				caConvertMeasurement(caGetOption('labelHeight', $tinfo, null), 'mm');
		
		$top_margin = 			caConvertMeasurement(caGetOption('marginTop', $tinfo, null), 'mm');
		$bottom_margin = 		caConvertMeasurement(caGetOption('marginBottom', $tinfo, null), 'mm');
		$left_margin = 			caConvertMeasurement(caGetOption('marginLeft', $tinfo, null), 'mm');
		$right_margin = 		caConvertMeasurement(caGetOption('marginRight', $tinfo, null), 'mm');
		
		$horizontal_gutter = 	caConvertMeasurement(caGetOption('horizontalGutter', $tinfo, null), 'mm');
		$vertical_gutter = 		caConvertMeasurement(caGetOption('verticalGutter', $tinfo, null), 'mm');
		
		$page_size =			PDFRenderer::getPageSize(caGetOption('pageSize', $tinfo, 'letter'), 'mm', caGetOption('pageOrientation', $tinfo, 'portrait'));
		$page_width = 			$page_size['width']; $page_height = $page_size['height'];
		
		$label_count = 0;
		
		$left = $left_margin;
		$top = $top_margin;
		
		$view->setVar('pageWidth', "{$page_width}mm");
		$view->setVar('pageHeight', "{$page_height}mm");				
		$view->setVar('marginTop', caGetOption('marginTop', $tinfo, '0mm'));
		$view->setVar('marginRight', caGetOption('marginRight', $tinfo, '0mm'));
		$view->setVar('marginBottom', caGetOption('marginBottom', $tinfo, '0mm'));
		$view->setVar('marginLeft', caGetOption('marginLeft', $tinfo, '0mm'));
		
		$content = $view->render("pdfStart.php");
		
		$defined_vars = array_keys($view->getAllVars());		// get list defined vars (we don't want to copy over them)
		$tag_list = $view->getTagList($tinfo['path']);				// get list of tags in view
		
		$page_count = 0;
		while($result->nextHit()) {
			caDoPrintViewTagSubstitution($view, $result, $tinfo['path'], array('checkAccess' => caGetOption('checkAccess', $options, null)));
			
			$content .= "<div style=\"{$border} position: absolute; width: {$width}mm; height: {$height}mm; left: {$left}mm; top: {$top}mm; overflow: hidden; padding: 0; margin: 0;\">";
			$content .= $view->render($tinfo['path']);
			$content .= "</div>\n";
			
			$label_count++;
			
			$left += $vertical_gutter + $width;
			
			if (($left + $width) > $page_width) {
				$left = $left_margin;
				$top += $horizontal_gutter + $height;
			}
			if (($top + $height) > (($page_count + 1) * $page_height)) {
				
				// next page
				if ($label_count < $result->numHits()) { $content .= "<div class=\"pageBreak\">&nbsp;</div>\n"; }
				$left = $left_margin;
					
				switch($renderer) {
					case 'wkhtmltopdf':
						// WebKit based renderers (wkhtmltopdf) want things numbered relative to the top of the document (Eg. the upper left hand corner of the first page is 0,0, the second page is 0,792, Etc.)
						$page_count++;
						$top = ($page_count * $page_height) + $top_margin;
						break;
					case 'domPDF':
					default:
						// domPDF wants things positioned in a per-page coordinate space (Eg. the upper left hand corner of each page is 0,0)
						$top = $top_margin;								
						break;
				}
			}
		}
		
		$content .= $view->render("pdfEnd.php");
		$o_pdf->setPage(caGetOption('pageSize', $tinfo, 'letter'), caGetOption('pageOrientation', $tinfo, 'portrait'));
		
		if($output === 'STREAM') { 
			$request->isDownload(true);
			$o_pdf->render($content, ['stream'=> true, 'filename' => ($filename = $view->getVar('filename')) ? $filename : caGetOption('filename', $tinfo, 'labels.pdf')]);
		} else {
			$tmp_filename = caGetTempFileName('caExportResult', '');
			
			file_put_contents($tmp_filename, $o_pdf->render($content, ['stream'=> false]));
		
			return [
				'mimetype' => 'application/pdf', 
				'path' => $tmp_filename,
				'extension' => 'pdf'
			];
		}
		return true;
	} catch (Exception $e) {
		return false;
	}
}
# ----------------------------------------
/**
 *
 */
function caExportSummary($request, BaseModel $t_instance, string $template, int $display_id, string $output_filename, ?string $title=null, ?array $options=null) {
	$config = Configuration::load();
	$output = caGetOption('output', $options, 'STREAM');
	$access_values = caGetOption('checkAccess', $options, null);
	
	$table = $t_instance->tableName();
	$view = new View($request, $request->getViewsDirectoryPath().'/');
	
	$t_display = new ca_bundle_displays();
	$displays = caExtractValuesByUserLocale($t_display->getBundleDisplays(['table' => $t_instance->tableNum(), 'user_id' => $request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'restrictToTypes' => [$t_instance->getTypeID()]]));

	$view->setVar('t_subject', $t_instance);
	
	// PDF templates set in the display list need to be remapped to the template parameter
	if(substr($display_id, 0, 5) === '_pdf_') {
		$template = $display_id;
		$display_id = null;
	}

	if ((!$display_id ) || !isset($displays[$display_id])) {
		$display_id = $request->user->getVar("{$table}_summary_display_id");
	}
	
	if (!isset($displays[$display_id]) || (is_array($displays[$display_id]['settings']['show_only_in'] ?? null) && sizeof($displays[$display_id]['settings']['show_only_in']) && !in_array('editor_summary', $displays[$display_id]['settings']['show_only_in']))) {
		$tmp = array_filter($displays, function($v) { return isset($v['settings']['show_only_in']) && is_array($v['settings']['show_only_in']) && in_array('editor_summary', $v['settings']['show_only_in']); });
		$display_id = sizeof($tmp) > 0 ? array_shift(array_keys($tmp)) : 0;
	}
	
	$view->setVar('t_display', $t_display);
	$view->setVar('bundle_displays', $displays);

	// Check validity and access of specified display
	$media_to_append = [];
	if ($t_display->load($display_id) && ($t_display->haveAccessToDisplay($request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
		$view->setVar('display_id', $display_id);

		$placements = $t_display->getPlacements(['returnAllAvailableIfEmpty' => true, 'table' => $t_instance->tableNum(), 'user_id' => $request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true, 'omitEditingInfo' => true]);
		$display_list = array();
		foreach($placements as $placement_id => $display_item) {
			$settings = caUnserializeForDatabase($display_item['settings']);

			// get column header text
			$header = $display_item['display'];
			if (isset($settings['label']) && is_array($settings['label'])) {
				if ($tmp = array_shift(caExtractValuesByUserLocale(array($settings['label'])))) { $header = $tmp; }
			}

			$display_list[$placement_id] = array(
				'placement_id' => $placement_id,
				'bundle_name' => $display_item['bundle_name'],
				'display' => $header,
				'settings' => $settings
			);
			
			$e = explode(".", $display_item['bundle_name'])[1];
			if ($t_instance->hasElement($e) && (ca_metadata_elements::getElementDatatype($e) === __CA_ATTRIBUTE_VALUE_MEDIA__) && isset($settings['appendMultiPagePDFToPDFOutput']) && (bool)$settings['appendMultiPagePDFToPDFOutput']) {
				$media = $t_instance->get($display_item['bundle_name'].'.path', ['returnAsArray' => true, 'version' => 'original']);;
				$mimetypes = $t_instance->get($display_item['bundle_name'].'.original.mimetype', ['returnAsArray' => true]);
				foreach($mimetypes as $i => $mimetype) {
					if ($mimetype !== 'application/pdf') { continue; }
					$media_to_append[] = $media[$i];
				}
			  
			}
		}
		$view->setVar('placements', $display_list);

		$request->user->setVar("{$table}_summary_display_id", $display_id);
	} else {
		$display_id = null;
		$view->setVar('display_id', null);
		$view->setVar('placements', []);
	}

	//
	// PDF output
	//
	if ($template && (preg_match("!^_([A-Za-z0-9]+)_(.*)$!", $template, $m)) && (in_array($m[1], ['pdf', 'docx'])) && is_array($template_info = caGetPrintTemplateDetails('summary', $m[2]))) {
		$last_settings['template'] = $template;
	} else {		
		// When no display is specified (or valid) and no template is specified try loading the default summary format for the table
		if(!$display_id || !$t_display || !is_array($template_info = caGetPrintTemplateDetails('summary', "{$table}_".$t_display->get('display_code')."_summary"))) {
			if(!is_array($template_info = caGetPrintTemplateDetails('summary', "{$table}_summary"))) {
				if(!is_array($template_info = caGetPrintTemplateDetails('summary', "summary"))) {
					//$this->postError(3110, _t("Could not find view for PDF"),"BaseEditorController->PrintSummary()");
					return false;
				}
			}
		}
	}
	
	$include_header_footer = caGetOption('includeHeaderFooter', $template_info, false);
	
	// Pass download-time option settings to template
	$values = caGetPrintTemplateParameters('summary', $m[2], ['view' => $view, 'request' => $request]);
	Session::setVar("print_summary_options_{$m[2]}", $values);

	$barcode_files_to_delete = array();

	try {
		$view->setVar('base_path', $base_path = pathinfo($template_info['path'], PATHINFO_DIRNAME));
		$view->addViewPath(array($base_path, "{$base_path}/local"));

		$barcode_files_to_delete += caDoPrintViewTagSubstitution($view, $t_instance, $template_info['path'], array('checkAccess' => $access_values));

		switch($template_info['fileFormat']) {
			case 'pdf':
				$o_pdf = new PDFRenderer();

				$view->setVar('PDFRenderer', $o_pdf->getCurrentRendererCode());

				$page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $template_info, 'letter'), 'mm', caGetOption('pageOrientation', $template_info, 'portrait'));
				$page_width = $page_size['width']; $page_height = $page_size['height'];
				$view->setVar('pageWidth', "{$page_width}mm");
				$view->setVar('pageHeight', "{$page_height}mm");
				$view->setVar('marginTop', caGetOption('marginTop', $template_info, '0mm'));
				$view->setVar('marginRight', caGetOption('marginRight', $template_info, '0mm'));
				$view->setVar('marginBottom', caGetOption('marginBottom', $template_info, '0mm'));
				$view->setVar('marginLeft', caGetOption('marginLeft', $template_info, '0mm'));

				
				$content = '';
				if($include_header_footer) {
					$content .= $view->render("{$base_path}/pdfStart.php").$view->render("{$base_path}/header.php");
				}	
				$content .= $view->render($template_info['path']);

				if($include_header_footer) {
					$content .= $view->render("{$base_path}/footer.php").$view->render("{$base_path}/pdfEnd.php");
				}
				
				// Printable views can pass back PDFs to append if they want...
				if(is_array($media_set_in_view_to_append = $view->getVar('append'))) {
					$media_to_append = array_merge($media_to_append, $media_set_in_view_to_append);
				}

				$o_pdf->setPage(caGetOption('pageSize', $template_info, 'letter'), caGetOption('pageOrientation', $template_info, 'portrait'), caGetOption('marginTop', $template_info, '0mm'), caGetOption('marginRight', $template_info, '0mm'), caGetOption('marginBottom', $template_info, '0mm'), caGetOption('marginLeft', $template_info, '0mm'));
		
				if (!$filename_template = $config->get("{$table}_summary_file_naming")) {
					$filename_template = $view->getVar('filename') ? $filename_template : caGetOption('filename', $template_info, 'print_summary');
				}
				if (!($filename = caProcessTemplateForIDs($filename_template, $table, [$subject_id]))) {
					$filename = 'print_summary';
				}
				
				if($output === 'STREAM') { 
					$request->isDownload(true);
					$o_pdf->render($content, ['stream'=> true, 'append' => $media_to_append, 'filename' => "{$filename}.pdf"]);
				} else {
					$tmp_filename = caGetTempFileName('caExportSummary', '');
			
					file_put_contents($tmp_filename, $o_pdf->render($content, ['stream'=> false, 'append' => $media_to_append]));
		
					return [
						'mimetype' => 'application/pdf', 
						'path' => $tmp_filename,
						'extension' => 'pdf'
					];
				}
				$printed_properly = true;
				break;
			case 'docx':
				$request->isDownload(true);
				$content = $view->render($template_info['path']);
				if($output === 'STREAM') { 
					print $content;
				} else {
					$tmp_filename = caGetTempFileName('caExportSummary', '');
					file_put_contents($tmp_filename, $content);
					return [
						'mimetype' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
						'path' => $tmp_filename,
						'extension' => 'docx'
					];
				}
				break;
			default:
				throw new Exception(_t('Unsupported format: %1', $template_info['fileFormat']));
				break;
		}
		
		Session::setVar("{$table}_summary_last_settings", $last_settings);

		foreach($barcode_files_to_delete as $tmp) { @unlink($tmp);}
		return true;
	} catch (Exception $e) {
		die($e->getMessage());
		foreach($barcode_files_to_delete as $tmp) { @unlink($tmp);}
		$printed_properly = false;
		return false;
	}
}
# ----------------------------------------
