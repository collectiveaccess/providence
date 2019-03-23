<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/xlsx_results.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2018 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 
	$config = Configuration::load();
	
 	$precision = ini_get('precision');
	ini_set('precision', 12);
	
	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');

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
		
	// Add default reps
	if (
		($version = $config->get($vo_result->tableName()."_always_include_primary_representation_media_in_xlsx_output")) 
		&& 
		!sizeof(array_filter($va_display_list, function($v) { return preg_match('!^ca_object_representations.media!', $v['bundle']); }))
	) {
		array_unshift($va_display_list, [
			'display' => _t('Media'),
			'bundle' => "ca_object_representations.media.{$version}",
			'bundle_name' => "ca_object_representations.media.{$version}",
		]);
	}
	
	// Column headers
	$o_sheet->getRowDimension($vn_line)->setRowHeight(30);
	foreach($va_display_list as $vn_placement_id => $va_info) {
		if($vs_column) {
			$o_sheet->setCellValue($vs_supercol.$vs_column.$vn_line,$va_info['display']);
			$o_sheet->getStyle($vs_supercol.$vs_column.$vn_line)->applyFromArray($columntitlestyle);
			if (!($vs_column = next($va_a_to_z))) {
				$vs_supercol = array_shift($va_supercol_a_to_z);
				$vs_column = reset($va_a_to_z);
			}
		}
	}
	
	$vn_line = 2 ;

	// Other lines
	while($vo_result->nextHit()) {
		if(!is_array($va_media_versions = $vo_result->getMediaVersions('ca_object_representations.media'))) { $va_media_versions = []; }
		
		$vs_column = reset($va_a_to_z);
		
		$va_supercol_a_to_z = range('A', 'Z');
		$vs_supercol = '';

		// default to automatic row height. works pretty well in Excel but not so much in LibreOffice/OOo :-(
		$o_sheet->getRowDimension($vn_line)->setRowHeight(-1);

		foreach($va_display_list as $va_info) {
		    $vn_placement_id = $va_info['placement_id'];
			if (
				(strpos($va_info['bundle_name'], 'ca_object_representations.media') !== false)
				&&
				(!isset($va_info['settings']['display_mode']) || ($va_info['settings']['display_mode'] !== 'url'))
			) {
				$va_bits = explode(".", $va_info['bundle_name']);
				$vs_version = array_pop($va_bits);
				
				if (!in_array($vs_version, $va_media_versions)) { $vs_version = $va_media_versions[0]; }
	
				$va_info = $vo_result->getMediaInfo('ca_object_representations.media',$vs_version);
				
				if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
				
					if (is_file($vs_path = $vo_result->getMediaPath('ca_object_representations.media',$vs_version))) {
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
			} elseif ($vs_display_text = $t_display->getDisplayValue($vo_result, $vn_placement_id, array_merge(array('request' => $this->request, 'purify' => true), is_array($va_info['settings']) ? $va_info['settings'] : array()))) {
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

 	@header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 	@header('Content-Disposition:inline;filename=Export.xlsx ');
 	$o_writer->save('php://output');

    ini_set('precision', $precision);
