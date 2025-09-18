<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/summary/docx_summary.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2023 Whirl-i-Gig
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
 * @name Microsoft Word
 * @type page
 * @pageSize letter
 * @pageOrientation portrait
 * @tables ca_objects
 *
 * @marginTop 0.75in
 * @marginLeft 0.25in
 * @marginBottom 0.5in
 * @marginRight 0.25in
 * @fileFormat docx
 *
 * ----------------------------------------------------------------------
*/
$t_item = $this->getVar('t_subject');

$bundle_displays = $this->getVar('bundle_displays');
$t_display = $this->getVar('t_display');
$display_list = $this->getVar("placements");

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

$headerimage = ($this->request && $this->request->config->get('report_img')) ? $this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img') : '';
if(is_file($headerimage)){
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

// Define cell style arrays
$styleCell = array('valign'=>'center');
$styleCellBTLR = array('valign'=>'center');

// Define font style for first row
$fontStyle = array('bold'=>true, 'align'=>'center');

// Add table style
$phpWord->addTableStyle('myOwnTableStyle', $styleTable);


$table = $section->addTable('myOwnTableStyle');
$table->addRow();
$list = $display_list;

// First column : media
$mediaCell = $table->addCell( 5 * $cmToTwips);

$info = $t_item->getPrimaryRepresentation(["medium"]);

if($info['info']['medium']['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
	$path = $info['paths']['medium']; 
	if (is_file($path)) {
		$mediaCell->addImage(
			$path,
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
	caEscapeForXML(html_entity_decode(strip_tags(br2nl($t_item->get('preferred_labels'))), ENT_QUOTES | ENT_HTML5)),
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
		$info = $t_item->getMediaInfo('ca_object_representations.media',$version);
	
		// If it's a JPEG, print it (basic filter to avoid non handled media version)
		if($info['MIMETYPE'] == 'image/jpeg') { // don't try to insert anything non-jpeg into an Excel file
			$path = $t_item->getMediaPath('ca_object_representations.media',$version);
			if (is_file($path)) {
				$contentCell->addImage(
					$path
				);
			}
		}

	} elseif ($display_text = $t_display->getDisplayValue($t_item, $placement_id, array_merge(array('request' => $this->request, 'purify' => true), is_array($info['settings']) ? $info['settings'] : array()))) {
		
		// check if $display_text contains a music score
		if(strpos($display_text, '<div class="verovio_summary">') !== false) {
			// if it does, we need to replace the div with the actual image
			preg_match('/src="([^"]*)"/i', $display_text, $matches);
			if($matches[1]) {
				// Add the field label
				$textrun = $contentCell->createTextRun();
				if ($this->request->config->get('report_include_labels_in_docx_output')) {
					$textrun->addText(caEscapeForXML($info['display']).': ', $styleBundleNameFont);
				}

				// Add the image
				$contentCell->addImage(
					$matches[1],
					array('width' => 300)
				);
			}
			continue; // skip to the next iteration
		} 

		// Normal text field
		$textrun = $contentCell->createTextRun();
		
		if ($this->request->config->get('report_include_labels_in_docx_output')) {
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


// Finally, write the document:
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
header("Content-Type:application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header('Content-Disposition:inline;filename=Export.docx ');

$objWriter->save('php://output');
