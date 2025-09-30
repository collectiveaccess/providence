<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/sets/display.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name XLSX
 * @fileFormat xlsx
 * @type page
 * @pageSize letter
 * @pageOrientation portrait
 * @tables *
 *
 * ----------------------------------------------------------------------
 */
$t_display			= $this->getVar('display');
$display_list 		= $this->getVar('display_list');
$result 			= $this->getVar('result');
$items_per_page 	= $this->getVar('current_items_per_page');
$num_items			= (int)$result->numHits();
$t_set				= $this->getVar("t_set");

$config = Configuration::load();

$precision = ini_get('precision');
ini_set('precision', 12);

$ratio_pixels_to_excel_height = 0.85;
$ratio_pixels_to_excel_width = 0.135;

$va_supercol_a_to_z = range('A', 'Z');
$supercol = '';

$va_a_to_z = range('A', 'Z');

$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

$o_sheet = $workbook->getActiveSheet();
$workbook->getDefaultStyle()->getAlignment()->setWrapText(true);
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

$column = reset($va_a_to_z);
	
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
foreach($display_list as $placement_id => $va_info) {
	if($column) {
		$o_sheet->setCellValue($supercol.$column.$line,$va_info['display']);
		$o_sheet->getStyle($supercol.$column.$line)->applyFromArray($columntitlestyle);
		if (!($column = next($va_a_to_z))) {
			$supercol = array_shift($va_supercol_a_to_z);
			$column = reset($va_a_to_z);
		}
	}
}

$line = 2 ;

// Other lines
while($result->nextHit()) {
	if(!is_array($va_media_versions = $result->getMediaVersions('ca_object_representations.media'))) { $va_media_versions = []; }
	
	$column = reset($va_a_to_z);
	
	$va_supercol_a_to_z = range('A', 'Z');
	$supercol = '';

	// default to automatic row height. works pretty well in Excel but not so much in LibreOffice/OOo :-(
	$o_sheet->getRowDimension($line)->setRowHeight(-1);

	foreach($display_list as $va_info) {
		$placement_id = $va_info['placement_id'];
		
		if (is_array($va_info['settings']) && isset($va_info['settings']['format']) && ($tags = array_filter(caGetTemplateTags($va_info['settings']['format']), function($v) { return preg_match("!^ca_object_representations.media.!", $v); }))) {
			// Transform bundle with template including media into a media bundle as that's the only way to show media within an XLSX
			$va_info['bundle_name'] = $tags[0];
		}
		if (
			(preg_match('!^ca_object_representations.media!', $va_info['bundle_name']))
			&&
			!strlen($va_info['settings']['format'])
			&&
			(!isset($va_info['settings']['display_mode']) || ($va_info['settings']['display_mode'] !== 'url'))
		) {
			$va_bits = explode(".", $va_info['bundle_name']);
			$version = array_pop($va_bits);
			
			if (!in_array($version, $va_media_versions)) { $version = $va_media_versions[0]; }

			$va_info = $result->getMediaInfo('ca_object_representations.media',$version);
			
			if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
			
				if (is_file($path = $result->getMediaPath('ca_object_representations.media',$version))) {
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

				$width = floor(intval($va_info['PROPERTIES']['width']) * $ratio_pixels_to_excel_width);
				$height = floor(intval($va_info['PROPERTIES']['height']) * $ratio_pixels_to_excel_height);

				// set the calculated withs for the current row and column,
				// but make sure we don't make either smaller than they already are
				if($width > $o_sheet->getColumnDimension($supercol.$column)->getWidth()) {
					$o_sheet->getColumnDimension($supercol.$column)->setWidth($width);	
				}
				if($height > $o_sheet->getRowDimension($line)->getRowHeight()){
					$o_sheet->getRowDimension($line)->setRowHeight($height);
				}

			}
		} elseif ($display_text = $t_display->getDisplayValue($result, $placement_id, array_merge(array('request' => $this->request, 'purify' => true), is_array($va_info['settings']) ? $va_info['settings'] : array()))) {
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

		if (!($column = next($va_a_to_z))) {
			$supercol = array_shift($va_supercol_a_to_z);
			$column = reset($va_a_to_z);
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

if($this->request && ($this->request->config->get('excel_report_header_enabled') || $this->request->config->get('excel_report_footer_enabled'))){
	$o_sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
	$o_sheet->getPageMargins()->setTop(1);
	$o_sheet->getPageMargins()->setRight(0.75);
	$o_sheet->getPageMargins()->setLeft(0.75);
	$o_sheet->getPageMargins()->setBottom(1);
	$o_sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,1);
	
	if($this->request && $this->request->config->get('excel_report_header_enabled')){
		if(file_exists($this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img'))){
			$logo_path = $this->request->getThemeDirectoryPath().'/graphics/logos/'.$this->request->config->get('report_img');
		}
		$objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing();
		$objDrawing->setName('Image');
		$objDrawing->setPath($logo_path);
		$objDrawing->setHeight(36);
		$o_sheet->getHeaderFooter()->addImage($objDrawing, \PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter::IMAGE_HEADER_LEFT);
		$criteria_summary = str_replace("&", "+", strip_tags(html_entity_decode($criteria_summary)));
		$criteria_summary = (strlen($criteria_summary) > 90) ? mb_substr($criteria_summary, 0, 90)."..." : $criteria_summary;
		$criteria_summary = wordwrap($criteria_summary, 50, "\n", true);
		$o_sheet->getHeaderFooter()->setOddHeader('&L&G& '.(($this->request->config->get('excel_report_show_search_term')) ? '&R&B&12 '.$criteria_summary : ''));
		
	}
	if(!$this->request || $this->request->config->get('excel_report_footer_enabled')){
		$t_instance = Datamodel::getInstanceByTableName($result->tableName(), true);
		$o_sheet->getHeaderFooter()->setOddFooter('&L&10'.ucfirst($t_instance->getProperty('NAME_SINGULAR').' report').' &C&10Page &P of &N &R&10 '.date("m/t/y"));
	}
}

$o_writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);

@header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
@header('Content-Disposition:inline;filename=Export.xlsx ');
$o_writer->save('php://output');

ini_set('precision', $precision);
