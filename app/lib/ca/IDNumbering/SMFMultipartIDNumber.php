<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/IDNumbering/SMFMultipartIDNumber.php : plugin to generate id numbers for Musées de France
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2012 Whirl-i-Gig
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
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 * File created by Gautier Michelin (www.ideesculture.com) for specific Musées de France requirements
 */
 

	require_once(__CA_LIB_DIR__ . "/core/Configuration.php");
	require_once(__CA_LIB_DIR__ . "/core/Datamodel.php");
	require_once(__CA_LIB_DIR__ . "/core/Db.php");
	require_once(__CA_LIB_DIR__ . "/core/ApplicationVars.php");
	require_once(__CA_LIB_DIR__ . "/ca/IDNumbering/IDNumber.php");
	require_once(__CA_LIB_DIR__ . "/ca/IDNumbering/IIDNumbering.php");
	require_once(__CA_APP_DIR__ . "/helpers/navigationHelpers.php");
	
	class SMFMultipartIDNumber extends IDNumber implements IIDNumbering {
		# -------------------------------------------------------
		private $opo_idnumber_config;
		private $opa_formats;
		
		private $opo_db;
		
		# -------------------------------------------------------
		public function __construct($ps_format=null, $pm_type=null, $ps_value=null, $po_db=null) {
			if (!$pm_type) { $pm_type = array('__default__'); }
			
			parent::__construct();
			$this->opo_idnumber_config = Configuration::load($this->opo_config->get('multipart_id_numbering_config'));
			$this->opa_formats = $this->opo_idnumber_config->getAssoc('formats');
			
			if ($ps_format) { $this->setFormat($ps_format); }
			if ($pm_type) { $this->setType($pm_type); }
			if ($ps_value) { $this->setValue($ps_value); }
			
			if ((!$po_db) || !is_object($po_db)) { 
				$this->opo_db = new Db();
			} else {
				$this->opo_db = $po_db;
			}
		}
		# -------------------------------------------------------
		# Formats
		# -------------------------------------------------------
		public function getFormats() {
			return array_keys($this->opa_formats);
		}
		# -------------------------------------------------------
		public function isValidFormat($ps_format) {
			return in_array($ps_format, $this->getFormats());
		}
		# -------------------------------------------------------
		public function getSeparator() {
			if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->opa_formats[$vs_format][$vs_type]['separator'])) {
				return $this->opa_formats[$vs_format][$vs_type]['separator'] ? $this->opa_formats[$vs_format][$vs_type]['separator'] : '';
			}
			return '.';
		}
		# -------------------------------------------------------
		public function getElementOrderForSort() {
			if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->opa_formats[$vs_format][$vs_type]['sort_order'])) {
				return (is_array($this->opa_formats[$vs_format][$vs_type]['sort_order']) && sizeof($this->opa_formats[$vs_format][$vs_type]['sort_order'])) ? $this->opa_formats[$vs_format][$vs_type]['sort_order'] : null;
			}
			return null;
		}
		# -------------------------------------------------------
		public function isSerialFormat($ps_format=null, $ps_type=null) {
			if ($ps_format) {
				if (!$this->isValidFormat($ps_format)) {
					return false;
				}
				$vs_format = $ps_format;
			} else {
				if(!($vs_format = $this->getFormat())) {
					return false;
				}
			}
			if ($ps_type) {
				if (!$this->isValidType($ps_type)) {
					return false;
				}
				$vs_type = $ps_type;
			} else {
				if(!($vs_type = $this->getType())) {
					return false;
				}
			}
			
			$va_elements = $this->opa_formats[$vs_format][$vs_type]['elements'];
			$va_last_element = array_pop($va_elements);
			if ($va_last_element['type'] == 'SERIAL') {
				return true;
			}
			return false;
		}
		# -------------------------------------------------------
		# Returns true if the current format is an extension of $ps_format
		# That is, the current format is the same as the $ps_form with an auto-generated
		# extra element such that the system can auto-generate unique numbers using a $ps_format
		# compatible number as the basis. This is mainly used to determine if the system configuration
		# is such that object numbers can be auto-generated based upon lot numbers.
		public function formatIsExtensionOf($ps_format, $ps_type='__default__') {
			if (!$this->isSerialFormat()) {
				return false;	// If this format doesn't end in a SERIAL element it can't be autogenerated.
			}
			
			if (!$this->isValidFormat($ps_format)) { 
				return false;	// specifed format does not exist
			}
			if (!$this->isValidType($ps_type)) { 
				return false;	// specifed type does not exist
			}
			
			$va_base_elements = $this->opa_formats[$ps_format][$ps_type]['elements'];
			$va_ext_elements = $this->getElements();
			
			if (sizeof($va_ext_elements) != (sizeof($va_base_elements) + 1)) {
				return false;	// extension should have exactly one more element than base
			}
			
			$vn_num_elements = sizeof($va_base_elements);
			for($vn_i=0; $vn_i < $vn_num_elements; $vn_i++) {
				$va_base_element = array_shift($va_base_elements);
				$va_ext_element = array_shift($va_ext_elements);
				
				if ($va_base_element['type'] != $va_ext_element['type']) { return false; }
				if ($va_base_element['width'] > $va_ext_element['width']) { return false; }
				
				switch($va_base_element['type']) {
					case 'LIST':
						if (!is_array($va_base_element['values']) || !is_array($va_ext_element['values'])) { return false; }
						if (sizeof($va_base_element['values']) != sizeof($va_ext_element['values'])) { return false; }
						for($vn_j=0; $vn_j < sizeof($va_base_element['values']); $vn_j++) {
							if ($va_base_element['values'][$vn_j] != $va_ext_element['values'][$vn_j]) { return false; }
						}
						break;
					case 'CONSTANT';
						if ($va_base_element['value'] != $va_ext_element['value']) { return false; }
						break;
					case 'NUMERIC':
						if ($va_base_element['minimum_length'] < $va_ext_element['minimum_length']) { return false; }
						if ($va_base_element['maximum_length'] > $va_ext_element['maximum_length']) { return false; }
						if ($va_base_element['minimum_value'] < $va_ext_element['minimum_value']) { return false; }
						if ($va_base_element['maximum_value'] > $va_ext_element['maximum_value']) { return false; }
						break;
					case 'ALPHANUMERIC':
						if ($va_base_element['minimum_length'] < $va_ext_element['minimum_length']) { return false; }
						if ($va_base_element['maximum_length'] > $va_ext_element['maximum_length']) { return false; }
						break;
					case 'FREE':
						if ($va_base_element['minimum_length'] < $va_ext_element['minimum_length']) { return false; }
						if ($va_base_element['maximum_length'] > $va_ext_element['maximum_length']) { return false; }
						break;
				}
			}
			
			return true;
			
		}
		# -------------------------------------------------------
		# Types
		# -------------------------------------------------------
		public function getTypes() {
			$va_formats = $this->getFormats();
			
			$va_types = array();
			foreach($va_formats as $vs_format) {
				if (is_array($this->opa_formats[$vs_format])) {
					foreach($this->opa_formats[$vs_format] as $vs_type => $va_info) {
						$va_types[$vs_type] = true;
					}
				}
			}
			
			return array_keys($va_types);
		}
		# -------------------------------------------------------
		public function isValidType($ps_type) {
			return ($ps_type) && in_array($ps_type, $this->getTypes());
		}
		# -------------------------------------------------------
		# Elements
		# -------------------------------------------------------
		private function getElements() {
			if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType())) {
				if (is_array($this->opa_formats[$vs_format][$vs_type]['elements'])) {
					$vb_is_child = $this->isChild();
					$va_elements = array();
					foreach($this->opa_formats[$vs_format][$vs_type]['elements'] as $vs_k => $va_element_info) {
						if (!$vb_is_child && isset($va_element_info['child_only']) && (bool)$va_element_info['child_only']) { continue; }
						$va_elements[$vs_k] = $va_element_info;
					}
				}
				return $va_elements;
			}
			return null;
		}
		# -------------------------------------------------------
		private function getElementInfo($ps_element_name) {
			if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType())) {
				return $this->opa_formats[$vs_format][$vs_type]['elements'][$ps_element_name];
			}
			return null;
		}
		# -------------------------------------------------------
		public function validateValue($ps_value) {
			//if (!$ps_value) { return array(); }
			$vs_separator = $this->getSeparator();
			$va_elements = $this->getElements();
			if (!is_array($va_elements)) { return array(); }
			
			if ($vs_separator) {
				$va_element_vals = explode($vs_separator, $ps_value);
			} else {
				$va_element_vals = array($ps_value);
			}
			
			$vn_i = 0;
			
			$va_element_errors = array();
			foreach($va_elements as $vs_element_name => $va_element_info) {
				$vs_value = $va_element_vals[$vn_i];
				$vn_value_len = mb_strlen($vs_value);
				
				switch($va_element_info['type']) {
					case 'LIST':
						if (!in_array($vs_value, $va_element_info['values'])) { 
							$va_element_errors[$vs_element_name] = _t("'%1' is not valid for %2", $vs_value, $va_element_info['description']);
						}
						break;
					case 'SERIAL':
						if ($vs_value) {
							if (!preg_match("/^[A-Za-z0-9]+$/", $vs_value)) { 
								$va_element_errors[$vs_element_name] = _t("'%1' is not valid for %2; only letters and numbers are allowed", $vs_value, $va_element_info['description']);
							}
						}
						break;
					case 'CONSTANT':
						if ($vs_value && ($vs_value != $va_element_info['value'])) { 
							$va_element_errors[$vs_element_name] = _t("%1 must be set to %2", $va_element_info['description'], $va_element_info['value']);
						}
						break;
					case 'FREE':
						# noop
						//if (!$vs_value) {
						//	$va_element_errors[$vs_element_name] = _t("%1 must not be blank", $va_element_info['description']);
						//}
						if (isset($va_element_info['minimum_length']) && ($vn_value_len < $va_element_info['minimum_length'])) {
							if($va_element_info['minimum_length'] == 1) {
								$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 character", $va_element_info['description'], $va_element_info['minimum_length']);
							} else {
								$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 characters", $va_element_info['description'], $va_element_info['minimum_length']);
							}
						}
						if (isset($va_element_info['maximum_length']) && ($vn_value_len > $va_element_info['maximum_length'])) {
							if($va_element_info['minimum_length'] == 1) {
								$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 character", $va_element_info['description'], $va_element_info['maximum_length']);
							} else {
								$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 characters", $va_element_info['description'], $va_element_info['maximum_length']);
							}
						}
						break;
					case 'NUMERIC':
						if (!preg_match("/^[\d]+[a-zA-Z]{0,1}$/", $vs_value)) {
							$va_element_errors[$vs_element_name] = _t("%1 must be a number", $va_element_info['description']);
						}
						if (isset($va_element_info['minimum_value']) && ($vs_value < $va_element_info['minimum_value'])) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be less than %2", $va_element_info['description'], $va_element_info['minimum_value']);
						}
						if (isset($va_element_info['maximum_value']) && ($vs_value > $va_element_info['maximum_value'])) {
							$va_element_errors[$vs_element_name] = _t("%1 must not be more than %2", $va_element_info['description'], $va_element_info['maximum_value']);
						}
						if (isset($va_element_info['minimum_length']) && ($vn_value_len < $va_element_info['minimum_length'])) {
							if ($va_element_info['minimum_length'] == 1) {
								$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 character", $va_element_info['description'], $va_element_info['minimum_length']);
							} else {
								$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 characters", $va_element_info['description'], $va_element_info['minimum_length']);
							}
						}
						if (isset($va_element_info['maximum_length']) && ($vn_value_len > $va_element_info['maximum_length'])) {
							if ($va_element_info['maximum_length'] == 1) {
								$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 character", $va_element_info['description'], $va_element_info['maximum_length']);
							} else {
								$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 characters", $va_element_info['description'], $va_element_info['maximum_length']);
							}
						}
						break;
					case 'ALPHANUMERIC':
						if ($vs_value != '' && !preg_match("/^[A-Za-z0-9]+$/", $vs_value)) {
							$va_element_errors[$vs_element_name] = _t("%1 must consist only letters and numbers", $va_element_info['description']);
						}
						if (isset($va_element_info['minimum_length']) && ($vn_value_len < $va_element_info['minimum_length'])) {
							if ($va_element_info['minimum_length'] == 1) {
								$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 character", $va_element_info['description'], $va_element_info['minimum_length']);
							} else {
								$va_element_errors[$vs_element_name] = _t("%1 must not be shorter than %2 characters", $va_element_info['description'], $va_element_info['minimum_length']);
							}
						}
						if (isset($va_element_info['maximum_length']) && ($vn_value_len > $va_element_info['maximum_length'])) {
							if ($va_element_info['maximum_length'] == 1) {
								$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 character", $va_element_info['description'], $va_element_info['maximum_length']);
							} else {
								$va_element_errors[$vs_element_name] = _t("%1 must not be longer than %2 characters", $va_element_info['description'], $va_element_info['maximum_length']);
							}
						}
						break;
					case 'YEAR':
						$va_tmp = getdate();
						if ($vs_value != '') {
							if ((($vs_value < 1800) || ($vs_value > ($va_tmp['year'] + 10))) || ($vs_value != intval($vs_value))) { 
								$va_element_errors[$vs_element_name] = _t("%1 must be a valid year", $va_element_info['description']);
							}
						}
						break;
					case 'MONTH':
						if ($vs_value != '') {
							if ((($vs_value < 1) || ($vs_value > 12)) || ($vs_value != intval($vs_value))) { 
								$va_element_errors[$vs_element_name] = _t("%1 must be a valid numeric month (between 1 and 12)", $va_element_info['description']);
							}
						}
						break;
					case 'DAY':
						if ($vs_value != '') {
							if ((($vs_value < 1) || ($vs_value > 31)) || ($vs_value != intval($vs_value))) { 
								$va_element_errors[$vs_element_name] = _t("%1 must be a valid numeric day (between 1 and 31)", $va_element_info['description']);
							}
						}
						break;
					default:
						# noop
						break;
						
				}
				$vn_i++;
			}
			return $va_element_errors;
		}
		# -------------------------------------------------------
		public function isValidValue($ps_value=null) {
			return $this->validateValue(!is_null($ps_value) ? $ps_value : $this->getValue());
		}
		# -------------------------------------------------------
		public function getNextValue($ps_element_name,$ps_value=null, $pb_dont_mark_value_as_used=false) {
			
			switch($ps_element_name) {
				case "depot_accession_number":
					$this->opo_db->dieOnError(false);
					
					// Get the next number based upon field data
					$qr_res = $this->opo_db->query("
						SELECT idno FROM ca_objects
						WHERE idno LIKE CONCAT(\"D.\",YEAR(NOW()),\".%\") AND idno regexp \"^D\.[0-9]*\.[0-9]*\.[0-9]*$\"
						ORDER BY 
							idno_sort DESC LIMIT 1
					");
					
					if ($this->opo_db->numErrors()) {
						return "ERR";
					}
						
					// Figure out what the sequence (last) number in the multipart number taken from the field is...
					if ($qr_res->numRows()) {
						$qr_res->nextRow();
						$idno_max = $qr_res->get("idno");
						$idno_parts = explode(".",$idno_max);
						$vn_num =  (int)$idno_parts[2];
					} else {
						$vn_num = 1 ;
					}
					break;				
				case "depot_object_number":
					$this->opo_db->dieOnError(false);
					
					// Get the next number based upon field data
					$qr_res = $this->opo_db->query("
						SELECT idno FROM ca_objects
						WHERE idno LIKE CONCAT(\"D.\",YEAR(NOW()),\".%\") AND idno regexp \"^D\.[0-9]*\.[0-9]*\.[0-9]*$\"
						ORDER BY 
							idno_sort DESC LIMIT 1
					");
					
					if ($this->opo_db->numErrors()) {
						return "ERR";
					}
						
					// Figure out what the sequence (last) number in the multipart number taken from the field is...
					if ($qr_res->numRows()) {
						$qr_res->nextRow();
						$idno_max = $qr_res->get("idno");
						$idno_parts = explode(".",$idno_max);
						$vn_num =  (int)$idno_parts[3] + 1 ;
					} else {
						$vn_num = 1 ;
					}
					break;
				case "accession_number":
					$this->opo_db->dieOnError(false);
					
					// Get the next number based upon field data
					$qr_res = $this->opo_db->query("
						SELECT idno FROM ca_objects
						WHERE idno LIKE CONCAT(YEAR(NOW()),\".%\") AND idno regexp \"^[0-9]*\.[0-9]*\.[0-9]*$\"
						ORDER BY 
							idno_sort DESC LIMIT 1
					");
					
					if ($this->opo_db->numErrors()) {
						return "ERR";
					}
						
					// Figure out what the sequence (last) number in the multipart number taken from the field is...
					if ($qr_res->numRows()) {
						$qr_res->nextRow();
						$idno_max = $qr_res->get("idno");
						$idno_parts = explode(".",$idno_max);
						$vn_num =  (int)$idno_parts[1];
					} else {
						$vn_num = 1 ;
					}
					break;
				case "object_number":
					$this->opo_db->dieOnError(false);
					
					// Get the next number based upon field data
					$qr_res = $this->opo_db->query("
						SELECT idno FROM ca_objects
						WHERE idno LIKE CONCAT(YEAR(NOW()),\".%\") AND idno regexp \"^[0-9]*\.[0-9]*\.[0-9]*$\"
						ORDER BY 
							idno_sort DESC LIMIT 1
					");
					
					if ($this->opo_db->numErrors()) {
						return "ERR";
					}
						
					// Figure out what the sequence (last) number in the multipart number taken from the field is...
					if ($qr_res->numRows()) {
						$qr_res->nextRow();
						$idno_max = $qr_res->get("idno");
						$idno_parts = explode(".",$idno_max);
						$vn_num =  (int)$idno_parts[2] + 1 ;
					} else {
						$vn_num = 1 ;
					}
					break;
				default:
					$vn_num = 1;
					break;
			}

			return $vn_num;
		}
		# -------------------------------------------------------
		/**
		 * Returns sortable value padding according to the format of the specified format and type
		 *
		 * @param string $ps_value
		 * @return string
		 */
		public function getSortableValue($ps_value=null) {
			$vs_separator = $this->getSeparator();
			if (!is_array($va_elements_normal_order = $this->getElements())) { $va_elements_normal_order = array(); }
			$va_element_names_normal_order = array_keys($va_elements_normal_order);
			
			if (!($va_elements = $this->getElementOrderForSort())) { $va_elements = $va_element_names_normal_order; }
			if($vs_separator) {
				$va_element_vals = explode($vs_separator, $ps_value ? $ps_value : $this->getValue());
			} else {
				$va_element_vals = array($ps_value ? $ps_value : $this->getValue());
			}
			$va_output = array();
		
			$vn_i = 0;
			foreach($va_elements as $vn_x => $vs_element) {
				$va_element_info = $va_elements_normal_order[$vs_element];
				$vn_i = array_search($vs_element, $va_element_names_normal_order);
				$vn_padding = 20;
				
				switch($va_element_info['type']) {
					case 'LIST':
						$vn_w = $vn_padding - mb_strlen($va_element_vals[$vn_i]);
						if ($vn_w < 0) { $vn_w = 0; }
						$va_output[] = str_repeat(' ', $vn_w).$va_element_vals[$vn_i];
						break;
					case 'CONSTANT':
						$vn_len = mb_strlen($va_element_info['value']);
						if ($vn_padding < $vn_len) { $vn_padding = $vn_len; }
						$va_output[] = str_repeat(' ', $vn_padding - mb_strlen($va_element_vals[$vn_i])).$va_element_vals[$vn_i];
						break;
					case 'FREE':
					case 'ALPHANUMERIC':
						if ($vn_padding < $va_element_info['width']) { $vn_padding = $va_element_info['width']; }
						//$vn_pad_len = $vn_padding - mb_strlen($va_element_vals[$vn_i]);
						//if ($vn_pad_len < 0) { $vn_pad_len = 0; }
						///$va_output[] = str_repeat(' ', $vn_pad_len).$va_element_vals[$vn_i];
						$va_tmp = preg_split('![^A-Za-z0-9]+!',  $va_element_vals[$vn_i]);
			
						$va_zeroless_output = array();
						$va_raw_output = array();
						while(sizeof($va_tmp)) {
							$vs_piece = array_shift($va_tmp);
							if (preg_match('!^([\d]+)(.*)!', $vs_piece, $va_matches)) {
								$vs_piece = $va_matches[1];
								
								if (sizeof($va_matches) >= 3) {
									array_unshift($va_tmp, $va_matches[2]);
								}
							}
							$vn_pad_len = 12 - mb_strlen($vs_piece);
							
							if ($vn_pad_len >= 0) {
								if (is_numeric($vs_piece)) {
									$va_raw_output[] = str_repeat(' ', $vn_pad_len).$va_matches[1];
								} else {
									$va_raw_output[] = $vs_piece.str_repeat(' ', $vn_pad_len);
								}
							} else {
								$va_raw_output[] = $vs_piece;
							}
							if ($vs_tmp = preg_replace('!^[0]+!', '', $vs_piece)) {
								$va_zeroless_output[] = $vs_tmp;
							} else {
								$va_zeroless_output[] = $vs_piece;
							}
						}
						$va_output[] = join('', $va_raw_output); //.' '.join('.', $va_zeroless_output);
						break;
					case 'SERIAL':
					case 'NUMERIC':
						if ($vn_padding < $va_element_info['width']) { $vn_padding = $va_element_info['width']; }
						if (preg_match("/^([0-9]+)([A-Za-z]{1})$/", $va_element_vals[$vn_i], $va_matches)) {
							$va_output[] = str_repeat(' ', $vn_padding - mb_strlen(intval($va_matches[1]))).intval($va_matches[1]).$va_matches[2];
						} else {
							$va_output[] = str_repeat(' ', $vn_padding - mb_strlen(intval($va_element_vals[$vn_i]))).intval($va_element_vals[$vn_i]);
						}
						break;
					case 'YEAR':
						$vn_p = 4 - mb_strlen($va_element_vals[$vn_i]);
						if ($vn_p < 0) { $vn_p = 0; }
						$va_output[] = str_repeat(' ', $vn_p).$va_element_vals[$vn_i];
						break;
					case 'MONTH':
					case 'DAY':
						$vn_p = 2 - mb_strlen($va_element_vals[$vn_i]);
						if ($vn_p < 0) { $vn_p = 0; }
						$va_output[] = str_repeat(' ', 2 - $vn_p).$va_element_vals[$vn_i];
						break;
					default:
						$va_output[] = str_repeat(' ', $vn_padding - mb_strlen($va_element_vals[$vn_i])).$va_element_vals[$vn_i];
						break;
						
				}
			}
			return join($vs_separator, $va_output);
		}
		# -------------------------------------------------------
		/**
		 * Return a list of modified identifier values suitable for search indexing according to the format of the specified format and type
		 * Modifications include removal of leading zeros, stemming and more.
		 *
		 * @param string $ps_value
		 * @return array
		 */
		public function getIndexValues($ps_value=null) {
			$vs_separator = $this->getSeparator();
			if (!is_array($va_elements_normal_order = $this->getElements())) { $va_elements_normal_order = array(); }
			$va_element_names_normal_order = array_keys($va_elements_normal_order);
			
			if (!($va_elements = $this->getElementOrderForSort())) { $va_elements = $va_element_names_normal_order; }
			if($vs_separator) {
				$va_element_vals = explode($vs_separator, $ps_value ? $ps_value : $this->getValue());
			} else {
				$va_element_vals = array($ps_value ? $ps_value : $this->getValue());
			}
			$va_output = array();
		
			$vn_i = 0;
			
			$va_output = array();
			$vn_max_value_count = 0;
			
			// element-specific processing
			foreach($va_elements as $vn_x => $vs_element) {
				$va_element_info = $va_elements_normal_order[$vs_element];
				$vn_i = array_search($vs_element, $va_element_names_normal_order);
				
				switch($va_element_info['type']) {
					case 'LIST':
						$va_output[$vn_i] = array($va_element_vals[$vn_i]);
						break;
					case 'CONSTANT':
						$va_output[$vn_i] = array($va_element_vals[$vn_i]);
						break;
					case 'FREE':
					case 'ALPHANUMERIC':
						$va_output[$vn_i] = array($va_element_vals[$vn_i]);
						if ((int)$va_element_vals[$vn_i] > 0) {
							$va_output[$vn_i][] = (int)$va_element_vals[$vn_i];
						}
						
						break;
					case 'SERIAL':
					case 'NUMERIC':
					case 'MONTH':
					case 'DAY':
					case 'YEAR':
						$va_output[$vn_i] = array($va_element_vals[$vn_i]);
						if (preg_match('!^([0]+)([\d]+)$!', $va_element_vals[$vn_i], $va_matches)) {
							for($vn_i=0; $vn_i < sizeof($va_matches[1]); $vn_i++) {
								$va_output[$vn_i][] = substr($va_element_vals[$vn_i], $vn_i);
							}
						}
						break;
					default:
						$va_output[$vn_i] = array($va_element_vals[$vn_i]);
						break;
				}
				
				if ($vn_max_value_count < sizeof($va_output[$vn_i])) { $vn_max_value_count = sizeof($va_output[$vn_i]); }
			}
			
			$va_output_values = array();
			
			// Generate permutations from element-specific processing
			for($vn_c=0; $vn_c < $vn_max_value_count; $vn_c++) {
				$va_output_values_buf = array();
				
				foreach($va_elements as $vn_x => $vs_element) {
					if (!isset($va_output[$vn_i][0])) { continue; }
					
					$vn_i = array_search($vs_element, $va_element_names_normal_order);
					if (isset($va_output[$vn_i][$vn_c])) {
						$va_output_values_buf[] = $va_output[$vn_i][$vn_c];
					} else {
						$va_output_values_buf[] = $va_output[$vn_i][0];
					}
				}
				
				$va_output_values[] = join($vs_separator, $va_output_values_buf);
			}
			
			// generate incremental "stems" of identifier by exploding on punctuation
			if(preg_match_all("![^A-Za-z0-9]+!", $ps_value, $va_delimiters)) {
				$va_element_values = preg_split("![^A-Za-z0-9]+!", $ps_value);
				$va_acc = array();
				foreach($va_element_values as $vn_x => $vs_element_value) {
					$va_acc[] = $vs_element_value;
					$va_output_values[] = join('', $va_acc);
					if (is_numeric($vs_element_value)) {
						array_pop($va_acc);
						$va_acc[] = (int)$vs_element_value;
						$va_output_values[] = join('', $va_acc);
					}
					if (sizeof($va_delimiters[0]) > 0) { $va_acc[] = array_shift($va_delimiters[0]); }
				}
			}
			
			// generate versions without leading zeros
			$va_output_values[] = preg_replace("!^[0]+!", "", $ps_value);	// remove leading zeros
			if (preg_match_all("!([^0-9]+)([0]+)!", $ps_value, $va_matches)) {
				$vs_value_proc = $ps_value;
				for($vn_x=0; $vn_x < sizeof($va_matches[0]); $vn_x++) {
					$vs_value_proc = str_replace($va_matches[0][$vn_x], $va_matches[1][$vn_x], $vs_value_proc);
				}
				$va_output_values[] = $vs_value_proc;
			}
			
			// generate version without trailing letters after number (eg. KHF-134b => KHF-134)
			$va_tmp = $va_output_values;
			foreach($va_tmp as $vs_value_proc) {
				$va_output_values[] = preg_replace("!([\d]+)[A-Za-z]+$!", "$1", $vs_value_proc);
			}
			
			return array_unique($va_output_values);
		}
		# -------------------------------------------------------
		# User interace (HTML)
		# -------------------------------------------------------
		public function htmlFormElement($ps_name, &$pa_errors=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vs_id_prefix = isset($pa_options['id_prefix']) ? $pa_options['id_prefix'] : null;
			$vb_generate_for_search_form = isset($pa_options['for_search_form']) ? true : false;
			
			$pa_errors = $this->validateValue($this->getValue());
			
			$vs_separator = $this->getSeparator();
			
			if ($vs_separator) {
				$va_element_vals = explode($vs_separator, $this->getValue());
			} else {
				$va_element_vals = array($this->getValue());
			}
			
			if (!is_array($va_elements = $this->getElements())) { $va_elements = array(); }
			
			$va_element_controls = array();
			$va_element_control_names = array();
			$vn_i=0;
			
			$vb_next_in_seq_is_present = false;
			foreach($va_elements as $vs_element_name => $va_element_info) {
				if (($va_element_info['type'] == 'SERIAL') && ($va_element_vals[$vn_i] == '')) {
					$vb_next_in_seq_is_present = true;
				}
				$vs_tmp = $this->genNumberElement($vs_element_name, $ps_name, $va_element_vals[$vn_i], $vs_id_prefix, $vb_generate_for_search_form, $pa_options);
				$va_element_control_names[] = $ps_name.'_'.$vs_element_name;
		
				if (($pa_options['show_errors']) && (isset($pa_errors[$vs_element_name]))) {
					$vs_error_message = preg_replace("/[\"\']+/", "", $pa_errors[$vs_element_name]);
					if ($pa_options['error_icon']) {
						$vs_tmp .= "<a href='#'\" id='caIdno_{$vs_id_prefix}_{$ps_name}'><img src='".$pa_options['error_icon']."' border='0'/></a>";
					} else {
						$vs_tmp .= "<a href='#'\" id='caIdno_{$vs_id_prefix}_{$ps_name}'>["._t('Error')."]</a>";
					}
					TooltipManager::add("#caIdno_{$vs_id_prefix}_{$ps_name}", "<h2>"._t('Error')."</h2>{$vs_error_message}");
				}
				$va_element_controls[] = $vs_tmp;
				$vn_i++;
			}
			$va_element_error_display = array();
			if (sizeof($va_elements) < sizeof($va_element_vals)) {
				$vs_extra_vals = join($vs_separator, array_slice($va_element_vals, sizeof($va_elements)));
				$va_element_controls[] = "<input type='text' name='".$ps_name."_extra' value='".htmlspecialchars($vs_extra_vals, ENT_QUOTES, 'UTF-8')."' size='10'".($pa_options['readonly'] ? ' readonly="readonly" ' : '').">";
				$va_element_control_names[] = $ps_name.'_extra';
			}
			
			$vs_js = '';
			if (($pa_options['check_for_dupes']) && !$vb_next_in_seq_is_present){
				$va_ids = array();
				foreach($va_element_control_names as $vs_element_control_name) {
					$va_ids[] = "'#".$vs_id_prefix.$vs_element_control_name."'";
				}
				
				$vs_js = '<script type="text/javascript" language="javascript">'."\n// <![CDATA[\n";
				$va_lookup_url_info = caJSONLookupServiceUrl($pa_options['request'], $pa_options['table']);
				$vs_js .= "
					caUI.initIDNoChecker({
						errorIcon: '".$pa_options['error_icon']."',
						processIndicator: '".$pa_options['progress_indicator']."',
						idnoStatusID: 'idnoStatus',
						lookupUrl: '".$va_lookup_url_info['idno']."',
						searchUrl: '".$pa_options['search_url']."',
						idnoFormElementIDs: [".join(',', $va_ids)."],
						separator: '".$this->getSeparator()."',
						row_id: ".intval($pa_options['row_id']).",
						context_id: ".intval($pa_options['context_id']).",
						
						singularAlreadyInUseMessage: '".addslashes(_t('Identifier is already in use'))."',
						pluralAlreadyInUseMessage: '".addslashes(_t('Identifier is already in use %1 times'))."'
					});
				";
				
				$vs_js .= "// ]]>\n</script>\n";	
			}
			
			return join($vs_separator, $va_element_controls).$vs_js;
		}
		# -------------------------------------------------------
		public function htmlFormValue($ps_name, $ps_value=null, $pb_dont_mark_serial_value_as_used=false, $pb_generate_for_search_form=false, $pb_always_generate_serial_values=false) {
			$va_tmp = $this->htmlFormValuesAsArray($ps_name, $ps_value, $pb_dont_mark_serial_value_as_used, $pb_generate_for_search_form, $pb_always_generate_serial_values);
			if (!($vs_separator = $this->getSeparator())) { $vs_separator = ''; }
			
			return (is_array($va_tmp)) ? join($vs_separator, $va_tmp) : null;	
		}
		# -------------------------------------------------------
		/**
		 * Generates an id numbering template (text with "%" characters where serial values should be inserted)
		 * from a value. The elements in the value that are generated as SERIAL incrementing numbers will be replaced
		 * with "%" characters, resulting is a template suitable for use with BundlableLabelableBaseModelWithAttributes::setIdnoTWithTemplate
		 * If the $pb_no_placeholders parameter is set to true then SERIAL values are omitted altogether from the returned template.
		 *
		 * Note that when the number of element replacements is limited, the elements are counted right-to-left. This means that
		 * if you limit the template to two replacements, the *rightmost* two SERIAL elements will be replaced with placeholders.
		 *
		 * @see BundlableLabelableBaseModelWithAttributes::setIdnoTWithTemplate
		 *
		 * @param string $ps_value The id number to use as the basis of the template
		 * @param int $pn_max_num_replacements The maximum number of elements to replace with placeholders. Set to 0 (or omit) to replace all SERIAL elements.
		 * @param bool $pb_no_placeholders If set SERIAL elements are omitted altogether rather than being replaced with placeholder values
		 *
		 * @return string A template
		 */
		public function makeTemplateFromValue($ps_value, $pn_max_num_replacements=0, $pb_no_placeholders=false) {
			$vs_separator = $this->getSeparator();
			$va_values = $vs_separator ? explode($vs_separator, $ps_value) : array($ps_value);
			
			$va_elements = $this->getElements();
			$vn_num_serial_elements = 0;
			foreach($va_elements as $vs_element_name => $va_element_info) {
				if ($va_element_info['type'] == 'SERIAL') { $vn_num_serial_elements++; }
			}
			
			$vn_i = 0;
			$vn_num_serial_elements_seen = 0;
			foreach($va_elements as $vs_element_name => $va_element_info) {
				if ($vn_i >= sizeof($va_values)) { break; }
				
				if ($va_element_info['type'] == 'SERIAL') {
					$vn_num_serial_elements_seen++;
						
					if ($pn_max_num_replacements <= 0) {	// replace all
						if ($pb_no_placeholders) { unset($va_values[$vn_i]); $vn_i++; continue; }
						$va_values[$vn_i] = '%';
					} else {
						if (($vn_num_serial_elements - $vn_num_serial_elements_seen) < $pn_max_num_replacements) {
							if ($pb_no_placeholders) { unset($va_values[$vn_i]); $vn_i++; continue; }
							$va_values[$vn_i] = '%';
						}
					}
				}
				
				$vn_i++;
			}
			
			return join($vs_separator, $va_values);
		}
		# -------------------------------------------------------
		public function htmlFormValuesAsArray($ps_name, $ps_value=null, $pb_dont_mark_serial_value_as_used=false, $pb_generate_for_search_form=false, $pb_always_generate_serial_values=false) {
			if (is_null($ps_value)) {
				if(isset($_REQUEST[$ps_name]) && $_REQUEST[$ps_name]) { return $_REQUEST[$ps_name]; }
			}
			if (!is_array($va_element_list = $this->getElements())) { return null; }
			
			$va_element_names = array_keys($va_element_list);
			$vs_separator = $this->getSeparator();
			$va_element_values = array();
			if ($ps_value) {
				if ($vs_separator) {
					$va_tmp = explode($vs_separator, $ps_value);
				} else {
					$va_tmp = array($ps_value);
				}
				foreach($va_element_names as $vs_element_name) {
					if (!sizeof($va_tmp)) { break; }
					$va_element_values[$ps_name.'_'.$vs_element_name] = array_shift($va_tmp);
				}
			} else {
				foreach($va_element_names as $vs_element_name) {
					if(isset($_REQUEST[$ps_name.'_'.$vs_element_name])) {
						$va_element_values[$ps_name.'_'.$vs_element_name] = $_REQUEST[$ps_name.'_'.$vs_element_name];
					}
				}
			}
			
			$vb_isset = false;
			$vb_is_not_empty = false;
			$va_tmp = array();
			$va_elements = $this->getElements();
			foreach($va_elements as $vs_element_name => $va_element_info) {
				if ($va_element_info['type'] == 'SERIAL') {
					if ($pb_generate_for_search_form) { 
						$va_tmp[$vs_element_name] = $va_element_values[$ps_name.'_'.$vs_element_name]; 
						continue;
					}
					
					if (($va_element_values[$ps_name.'_'.$vs_element_name] == '') || ($va_element_values[$ps_name.'_'.$vs_element_name] == '%') || $pb_always_generate_serial_values) {
						if ($va_element_values[$ps_name.'_'.$vs_element_name] == '%') { $va_element_values[$ps_name.'_'.$vs_element_name] = ''; }
						$va_tmp[$vs_element_name] = $this->getNextValue($vs_element_name, join($vs_separator, $va_tmp), $pb_dont_mark_serial_value_as_used);
						$vb_isset = $vb_is_not_empty = true;
						continue;
					} else {
						if (!$pb_dont_mark_serial_value_as_used && (intval($va_element_values[$ps_name.'_'.$vs_element_name]) > $this->getSequenceMaxValue($ps_name, $vs_element_name, ps_element_name))) {
							$this->setSequenceMaxValue($this->getFormat(), $vs_element_name, join($vs_separator, $va_tmp), $va_element_values[$ps_name.'_'.$vs_element_name]);
						}
					}
				} 
				
				if ($pb_generate_for_search_form) {
					if ($va_element_values[$ps_name.'_'.$vs_element_name] == '') {
						$va_tmp[$vs_element_name] = '';
						break;
					}
				}
				$va_tmp[$vs_element_name] = $va_element_values[$ps_name.'_'.$vs_element_name];
				
				if (isset($va_element_values[$ps_name.'_'.$vs_element_name])) {
					$vb_isset = true;
				}
				if ($va_element_values[$ps_name.'_'.$vs_element_name] != '') {
					$vb_is_not_empty = true;
				}
			}
			if (isset($va_element_values[$ps_name.'_extra']) && ($vs_tmp = $va_element_values[$ps_name.'_extra'])) {
				$va_tmp[$ps_name.'_extra'] = $vs_tmp;
			}
			
			return ($vb_isset && $vb_is_not_empty) ? $va_tmp : null;
		}
		# -------------------------------------------------------
		# Generated id number element
		# -------------------------------------------------------
		
		private function getElementWidth($pa_element_info, $vn_default=3) {
			$vn_width = isset($pa_element_info['width']) ? $pa_element_info['width'] : 0;
			if ($vn_width <= 0) { $vn_width = $vn_default; }
			
			return $vn_width;
		}
		# -------------------------------------------------------
		private function genNumberElement($ps_element_name, $ps_name, $ps_value, $ps_id_prefix=null, $pb_generate_for_search_form=false, $pa_options=null) {
			if (!($vs_format = $this->getFormat())) {
				return null;
			}
			if (!($vs_type = $this->getType())) {
				return null;
			}
			$vs_element = '';
			
			$va_element_info = $this->opa_formats[$vs_format][$vs_type]['elements'][$ps_element_name];
			$vs_element_form_name = $ps_name.'_'.$ps_element_name;
			
			$vs_element_value = $ps_value;
			switch($va_element_info['type']) {
				# ----------------------------------------------------
				case 'LIST':
					if (!$vs_element_value || $va_element_info['editable'] || $pb_generate_for_search_form) {
						if (!$vs_element_value && !$pb_generate_for_search_form) { $vs_element_value = $va_element_info['default']; }
						$vs_element = '<select name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'">';
						if ($pb_generate_for_search_form) {
							$vs_element .= "<option value='' SELECTED='1'>-</option>";
						}
						foreach($va_element_info['values'] as $ps_value) {
							if ($ps_value == $vs_element_value) { $SELECTED = 'SELECTED="1"'; } else { $SELECTED = ''; }
							$vs_element .= '<option '.$SELECTED.'>'.$ps_value.'</option>';
						}
						
						if (!$pb_generate_for_search_form) {
							if (!in_array($vs_element_value, $va_element_info['values'])) {
								$vs_element .= '<option SELECTED="1">'.$vs_element_value.'</option>';
							}
						}
						
						$vs_element .= '</select>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
					}
					
					break;
				# ----------------------------------------------------
				case 'SERIAL':
					$vn_width = $this->getElementWidth($va_element_info, 3);
					
					if ($pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="" maxlength="'.$vn_width.'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
					} else {
						if ($vs_element_value == '') {
							$vs_next_num = $this->getNextValue($ps_element_name, null, true);
							$vs_element .= '&lt;'._t('Will be assigned %1 when saved', $vs_next_num).'&gt;';
						} else {
							if ($va_element_info['editable']) {
								$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
							} else {
								$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
							}
						}
					}
					break;
				# ----------------------------------------------------
				case 'CONSTANT':
					$vn_width = $this->getElementWidth($va_element_info, 3);
					
					if (!$vs_element_value) { $vs_element_value = $va_element_info['value']; }
					if ($va_element_info['editable'] || $pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
					}
					break; 
				# ----------------------------------------------------
				case 'FREE':
				case 'NUMERIC':
				case 'ALPHANUMERIC':
					if (!$vs_element_value && !$pb_generate_for_search_form) { $vs_element_value = $va_element_info['default']; }
					$vn_width = $this->getElementWidth($va_element_info, 3);
					if (!$vs_element_value || $va_element_info['editable'] || $pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
					}
					break;
				# ----------------------------------------------------
				case 'YEAR':
				case 'MONTH':
				case 'DAY':
					$vn_width = $this->getElementWidth($va_element_info, 5);
					$va_date = getdate();
					if ($vs_element_value == '') {
						$vn_value = '';
						if (!$pb_generate_for_search_form) {
							if ($va_element_info['type'] == 'YEAR') { $vn_value = $va_date['year']; }
							if ($va_element_info['type'] == 'MONTH') { $vn_value = $va_date['mon']; }
							if ($va_element_info['type'] == 'DAY') { $vn_value = $va_date['mday']; }
						}
						
						if ($va_element_info['editable'] || $pb_generate_for_search_form) {
							$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vn_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
						} else {
							$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vn_value, ENT_QUOTES, 'UTF-8').'"/>'.$vn_value;
						}
					} else {
						if ($va_element_info['editable'] || $pb_generate_for_search_form) {
							$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
						} else {
							$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_element_value, ENT_QUOTES, 'UTF-8').'"/>'.$vs_element_value;
						}
					}
				
					break;
				# ----------------------------------------------------
				default:
					return '[Invalid element type]';
					break;
				# ----------------------------------------------------
			}
			return $vs_element;
		}
		# -------------------------------------------------------
		public function getSequenceMaxValue($ps_format, $ps_element, $ps_idno_stub) {
			$this->opo_db->dieOnError(false);
			if (!($qr_res = $this->opo_db->query("
				SELECT seq
				FROM ca_multipart_idno_sequences
				WHERE
					(format = ?) AND (element = ?) AND (idno_stub = ?)
			", $ps_format, $ps_element, $ps_idno_stub))) {
				return null;
			}
			if (!$qr_res->nextRow()) { return 0; }
			return $qr_res->get('seq');
		}
		# -------------------------------------------------------
		public function setSequenceMaxValue($ps_format, $ps_element, $ps_idno_stub, $pn_value) {
			$this->opo_db->dieOnError(false);
			$this->opo_db->query("
				DELETE FROM ca_multipart_idno_sequences 
				WHERE format = ? AND element = ? AND idno_stub = ?
			", $ps_format, $ps_element, $ps_idno_stub);
			
			if (!($qr_res = $this->opo_db->query("
				INSERT INTO ca_multipart_idno_sequences
				(format, element, idno_stub, seq)
				VALUES
				(?, ?, ?, ?)
			", $ps_format, $ps_element, $ps_idno_stub, $pn_value))) {
				return null;
			}
			
			return $qr_res;
		}
		# -------------------------------------------------------
		public function setDb($po_db) {
			$this->opo_db = $po_db;
		}
		# -------------------------------------------------------
	}
?>