<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/docx_results.php
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
	);
	$section = $phpWord->addSection($sectionStyle);


    // Add header for all pages
    $header = $section->addHeader();
    //$header->addText("Header");
    
    $headerimage = $this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img');
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
$styleFirstRow = array('borderBottomSize'=>18, 'borderBottomColor'=>'0000FF');

// Define cell style arrays
$styleCell = array('valign'=>'center');
$styleCellBTLR = array('valign'=>'center');

// Define font style for first row
$fontStyle = array('bold'=>true, 'align'=>'center');

// Add table style
$phpWord->addTableStyle('myOwnTableStyle', $styleTable, $styleFirstRow);


	while($vo_result->nextHit()) {
		$table = $section->addTable('myOwnTableStyle');
		$table->addRow();
    	$list = $va_display_list;

		// First column : media
		$mediaCell = $table->addCell( 5 * $cmToTwips);

		$va_info = $vo_result->getMediaInfo('ca_object_representations.media',"medium");
		
		if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
			$vs_path = $vo_result->getMediaPath('ca_object_representations.media',"medium");
			if (is_file($vs_path)) {
				$mediaCell->addImage(
					$vs_path,
					array(
						'width' => 195,
						'wrappingStyle' => 'inline'
					)
				);
			}
		}


		// Second column : bundles
		$contentCell = $table->addCell(12 * $cmToTwips);

		$contentCell->addText(
			html_entity_decode(strip_tags(br2nl($vo_result->get('preferred_labels'))), ENT_QUOTES | ENT_HTML5),
			$styleHeaderFont
		);

		foreach($list as $vn_placement_id => $va_display_item) {

			if (
				(strpos($va_display_item['bundle_name'], 'ca_object_representations.media') !== false)
				&&
				($va_display_item['settings']['display_mode'] == 'media') // make sure that for the 'url' mode we don't insert the image here
			) {
				// Inserting bundle name on one line
				$contentCell->addText($va_display_item['display'].' :', $styleBundleNameFont);

				// Fetching version asked & corresponding file
				$vs_version = str_replace("ca_object_representations.media.", "", $va_display_item['bundle_name']);
				$va_info = $vo_result->getMediaInfo('ca_object_representations.media',$vs_version);
				
				// If it's a JPEG, print it (basic filter to avoid non handled media version)
				if($va_info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
					$vs_path = $vo_result->getMediaPath('ca_object_representations.media',$vs_version);
					if (is_file($vs_path)) {
						$contentCell->addImage(
    						$vs_path
						);
					}
				}

			} elseif ($vs_display_text = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request))) {


                $textrun = $contentCell->createTextRun();
				$textrun->addText($va_display_item['display'].' :', $styleBundleNameFont);
				$textrun->addText(
					html_entity_decode(strip_tags(br2nl($vs_display_text)), ENT_QUOTES | ENT_HTML5),
					$styleContentFont
				);

			}}
		$vn_line++;
		// Two text break
		$section->addTextBreak(2);

	}
	
 	// Finally, write the document:
	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
 	header("Content-Type:application/vnd.openxmlformats-officedocument.wordprocessingml.document");
	header('Content-Disposition:inline;filename=Export.docx ');
 	
 	//$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'RTF');
 	//header("Content-type: application/rtf");
 	//header('Content-Disposition:inline;filename=Export.rtf ');
 	
	//$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'ODText');
	//header('Content-Type: application/vnd.oasis.opendocument.text');
	//header('Content-Disposition:inline;filename=Export.odt ');
 	
 	$objWriter->save('php://output');