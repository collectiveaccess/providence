<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/ca_objects_xlsx_results.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2012 Whirl-i-Gig
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

$vn_item_count = 0;
$vb_media_column_present = false;

$workbook = new PHPExcel;

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

$vc_column = "A";
$line = 1;

// First time browse
foreach($va_display_list as $vn_placement_id => $va_display_item) {
	// Detecting if column contains a media so if table contains media columns
	if (strpos($va_display_item['bundle_name'], 'ca_object_representations.media') !== false) {
		$vb_media_column_present = true;
	}
	$sheet->setCellValue($vc_column.$line,$va_display_item['display']);
	$sheet->getStyle($vc_column.$line)->applyFromArray($columntitlestyle);
	$vc_column++;
}
// Adding media column & media bundle name if none, positioned as first one
if (!$vb_media_column_present) {
	$va_display_list = array_merge(
		array( 0 =>
			array(
				/* "placement_id"=>0, */
				"bundle_name" => "ca_object_representations.media.icon"
			)
		),
		$va_display_list
	);
	$sheet->insertNewColumnBefore('A');
	$sheet->setCellValue('A1','Image');
	$sheet->getStyle('A1')->applyFromArray($columntitlestyle);
}

// Column headers
$sheet->getRowDimension($line)->setRowHeight(30);

foreach($va_display_list as $vn_placement_id => $va_display_item) {
}

$line = 2 ;

// Other lines
while($vo_result->nextHit()) {
	$vc_column="A";
	foreach($va_display_list as $vn_placement_id => $va_display_item) {

		if (strpos($va_display_item['bundle_name'], 'ca_object_representations.media') !== false) {
			$media = str_replace("ca_object_representations.media.", "", $va_display_item['bundle_name']);
			$vs_display_text = $vo_result->getMediaPath('ca_object_representations.media',$media);
			if (is_file($vs_display_text)) {
				$image = "image".$vc_column.$line;
				$$image = new PHPExcel_Worksheet_Drawing();
				$$image->setName($image);
				$$image->setDescription($image);
				$$image->setPath($vs_display_text);
				$$image->setCoordinates($vc_column.$line);
				$$image->setWorksheet($sheet);
				$$image->setOffsetX(10);
				$$image->setOffsetY(10);


				if ($line == 2) {
					// Using first result line to extract rows height from max media version height,
					// and to define each column width from media version width
					$va_media_dimensions = caGetSizeForMediaIcon($media);
					if (($va_media_dimensions["height"] > $vn_lines_height ))
						$vn_lines_height = $va_media_dimensions["height"];

					$sheet->getColumnDimension($vc_column)->setWidth($va_media_dimensions["width"] * $vn_ratio_pixels_to_excel_width);
				}
			}

		} elseif ($vs_display_text = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request))) {
			$sheet->setCellValue($vc_column.$line,$vs_display_text);
		}
		$vc_column++;
	}
	$vn_item_count++;
	$line ++;
}

for($i = "A"; $i !== "Z"; $i++) {
	if ($sheet->getColumnDimension($i)->getWidth() == -1 ) $sheet->getColumnDimension($i)->setWidth(28);
}

for($j = 2; $j < $line; $j++) {
	$sheet->getRowDimension($j)->setRowHeight($vn_lines_height * $vn_ratio_pixels_to_excel_height );
}
$writer = new PHPExcel_Writer_Excel2007($workbook);

header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:inline;filename=Export.xlsx ');
$writer->save('php://output');