<?php
/** ---------------------------------------------------------------------
 * app/lib/Parsers/DataImportParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2020 Whirl-i-Gig
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
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
	require_once(__CA_LIB_DIR__.'/Parsers/IDataParser.php');
	
	/**
	 * Utility class for parsing data in delimited text files (eg. tab-delimited or CSV files)
	 * and in Excel-format files. Both formats are handled similarly.
	 *
	 */
	class DelimitedDataParser implements IDataParser {
		# ----------------------------------------
		/**
		 * @string Delimiter for text files (typically a tab or comma)
		 */ 
		private $ops_delimiter;
		
		/**
		 * @string Text to use to enclose text blocks (typically a double quote ")
		 */ 
		private $ops_text_marker = '"';
		
		/**
		 * @mixed Parsed file input. For text files, a PHP file resource; for Excel files a \PhpOffice\PhpSpreadsheet\Spreadsheet row iterator instance.
		 */ 
		private $opr_file;
		
		/**
		 * @string Path to the parsed file
		 */
		private $filepath;
		
		/**
		 * @mixed \PhpOffice\PhpSpreadsheet\Spreadsheet instance.
		 */ 
		private $opo_excel;
		
		/**
		 * @string Type of file, "xlsx" for Excel, "txt" for text file
		 */ 
		private $ops_type;
		
		/**
		 * @array Array of current row values. Excel row data is read onto a 0-based array.
		 */ 
		private $opa_current_row;
		
		/**
		 * @int Index of current row 
		 */ 
		private $opn_current_row;

		/**
		 * @int Max number of columns to read on a file
		 */
		private $opn_max_columns;


		# ----------------------------------------
		/**
		 * @param string $ps_delimiter Delimiter for text files. [Default is tab (\t)]
		 * @param string $ps_text_marker Marker used to enclose text blocks in text files. [Default is double quote (")]
		 * @param string $ps_filepath Path to parsable file. [Default is null]
		 * @param array $pa_options Options include:
		 *		worksheet = Worksheet number to read from when parsing Excel files. [Default is 0]
		 */
		public function __construct($ps_delimiter="\t", $ps_text_marker='"', $ps_filepath=null, $pa_options=null) {
			$this->setDelimiter($ps_delimiter);
			$this->setTextMarker($ps_text_marker);
			$this->opr_file = null;
			
			if ($ps_filepath) { $this->parse($ps_filepath, $pa_options); }

			$config                = Configuration::load();
			$this->opn_max_columns = $config->get('ca_max_columns_delimited_files')?: 512;
		}
		# ----------------------------------------
		/**
		 * Parse a delimited data file (text or XLSX) and return parser instance
		 *
		 * @param string $ps_filepath Path to parsable file. [Default is null]
		 * @param array $pa_options Options include:
		 *		delimiter = Delimiter for text files. [Default is tab (\t)]
		 *		textMarker = Marker used to enclose text blocks in text files. [Default is double quote (")]
		 *		worksheet = Worksheet number to read from when parsing Excel files. [Default is 0]
		 * @return DelimitedDataParser
		 */
		static public function load($ps_filepath, $pa_options=null) {
			$default_delimiter = preg_match("!.csv$!i", $ps_filepath) ? "," : "\t";
			return new DelimitedDataParser(caGetOption('delimiter', $pa_options, $default_delimiter), caGetOption('textMarker', $pa_options, '"'), $ps_filepath, $pa_options);
		}
		# ----------------------------------------
		/**
		 *
		 */
		public function __destruct() {
			if ($this->opr_file && ($this->ops_type == 'txt')) { fclose($this->opr_file); }
		}
		# ----------------------------------------
		/**
		 * Parse file. Text files and Excel files are supported.
		 *
		 * @param $ps_filepath string Path to file
		 * @param $pa_options array Options include:
		 *		worksheet = Worksheet number to read from when parsing Excel files. [Default is 0]
		 * @return bool True on success, false on failure
		 */
		public function parse($ps_filepath, $pa_options=null) {
			$this->opn_current_row = 0;
			try {
				$vb_valid = false;
				$va_excel_types = ['Xlsx', 'xls', 'Xml'];
				foreach ($va_excel_types as $vs_type) {
					$o_reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($vs_type);
					if ($o_reader->canRead($ps_filepath)) {
						$vb_valid = true;
						break;
					}
				}
				
				if(!$vb_valid) { throw new Exception("Not an Excel file"); }
				$this->opo_excel = \PhpOffice\PhpSpreadsheet\IOFactory::load($ps_filepath);
				$this->opo_excel->setActiveSheetIndex(caGetOption('worksheet', $pa_options, 0));
				
				$o_sheet = $this->opo_excel->getActiveSheet();
				$this->opr_file = $o_sheet->getRowIterator();
				
				$this->ops_type = 'xlsx';
			} catch (Exception $e) {
				$this->ops_type = 'txt';
				if ($this->opr_file) { fclose($this->opr_file); }
				$line_ending_setting = ini_get("auto_detect_line_endings");
				ini_set("auto_detect_line_endings", true);
				if (!($this->opr_file = fopen($ps_filepath, "r"))) { return false; }
				ini_set("auto_detect_line_endings", $line_ending_setting);
			}
			$this->filepath = $ps_filepath;
			return true;
		}
		# ----------------------------------------
		/**
		 * Get next row from file
		 *
		 * @return bool Returns true if next row can be returned, false if at end of file
		 */
		public function nextRow() {
			$this->opa_current_row = array();
			
			if ($this->ops_type == 'xlsx') {
				//
				// Parse Excel
				//
				
				if ($this->opn_current_row > 0) {
					$this->opr_file->next();
				}
	
				$this->opn_current_row++;
				if (!$this->opr_file->valid()) {return false; }
	
				if($o_row = $this->opr_file->current()) {
					$this->opa_current_row = array();
	
					$o_cells = $o_row->getCellIterator();
					$o_cells->setIterateOnlyExistingCells(false); 
		
					$vn_col = 1;
					$vn_last_col_set = null;
					foreach ($o_cells as $o_cell) {
						if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($o_cell)) {
							if (!($vs_val = caGetLocalizedDate(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(trim((string)$o_cell->getValue()))))) {
								if (!($vs_val = trim(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString((string)$o_cell->getValue(),'YYYY-MM-DD')))) {
									$vs_val = trim((string)$o_cell->getValue());
								}
							}
							$this->opa_current_row[] = $vs_val;
						} else {
							$this->opa_current_row[] = $vs_val = trim((string)$o_cell->getCalculatedValue());
						}
						if (strlen($vs_val) > 0) { $vb_val_was_set = true; $vn_last_col_set = $vn_col;}
			
						$vn_col++;
						// max columns; some Excel files have *thousands* of "phantom" columns
						if ($vn_col > $this->opn_max_columns) { break; }

					}
					return $this->opa_current_row;
				}
				//}
			} else {
				//
				// Parse text
				//
				$this->opn_current_row++;
				// Use fgetcsv to read file, it will handle delimiter, marker and escaping.
				$line = fgetcsv($this->opr_file, 0, $this->getDelimiter(), $this->getTextMarker());
				if (!is_array($line)) { return false; }
				$this->opa_current_row = array_slice($line, 0, $this->opn_max_columns);
				
				return $this->opa_current_row;
			}
			return false;
		}
		# ----------------------------------------
		/**
		 * Return value for specified column. Columns are numbered starting at one.
		 *
		 * @param int $pn_col
		 * @return string 
		 */
		public function getRowValue($pn_col) {
			$pn_col = (int)$pn_col;
			if ($pn_col > 0) { $pn_col--; }
			return trim($this->opa_current_row[$pn_col]);
		}
		# ----------------------------------------
		/**
		 * Return current row as an array of values
		 * Array indices are zero-based
		 *
		 * @return array
		 */
		public function getRow() {
			return $this->opa_current_row;
		}
		
		# ----------------------------------------
		/**
		 * Count number of data rows in the file
		 *
		 * @return int
		 */
		public function numRows() {
			if ($this->ops_type === 'xlsx') {
				return $this->opo_excel->getActiveSheet()->getHighestRow();
			} else {
				$line_ending_setting = ini_get("auto_detect_line_endings");
				ini_set("auto_detect_line_endings", true);
				
				$r = fopen($this->filepath, "r");
				$count = 0;
				while($line = fgetcsv($r, 0, $this->getDelimiter(), $this->getTextMarker())) {
					$count++;
				}
				fclose($r);
				ini_set("auto_detect_line_endings", $line_ending_setting);
				return $count;
			}
		}
		# ----------------------------------------
		# Utilities
		# ----------------------------------------
		/**
		 * Set current text delimiter
		 *
		 * @param string $ps_delimiter
		 * @return void
		 */
		public function setDelimiter($ps_delimiter) {
			$this->ops_delimiter = substr($ps_delimiter,0,1);
		}
		# ----------------------------------------
		/**
		 * Return current text delimiter
		 *
		 * return @string
		 */
		public function getDelimiter() {
			return $this->ops_delimiter;
		}
		# ----------------------------------------
		/**
		 * Set current text marker
		 *
		 * @param string $ps_marker
		 * @return void
		 */
		public function setTextMarker($ps_marker) {
			$this->ops_text_marker = substr($ps_marker,0,1);
		}
		# ----------------------------------------
		/**
		 * Return current text marker
		 *
		 * @return string
		 */
		public function getTextMarker() {
			return $this->ops_text_marker;
		}
		# ----------------------------------------

		/**
		 * @return mixed
		 */
		public function getType() : string {
			return $this->ops_type;
		}

		/**
		 * @param mixed $ops_type
		 */
		public function setType( $type ): void {
			$this->ops_type = $type;
		}
	}
