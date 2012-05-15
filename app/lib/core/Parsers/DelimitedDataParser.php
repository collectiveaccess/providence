<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/DataImportParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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
	

	class DelimitedDataParser implements IDataParser {
		# ----------------------------------------
		private $ops_delimiter;
		private $ops_text_marker = '"';
		private $opr_file;
		
		private $opa_current_row;
		# ----------------------------------------
		public function __construct($ps_delimiter="\t", $ps_text_marker='"') {
			$this->setDelimiter($ps_delimiter);
			$this->setTextMarker($ps_text_marker);
			$this->opr_file = null;
		}
		# ----------------------------------------
		public function __destruct() {
			if ($this->opr_file) { fclose($this->opr_file); }
		}
		# ----------------------------------------
		public function parse($ps_filepath) {
			if ($this->opr_file) { fclose($this->opr_file); }
			if (!($this->opr_file = fopen($ps_filepath, "r"))) { return false; }
			return true;
		}
		# ----------------------------------------
		public function nextRow() {
			$vn_state = 0;
			$vb_in_quote = false;
			$this->opa_current_row = array();
			
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
			
			return false;
		}
		# ----------------------------------------
		public function getRowValue($ps_source) {
			$vn_n = intval($ps_source);
			if ($vn_n > 0) { $vn_n--; }
			return trim($this->opa_current_row[$vn_n]);
		}
		# ----------------------------------------
		public function getRow() {
			return $this->opa_current_row;
		}
		# ----------------------------------------
		# Utilities
		# ----------------------------------------
		public function setDelimiter($ps_delimiter) {
			$this->ops_delimiter = substr($ps_delimiter,0,1);
		}
		# ----------------------------------------
		public function getDelimiter() {
			return $this->ops_delimiter;
		}
		# ----------------------------------------
		public function setTextMarker($ps_marker) {
			$this->ops_text_marker = substr($ps_marker,0,1);
		}
		# ----------------------------------------
		public function getTextMarker() {
			return $this->ops_text_marker;
		}
		# ----------------------------------------
	}
?>