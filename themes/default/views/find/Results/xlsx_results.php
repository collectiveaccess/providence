<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/ca_objects_xlsx_results.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2014 Whirl-i-Gig
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
 
	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vo_ar					= $this->getVar('access_restrictions');

	$vn_ratio_pixels_to_excel_height = 0.95 ;
	$vn_ratio_pixels_to_excel_width = 0.135 ;

	$va_a_to_z = range('A', 'Z');
	
	$workbook = new PHPExcel;

	// more accurate (but slower) automatic cell size calculation
	PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);

	$sheet = $workbook->getActiveSheet();
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
	
	$sheet->getDefaultStyle()->applyFromArray($cellstyle);
	$sheet->setTitle("CollectiveAccess");
	
	$line = 1;

	$vs_column = reset($va_a_to_z);
	
	
	// Column headers
	$sheet->getRowDimension($line)->setRowHeight(30);
	foreach($va_display_list as $vn_placement_id => $va_display_item) {
		if($vs_column) {
			$sheet->setCellValue($vs_column.$line,$va_display_item['display']);
			$sheet->getStyle($vs_column.$line)->applyFromArray($columntitlestyle);
			$vs_column = next($va_a_to_z);
		}
	}

	
	$line = 2 ;

	// Other lines
	while($vo_result->nextHit()) {
		$column = reset($va_a_to_z);

		foreach($va_display_list as $vn_placement_id => $va_display_item) {

			if (strpos($va_display_item['bundle_name'], 'ca_object_representations.media') !== false) {
				$media = str_replace("ca_object_representations.media.", "", $va_display_item['bundle_name']);
				$vs_display_text = $vo_result->getMediaPath('ca_object_representations.media',$media);
				
				if (is_file($vs_display_text)) {
					$image = "image".$column.$line;
					$drawing = new PHPExcel_Worksheet_Drawing();
					$drawing->setName($image);
					$drawing->setDescription($image);
					$drawing->setPath($vs_display_text);
					$drawing->setCoordinates($column.$line);
					$drawing->setWorksheet($sheet);
				}

			} elseif ($vs_display_text = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request))) {
				$sheet->setCellValue($column.$line,$vs_display_text);
				// We trust the autosizing up to a certain point, but
				// since Arial is not fixed-with and font rendering
				// is different from system to system, this can get a
				// little dicey. The values come from experimentation.
				if ($sheet->getColumnDimension($column)->getWidth() == -1 ) {  // don't overwrite existing settings
					if(strlen($vs_display_text)>55) {
						$sheet->getColumnDimension($column)->setWidth(50);
					}
				}
			}

			$column = next($va_a_to_z);
		}

		// automatic row height. works pretty well in Excel but not so much in LibreOffice/OOo :-(
		$sheet->getRowDimension($line)->setRowHeight(-1);
		$line ++;
	}

	// set column width to auto for all columns where we haven't set width manually yet
	foreach(range('A','Z') as $vs_chr) {
		if ($sheet->getColumnDimension($vs_chr)->getWidth() == -1) {
			$sheet->getColumnDimension($vs_chr)->setAutoSize(true);	
		}
	}
	
 	$o_writer = new PHPExcel_Writer_Excel2007($workbook);

 	header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 	header('Content-Disposition:inline;filename=Export.xlsx ');
 	$o_writer->save('php://output');

?>
