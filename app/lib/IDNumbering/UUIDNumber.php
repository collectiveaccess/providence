<?php
/** ---------------------------------------------------------------------
 * app/lib/IDNumbering/UUIDNumber.php : plugin to generate UUIDs
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2018 Whirl-i-Gig
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
 */


	require_once(__CA_LIB_DIR__ . "/Configuration.php");
	require_once(__CA_LIB_DIR__ . "/Datamodel.php");
	require_once(__CA_LIB_DIR__ . "/Db.php");
	require_once(__CA_LIB_DIR__ . "/ApplicationVars.php");
	require_once(__CA_LIB_DIR__ . "/IDNumbering/IDNumber.php");
	require_once(__CA_LIB_DIR__ . "/IDNumbering/IIDNumbering.php");
	require_once(__CA_APP_DIR__ . "/helpers/navigationHelpers.php");

	class UUIDNumber extends IDNumber implements IIDNumbering {
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
			$va_element_errors = array();
			$va_elements = $this->getElements();
			foreach($va_elements as $vs_element_name => $va_element_info) {
				switch($va_element_info['type']) {
					case 'VER3':
                    case 'VER5':
                        if (!$ps_value) {
                            $va_element_errors[$vs_element_name] = _t("A value is reuiqred for UUID Versions 3 & 5");
                        }
                        if($va_element_info['namespaceID'] == ''){
                            $va_element_errors[$vs_element_name] = _t("A NamespaceID (root UUID) is required for UUID %1", $va_element_info['type']);
                        }
						break;
					case 'VER4':
						# noop
						break;
					default:
						# noop
						break;
				}
			}
			return $va_element_errors;
		}
		# -------------------------------------------------------
		public function isValidValue($ps_value=null) {
			return $this->validateValue(!is_null($ps_value) ? $ps_value : $this->getValue());
		}
		# -------------------------------------------------------
		public function getNextValue($ps_element_name,$ps_value=null, $pb_dont_mark_value_as_used=false) {
            // I don't think this function is necessary
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns sortable value padding according to the format of the specified format and type
		 *
		 * @param string $ps_value
		 * @return string
		 */
		public function getSortableValue($ps_value=null) {
            if($vs_output = $ps_value ? $ps_value : $this->getValue());
			return $vs_output;
		}
		# -------------------------------------------------------
		/**
		 * Return a list of modified identifier values suitable for search indexing according to the format of the specified format and type
		 * Modifications include removal of leading zeros, stemming and more.
		 *
		 * @param string $ps_value
		 * @return array
		 */
		public function getIndexValues($value=null) {
            if($output = ($value ? $value : $this->getValue()));
            if(!is_array($output)) { $output = [$output]; }
			return $output;
		}
		# -------------------------------------------------------
		# User interace (HTML)
		# -------------------------------------------------------
		public function htmlFormElement($ps_name, &$pa_errors=null, $pa_options=null) {

			$o_config = Configuration::load();
			if (!is_array($pa_options)) { $pa_options = array(); }

			$pa_errors = $this->validateValue($this->getValue());

			$vb_dont_allow_editing = isset($pa_options['row_id']) && ($pa_options['row_id'] > 0) && $o_config->exists($this->getFormat().'_dont_allow_editing_of_codes_when_in_use') && (bool)$o_config->get($this->getFormat().'_dont_allow_editing_of_codes_when_in_use');
			if ($vb_dont_allow_editing) { $pa_options['readonly'] = true; }

			if (!is_array($va_elements = $this->getElements())) { $va_elements = array(); }

			$va_element_val = $this->getValue();

			$va_element_controls = $va_element_control_names = array();
			$vn_i=0;

			$vb_generate_for_search_form = isset($pa_options['for_search_form']) ? true : false;

			foreach($va_elements as $vs_element_name => $va_element_info) {
				$vs_tmp = $this->genNumberElement($vs_element_name, $ps_name, $va_element_val, $vb_generate_for_search_form, $pa_options);
				$va_element_control_names[] = $ps_name.'_'.$vs_element_name;

				if (($pa_options['show_errors']) && (isset($pa_errors[$vs_element_name]))) {
					$vs_error_message = preg_replace("/[\"\']+/", "", $pa_errors[$vs_element_name]);
					if ($pa_options['error_icon']) {
						$vs_tmp .= "<a href='#' id='caIdno_{$vs_id_prefix}_{$ps_name}'>".$pa_options['error_icon']."</a>";
					} else {
						$vs_tmp .= "<a href='#' id='caIdno_{$vs_id_prefix}_{$ps_name}'>["._t('Error')."]</a>";
					}
					TooltipManager::add("#caIdno_{$vs_id_prefix}_{$ps_name}", "<h2>"._t('Error')."</h2>{$vs_error_message}");
				}
				$va_element_controls[] = $vs_tmp;
				$vn_i++;
			}
			$va_element_error_display = array();

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
		function genUUID($ps_type){
			
			switch($ps_type) {
				# ----------------------------------------------------
				case 'VER3':
				    /*
				     * VER3 is not supported at this stime
				     * It requires that we provide the generator with
				     * a namespace (possible) and a name (not possible)
				     * Implementing this would require intercepting the 
				     * record before it is saved, which currently is not
				     * done
				     *
					if (!$vs_namespace = $va_element_info['namespaceID'] || $vs_gen_value){ return null; }
					$vs_namespace = $va_element_info['namespaceID'];
					$vn_width = $this->getElementWidth($va_element_info, 3);
					$vs_ns_hex = str_replace('-', '', $vs_namespace);
					$vs_ns_string = '';
					for($i = 0; $i < strlen($vs_ns_hex); $i += 2){
						$vs_ns_string .= chr(hexdec($vs_ns_hex[$i].$vs_ns_hex[$i+1]));
					}

					$vs_hash = md5($vs_ns_string.$vs_gen_value);
					$va_uuid3_elements = array();
					$va_uuid3_elements['time_low'] = substr($vs_hash, 0, 8);
					$va_uuid3_elements['time_mid'] = substr($vs_hash, 8, 4);
					$va_uuid3_elements['time_hi_and_version'] = sprintf('%04x',(hexdec(substr($vs_hash, 12, 4)) & 0x0fff) | 0x3000);
					$va_uuid3_elements['clk_seq_hi_res'] = sprintf('%04x', (hexdec(substr($vs_hash, 16, 4)) & 0x3fff) | 0x8000);
					$va_uuid3_elements['node'] = substr($vs_hash, 20, 12);

					$vs_uuid3 = implode('-', $va_uuid3_elements);
					if ($va_element_info['editable'] || $pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid3, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid3, ENT_QUOTES, 'UTF-8').'"/>'.$vs_uuid3;
					}
					*/
					return false;
					break;
				# ----------------------------------------------------
				case 'VER4':
					$vn_width = $this->getElementWidth($va_element_info, 3);

					$vs_rand_data = openssl_random_pseudo_bytes(16);

					$vs_rand_data[6] = chr(ord($vs_rand_data[6]) & 0x0f | 0x40);
    				$vs_rand_data[8] = chr(ord($vs_radn_data[8]) & 0x3f | 0x80);

					$vs_uuid4 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($vs_rand_data), 4));
					
					return $vs_uuid4;
					
					if ($va_element_info['editable'] || $pb_generate_for_search_form) {
						
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid4, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid4, ENT_QUOTES, 'UTF-8').'"/>'.$vs_uuid4;
					}
					break;
				# ----------------------------------------------------
				case 'VER5':
				    /*
				     * VER5 is not supported at this stime
				     * For the same reason as VER3 (this is the same, 
				     * with the exception that it uses SHA-1 instead
				     * of MD5)
				     *
					if (!$vs_namespace = $va_element_info['namespaceID'] || $vs_gen_value){ return null; }
					$vn_width = $this->getElementWidth($va_element_info, 3);
					$vs_namespace = $va_element_info['namespaceID'];
					$vs_ns_hex = str_replace('-', '', $vs_namespace);
					$vs_ns_string = '';
					for($i = 0; $i < strlen($vs_ns_hex); $i += 2){
						$vs_ns_string .= chr(hexdec($vs_ns_hex[$i].$vs_ns_hex[$i+1]));
					}

					$vs_hash = sha1($vs_ns_string.$vs_gen_value);

					$va_uuid5_elements = array();
					$va_uuid5_elements['time_low'] = substr($vs_hash, 0, 8);
					$va_uuid5_elements['time_mid'] = substr($vs_hash, 8, 4);
					$va_uuid3_elements['time_hi_and_version'] = sprintf('%04x',(hexdec(substr($vs_hash, 12, 4)) & 0x0fff) | 0x5000);
					$va_uuid3_elements['clk_seq_hi_res'] = sprintf('%04x', (hexdec(substr($vs_hash, 16, 4)) & 0x3fff) | 0x8000);
					$va_uuid5_elements['node'] = substr($vs_hash, 20, 12);

					$vs_uuid5 = implode('-', $va_uuid5_elements);

					if ($va_element_info['editable'] || $pb_generate_for_search_form) {
						$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid5, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
					} else {
						$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid5, ENT_QUOTES, 'UTF-8').'"/>'.$vs_uuid5;
					}
					*/
					return false;
					break;
				# ----------------------------------------------------
				default:
					return false;
					break;
				# ----------------------------------------------------
			}
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
			$vs_gen_value = $ps_value;
			$vn_width = $this->getElementWidth($va_element_info, 3);
			if(preg_match("/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/", $ps_value) == true){
				if ($va_element_info['editable'] || $pb_generate_for_search_form) {
					$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($ps_value, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
				} else {
					$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($ps_value, ENT_QUOTES, 'UTF-8').'"/>'.$ps_value;
				}
				return $vs_element;
			}
			$vs_uuid = $this->genUUID($va_element_info['type']);
			if(!$vs_uuid){
				return '[Invalid element type]';
			}
			
			if ($va_element_info['editable'] || $pb_generate_for_search_form) {
				$vs_element .= '<input type="text" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid, ENT_QUOTES, 'UTF-8').'" size="'.$vn_width.'" maxlength="'.$vn_width.'"'.($pa_options['readonly'] ? ' readonly="readonly" ' : '').'/>';
			} else {
				$vs_element .= '<input type="hidden" name="'.$vs_element_form_name.'" id="'.$ps_id_prefix.$vs_element_form_name.'" value="'.htmlspecialchars($vs_uuid, ENT_QUOTES, 'UTF-8').'"/>'.$vs_uuid;
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
		/**
		 * Return separator string for current format
		 *
		 * @return string Separator, or "." if no separator setting is present
		 */
		public function getSeparator() {
			return $this->getFormatProperty('separator', array('default' => '.'));
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
		/**
		 * Returns true if the current format is an extension of $ps_format
		 * That is, the current format is the same as the $ps_form with an auto-generated
		 * extra element such that the system can auto-generate unique numbers using a $ps_format
		 * compatible number as the basis. This is mainly used to determine if the system configuration
		 * is such that object numbers can be auto-generated based upon lot numbers.
		 *
		 * @param string $ps_string
		 * @param string $ps_type [Default is __default__]
		 * @return bool
		 */
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
		/**
		 * Return property for current format
		 *
		 * @param string $ps_property A format property name (eg. "separator")
		 * @param array $pa_options Options include:
		 *		default = Value to return if property does not exist [Default is null]
		 * @return string
		 */
		public function getFormatProperty($ps_property, $pa_options=null) {
			if (($vs_format = $this->getFormat()) && ($vs_type = $this->getType()) && isset($this->opa_formats[$vs_format][$vs_type][$ps_property])) {
				return $this->opa_formats[$vs_format][$vs_type][$ps_property] ? $this->opa_formats[$vs_format][$vs_type][$ps_property] : '';
			}
			return caGetOption('default', $pa_options, null);
		}
		# -------------------------------------------------------
		/**
		 * Returns true if editable is set to 1 for the identifier, otherwise returns false
		 * Also, if the identifier consists of multiple elements, false will be returned.
		 *
		 * @param string $ps_format_name Name of format
		 * @param array $pa_options Options include:
		 *		singleElementsOnly = Only consider formats with a single editable element to be editable. [Default is false]
		 * @return bool
		 */
		public function isFormatEditable($ps_format_name, $pa_options=null) {
			if (!is_array($va_elements = $this->getElements())) { return false; }

			$vb_single_elements_only = caGetOption('singleElementsOnly', $pa_options, false);

			foreach($va_elements as $vs_element => $va_element_info) {
				if (isset($va_element_info['editable']) && (bool)$va_element_info['editable']) { return true; }
				if ($vb_single_elements_only) { return false; }
			}
			return false;
		}
		# -------------------------------------------------------
	}
?>
