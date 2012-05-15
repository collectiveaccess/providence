<?php
/* ----------------------------------------------------------------------
 * excel_xml_to_tab_with_formatting.php : converts Excel XML (2004) format (*NOT 2007*) to tab-delimited data with formatting intact 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 
//
// Converts Excel XML (2004) format (*NOT 2007*) to tab-delimited data 
// with formatting intact. Formatting will be in the form of HTML tags.
//
// Take two parameters on the command line:
//
//		(1) Path to XML file to extract data from
//		(2) Number of worksheet to extract data from. Worksheets are numbered from zero (eg. the first sheet is 0)
//		(3) The file to write extracted data to
//
	$xml = simplexml_load_file($argv[1], null, LIBXML_NOBLANKS);

	$pn_worksheet_index = $argv[2];
	if (!is_numeric($pn_worksheet_index)) { die("Must specify worksheet index! (starts from zero)\n"); }
	
	$ps_output_file = $argv[3];
	if (!$ps_output_file) { die("Must specify output file!\n"); }
	
	$vn_cur_worksheet_index = 0;
	foreach ($xml->Worksheet as $worksheet) {
	
		if ($vn_cur_worksheet_index == $pn_worksheet_index) {
			$vn_row = 0;
			$va_data = array();
			foreach($worksheet->Table as $table) {
				foreach($table->Row as $row) {
					$vn_cell = 0;
					foreach($row->Cell as $cell) {
						$vs_content = $cell->Data->asXML();
						$vs_content = preg_replace("!^<Data[^>]*>!", "", $vs_content);
						$vs_content = preg_replace("!</Data[^>]*>$!", "", $vs_content);
						$vs_content = preg_replace("![ ]*xmlns=\"http://www.w3.org/TR/REC-html40\"[ ]*!", "", $vs_content);
						$vs_content = preg_replace("!<[/]*font>!i", "", $vs_content);
						$vs_content = str_replace("’", "'", $vs_content);
						$vs_content = str_replace("“", '"', $vs_content);
						$vs_content = str_replace("”", '"', $vs_content);
						
						$va_data[$vn_row][$vn_cell] = $vs_content;
						$vn_cell++;
					}
					$vn_row++;
				}
			}
			
			$r_fp = fopen($ps_output_file, "w");
			foreach($va_data as $va_row) {
				fputs($r_fp, join("\t", $va_row)."\n");
			}
			fclose($r_fp);
			break;
		}
		$vn_cur_worksheet_index++;
	}
?>