<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLITools.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

 	require_once(__CA_LIB_DIR__.'/Utils/CLIBaseUtils.php');
	require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
 
	class CLITools extends CLIBaseUtils {
		# -------------------------------------------------------
		# Create a profile <list> element from an Excel spreadsheet. 
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function make_list_from_excel($po_opts=null) {
			$vs_filepath = (string)$po_opts->getOption('file');
			if (!$vs_filepath) { 
				CLITools::addError(_t("You must specify a file", $vs_filepath));
				return false;
			}
			if (!file_exists($vs_filepath)) { 
				CLITools::addError(_t("File '%1' does not exist", $vs_filepath));
				return false;
			}
			
			if ($vs_output_path = (string)$po_opts->getOption('out')) {
				if (!is_writeable(pathinfo($vs_output_path, PATHINFO_DIRNAME))) { 
					CLITools::addError(_t("Cannot write to %1", $vs_output_path));
					return false;
				}
			}
			
			$vn_skip = (int)$po_opts->getOption('skip');
			
			$o_handle = PHPExcel_IOFactory::load($vs_filepath);
			$o_sheet = $o_handle->getActiveSheet();
			
			
			$vn_c = 0;
			$vn_last_level = 0;
			$va_list = array();
			$va_stack = array(&$va_list);
			
			foreach ($o_sheet->getRowIterator() as $o_row) {
				$vn_c++;
				if ($vn_skip >= $vn_c) { continue; }
				
				$va_row = array();
				$o_cells = $o_row->getCellIterator();
				$o_cells->setIterateOnlyExistingCells(false); 

				$vn_col = 0;
				foreach ($o_cells as $o_cell) {
					if($vs_val = trim((string)$o_cell->getValue())) {
						if ($vn_col > $vn_last_level) {
							$va_stack[] = &$va_stack[sizeof($va_stack)-1][sizeof($va_stack[sizeof($va_stack)-1]) - 1]['subitems'];
						} elseif ($vn_col < $vn_last_level) {
							while(sizeof($va_stack) && ($va_stack[sizeof($va_stack)-1][0]['level'] > $vn_col)) {
								array_pop($va_stack);
							}
						}
						$va_stack[sizeof($va_stack)-1][] = array('value' => $vs_val, 'subitems' => array(), 'level' => $vn_col);
						$vn_last_level = $vn_col;
						
						$vn_col++;
						break;
					}
			
					$vn_col++;
				}
			}
			
			$vs_locale = 'en_US';
		
			$vs_output = "<list code=\"LIST_CODE_HERE\" hierarchical=\"0\" system=\"0\" vocabulary=\"0\">\n";
			$vs_output .= "\t<labels>
		<label locale=\"{$vs_locale}\">
			<name>ListNameHere</name>
		</label>
	</labels>\n";
			$vs_output .= "<items>\n";
			$vs_output .= CLITools::_makeList($va_list, 1);
			$vs_output .= "</items>\n";
			$vs_output .= "</list>\n";
			
			if ($vs_output_path) {
				file_put_contents($vs_output_path, $vs_output);
				CLITools::addMessage(_t("Wrote output to %1", $vs_output_path));
			} else {
				print $vs_output;
			}
			return true;
		}
		# -------------------------------------------------------
		private static function _makeList($pa_list, $pn_indent=0, $pa_stack=null) {
			if(!is_array($pa_stack)) { $pa_stack = array(); }
			$vs_locale = 'en_US';
			$vn_ident = $pn_indent ? str_repeat("\t", $pn_indent) : '';
			$vs_buf = '';
			foreach($pa_list as $vn_i => $va_item) {
				$vs_label = caEscapeForXML($va_item['value']);
				$vs_label_proc = preg_replace("![^A-Za-z0-9]+!", "_", $vs_label);
				if ($vs_label_prefix = join('_', $pa_stack)) { $vs_label_prefix .= '_'; }
				$vs_buf .= "{$vn_ident}<item idno=\"{$vs_label_prefix}{$vs_label_proc}\" enabled=\"1\" default=\"0\">
{$vn_ident}\t<labels>
{$vn_ident}\t\t<label locale=\"{$vs_locale}\" preferred=\"1\">
{$vn_ident}\t\t\t<name_singular>{$vs_label}</name_singular>
{$vn_ident}\t\t\t<name_plural>{$vs_label}</name_plural>
{$vn_ident}\t\t</label>
{$vn_ident}\t</labels>".
	((is_array($va_item['subitems']) && sizeof($va_item['subitems'])) ? "{$vn_ident}\t<items>\n{$vn_indent}".CLITools::_makeList($va_item['subitems'], $pn_indent + 2, array_merge($pa_stack, array(substr($vs_label_proc, 0, 10))))."{$vn_ident}\t</items>" : '')."
{$vn_ident}</item>\n";
				
			}
			
			return $vs_buf;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function make_list_from_excelParamList() {
			return array(
				"file|f-s" => _t('Excel file to convert to profile list.'),
				"out|o-s" => _t('File to write output to.'),
				"skip|s-s" => _t('Number of rows to skip before reading data.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function make_list_from_excelUtilityClass() {
			return _t('Profile development tools');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function make_list_from_excelHelp() {
			return _t("Create a profile <list> element from an Excel spreadsheet. Your list should have one list item per row, with hierarchical level indicated by indentation. For example, if you want to have a list with A, B, C, D, E and F, with B and C sub-items of A and F a sub-item of E your Excel document should look like this:\n\n\tA\n\t\tB\n\t\tC\n\tD\n\tE\n\t\tF\n\n\tIf your Excel document has column headers you can skip them by specifying the number of rows to skip using the \"skip\" option.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function make_list_from_excelShortHelp() {
			return _t("Create a profile <list> element from an Excel spreadsheet. ");
		}
		# -------------------------------------------------------
		# Get EXIF tags
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function get_exif_tags($po_opts=null) {
			require_once(__CA_LIB_DIR__."/Import/DataReaders/ExifDataReader.php");
			
			$vs_directory_path = (string)$po_opts->getOption('directory');
			if (!$vs_directory_path) { 
				CLITools::addError(_t("You must specify a directory", $vs_directory_path));
				return false;
			}
			if (!file_exists($vs_directory_path)) { 
				CLITools::addError(_t("Directory '%1' does not exist", $vs_directory_path));
				return false;
			}
			if (!is_dir($vs_directory_path)) { 
				CLITools::addError(_t("'%1' is not a directory", $vs_directory_path));
				return false;
			}
			if (!($vs_output_path = (string)$po_opts->getOption('out'))) {
				CLITools::addError(_t("You must specify an output file"));
				return false;
			}
			if (!is_writeable(pathinfo($vs_output_path, PATHINFO_DIRNAME))) { 
				CLITools::addError(_t("Cannot write to %1", $vs_output_path));
				return false;
			}
			
			if (!caExifToolInstalled()) {
				CLITools::addError(_t("ExifTool external application is required but not installed on this server."));
				return false;	
			}
			
			$va_file_list = caGetDirectoryContentsAsList($vs_directory_path);
		
			$o_reader = new ExifDataReader();
			
			$va_tag_list = [];
			$va_sample_data = [];
			foreach($va_file_list as $vs_file_path) {
				if ($o_reader->read($vs_file_path) && $o_reader->nextRow()) {
					$va_tag_groups = $o_reader->getRow();
					
					foreach($va_tag_groups as $vs_group => $va_tags) {
						if(is_array($va_tags)) {
							foreach($va_tags as $vs_tag => $vs_value) {
								$va_tag_list["{$vs_group}/{$vs_tag}"]++;
								$va_sample_data["{$vs_group}/{$vs_tag}"][(string)$vs_value]++;
							}
						} else {
							$va_tag_list[$vs_group]++;
							$va_sample_data[$vs_group][$vs_value]++;
						}
					}
				}
			}
			
			// output tags
			$va_output = ["Tag\tUsed in # files\tTop 3 values"];
			foreach($va_tag_list as $vs_tag => $vn_count) {
				$va_values = $va_sample_data[$vs_tag];
				asort($va_values, SORT_NUMERIC);
				$va_output[] = "\"{$vs_tag}\"\t\"{$vn_count}\"\t\"".join("; ", array_slice(array_keys($va_values), 0, 3))."\"";
			}
			file_put_contents($vs_output_path, join("\n", $va_output));
			CLITools::addMessage(_t("Wrote output to '%1'", $vs_output_path));
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function get_exif_tagsParamList() {
			return array(
				"directory|d-s" => _t('Directory containing images to examine.'),
				"out|o-s" => _t('File to write tab-delimited output to.')
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function get_exif_tagsUtilityClass() {
			return _t('Profile development tools');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function get_exif_tagsHelp() {
			return _t("Analyzes a directory of images and returns a tab-delimited list of import-mapping compatible EXIF and IPTC tags containing data.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function get_exif_tagsShortHelp() {
			return _t("Analyzes a directory of images and returns a tab-delimited  list of import-mapping compatible EXIF and IPTC tags containing data.");
		}
		# -------------------------------------------------------
	}
