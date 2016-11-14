<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/DataImportParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
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

	require_once(__CA_LIB_DIR__.'/core/Parsers/IDataParser.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel/IOFactory.php');
	
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
		 * @mixed Parsed file input. For text files, a PHP file resource; for Excel files a PHPExcel row iterator instance.
		 */ 
		private $opr_file;
		
		/**
		 * @mixed PHPExcel instance.
		 */ 
		private $opo_excel;
		
		/**
		 * @string Type of file, "xlsx" for Excel, "txt" for text file
		 */ 
		private $ops_type;
		
		/**
		 * @array Array of current row values
		 */ 
		private $opa_current_row;
		
		/**
		 * @int Index of current row 
		 */ 
		private $opn_current_row;
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
			return new DelimitedDataParser(caGetOption('delimiter', $pa_options, "\t"), caGetOption('textMarker', $pa_options, '"'), $ps_filepath, $pa_options);
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
		 * @param $ps_filepath Path to file
		 * @param $pa_options array Options include:
		 *		worksheet = Worksheet number to read from when parsing Excel files. [Default is 0]
		 * @return bool True on success, false on failure
		 */
		public function parse($ps_filepath, $pa_options=null) {
			$this->opn_current_row = 0;
			try {
				$vb_valid = false;
				$va_excel_types = ['Excel2007', 'Excel5', 'Excel2003XML'];
				foreach ($va_excel_types as $vs_type) {
					$o_reader = PHPExcel_IOFactory::createReader($vs_type);
					if ($o_reader->canRead($ps_filepath)) {
						$vb_valid = true;
						break;
					}
				}
				
				if(!$vb_valid) { throw new Exception("Not an Excel file"); }
				$this->opo_excel = PHPExcel_IOFactory::load($ps_filepath);
				$this->opo_excel->setActiveSheetIndex(caGetOption('worksheet', $pa_options, 0));
				
				$o_sheet = $this->opo_excel->getActiveSheet();
				$this->opr_file = $o_sheet->getRowIterator();
				
				$this->ops_type = 'xlsx';
			} catch (Exception $e) {
				$this->ops_type = 'txt';
				if ($this->opr_file) { fclose($this->opr_file); }
				if (!($this->opr_file = fopen($ps_filepath, "r"))) { return false; }
			}
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
		
					$va_row = array();
					$vb_val_was_set = false;
					$vn_col = 0;
					$vn_last_col_set = null;
					foreach ($o_cells as $o_cell) {
						if (PHPExcel_Shared_Date::isDateTime($o_cell)) {
							if (!($vs_val = caGetLocalizedDate(PHPExcel_Shared_Date::ExcelToPHP(trim((string)$o_cell->getValue()))))) {
								if (!($vs_val = trim(PHPExcel_Style_NumberFormat::toFormattedString((string)$o_cell->getValue(),'YYYY-MM-DD')))) {
									$vs_val = trim((string)$o_cell->getValue());
								}
							}
							$this->opa_current_row[] = $vs_val;
						} else {
							$this->opa_current_row[] = $vs_val = trim((string)$o_cell->getValue());
						}
						if (strlen($vs_val) > 0) { $vb_val_was_set = true; $vn_last_col_set = $vn_col;}
			
						$vn_col++;
			
						if ($vn_col > 255) { break; }	// max 255 columns; some Excel files have *thousands* of "phantom" columns
					}
					return $this->opa_current_row;
				}
				//}
			} else {
				//
				// Parse text
				//
				$vn_state = 0;
				$vb_in_quote = false;
				
				$this->opn_current_row++;
				while(!feof($this->opr_file)) {
					$vs_line = '';
				
					while(false !== ($lc = fgetc($this->opr_file))) {
						if (($lc == "\n") || ($lc == "\r")) {
							break;
						}
						$vs_line .= $lc;
					}
				
					// skip blank lines (with or without tabs)
					if (!$vb_in_quote) {
						if (str_replace("\t", '', $vs_line) == '') {
							continue;
						}
					}
				
					$vn_l = mb_strlen($vs_line);
					for($vn_i=0; $vn_i < $vn_l; $vn_i++) {
						if (sizeof($this->opa_current_row) > 255) { break; }
						$c = mb_substr($vs_line, $vn_i, 1);
					
						switch($vn_state) {
							# -----------------------------------
							case 0:		// start of field
							
								$vn_state = 10;
								if ($c == $this->ops_text_marker) {
									$vb_in_quote = true;
									$vs_fld_text = '';
								} else {
									if ($c == $this->ops_delimiter) {
										// empty fields
										$this->opa_current_row[] = $vs_fld_text;
										$vs_fld_text = '';
										$vn_state = 0;
									} else {
										$vs_fld_text = $c;
									}
								}
								break;
							# -----------------------------------
							case 10:	// in field
								if ($vb_in_quote) {
									if ($c == $this->ops_text_marker) {
										if (mb_substr($vs_line, $vn_i + 1, 1) != '"') {
											// is *not* double quote so leave quoted-ness
											$vb_in_quote = false;
										} else {
											// *is* double quote so treat as single quote in text
											$vs_fld_text .= $c;		// add quote 
											$vn_i++; 				// skip next quote
										}
										break;
									} else {
										$vs_fld_text .= $c;
										break;
									}
								}
							
								if ($c == $this->ops_delimiter) {
									$vn_state = 20;
									// fall through
								} else {
									$vs_fld_text .= $c;
									break;
								}
							# -----------------------------------
							case 20:	// end of field
								$this->opa_current_row[] = $vs_fld_text;
								$vs_fld_text = '';
								$vn_state = 0;
								break;
							# -----------------------------------
						}
					}
				
					if ($vb_in_quote) {
						// add return
						$vs_fld_text .= "\n";
					} else {
						// output last field if not already output
						if (strlen($vs_fld_text) > 0) {
							$this->opa_current_row[] = $vs_fld_text;
						}
					
						return $this->opa_current_row;
					}
				}
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
	}