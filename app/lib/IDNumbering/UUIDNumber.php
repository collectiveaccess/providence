<?php
/** ---------------------------------------------------------------------
 * app/lib/IDNumbering/UUIDNumber.php : plugin to generate UUIDs
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2021 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__ . "/IDNumbering/IDNumber.php");
require_once(__CA_APP_DIR__ . "/helpers/navigationHelpers.php");

class UUIDNumber extends IDNumber implements IIDNumbering {
	# -------------------------------------------------------
	/**
	 * Initialize the plugin
	 *
	 * @param string $format A format to set as current [Default is null]
	 * @param mixed $type A type to set a current [Default is __default__] 
	 * @param string $value A value to set as current [Default is null]
	 * @param Db $db A database connection to use for all queries. If omitted a new connection (may be pooled) is allocated. [Default is null]
	 */
	public function __construct($format=null, $type=null, $value=null, $db=null) {
		parent::__construct($format, $type, $value, $db);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function validateValue($value) {
		$element_errors = [];
		if(!preg_match("/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/", $value)) {
			$elements = $this->getElements();
			foreach($elements as $ename => $info) {
				$element_errors[$ename] = _t('%1 is not a valid UUID', $value);
				break;
			}
		}
		return $element_errors;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function isValidValue($value=null) {
		return $this->validateValue(!is_null($value) ? $value : $this->getValue());
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getNextValue($element_name, $value=null) {
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Returns sortable value padding according to the format of the specified format and type
	 *
	 * @param string $value
	 * @return string
	 */
	public function getSortableValue($value=null) {
		if($output = $value ? $value : $this->getValue());
		return $output;
	}
	# -------------------------------------------------------
	/**
	 * Return a list of modified identifier values suitable for search indexing according to the format of the specified format and type
	 * Modifications include removal of leading zeros, stemming and more.
	 *
	 * @param string $value
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
	/**
	 *
	 */
	public function htmlFormElement($name, &$errors=null, $options=null) {
		$o_config = Configuration::load();
		if (!is_array($options)) { $options = []; }

		$errors = $this->validateValue($this->getValue());

		$dont_allow_editing = isset($options['row_id']) && ($options['row_id'] > 0) && $o_config->exists($this->getFormat().'_dont_allow_editing_of_codes_when_in_use') && (bool)$o_config->get($this->getFormat().'_dont_allow_editing_of_codes_when_in_use');
		if ($dont_allow_editing) { $options['readonly'] = true; }

		if (!is_array($elements = $this->getElements())) { $elements = []; }

		$element_val = $this->getValue();

		$element_controls = $element_control_names = [];

		$generate_for_search_form = isset($options['for_search_form']) ? true : false;
		$id_prefix = isset($options['id_prefix']) ? $options['id_prefix'] : null;

		foreach($elements as $ename => $info) {
			$tmp = $this->genNumberElement($ename, $name, $element_val, $generate_for_search_form, false, $options);
			$element_control_names[] = $name.'_'.$ename;

			if (($options['show_errors']) && (isset($errors[$ename]))) {
				$error_message = preg_replace("/[\"\']+/", "", $errors[$ename]);
				if ($options['error_icon']) {
					$tmp .= "<a href='#' id='caIdno_{$id_prefix}_{$name}'>".$options['error_icon']."</a>";
				} else {
					$tmp .= "<a href='#' id='caIdno_{$id_prefix}_{$name}'>["._t('Error')."]</a>";
				}
				TooltipManager::add("#caIdno_{$id_prefix}_{$name}", "<h2>"._t('Error')."</h2>{$error_message}");
			}
			$element_controls[] = $tmp;
			
			break;
		}
		return join('', $element_controls);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function htmlFormValue($name, $value=null, $dont_mark_serial_value_as_used=false, $generate_for_search_form=false, $always_generate_serial_values=false) {
		$tmp = $this->htmlFormValuesAsArray($name, $value, $dont_mark_serial_value_as_used, $generate_for_search_form, $always_generate_serial_values);
		
		return (is_array($tmp)) ? join('', $tmp) : null;
	}
	# -------------------------------------------------------
	/**
	 * Generates an id numbering template (text with "%" characters where serial values should be inserted)
	 * from a value. The elements in the value that are generated as SERIAL incrementing numbers will be replaced
	 * with "%" characters, resulting is a template suitable for use with BundlableLabelableBaseModelWithAttributes::setIdnoTWithTemplate
	 * If the $no_placeholders parameter is set to true then SERIAL values are omitted altogether from the returned template.
	 *
	 * Note that when the number of element replacements is limited, the elements are counted right-to-left. This means that
	 * if you limit the template to two replacements, the *rightmost* two SERIAL elements will be replaced with placeholders.
	 *
	 * @see BundlableLabelableBaseModelWithAttributes::setIdnoTWithTemplate
	 *
	 * @param string $value The id number to use as the basis of the template
	 * @param int $pn_max_num_replacements The maximum number of elements to replace with placeholders. Set to 0 (or omit) to replace all SERIAL elements.
	 * @param bool $no_placeholders If set SERIAL elements are omitted altogether rather than being replaced with placeholder values
	 *
	 * @return string A template
	 */
	public function makeTemplateFromValue($value, $max_num_replacements=0, $no_placeholders=false) {
		return $value;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function htmlFormValuesAsArray($name, $value=null, $dont_mark_serial_value_as_used=false, $generate_for_search_form=false, $always_generate_serial_values=false) {
		if (is_null($value)) {
			if(isset($_REQUEST[$name]) && $_REQUEST[$name]) { return $_REQUEST[$name]; }
		}
		if (!is_array($elements = $this->getElements())) { return null; }

		$element_names = array_keys($elements);
		$element_values = [];
		if ($value) {
			$tmp = [$value];
			foreach($element_names as $ename) {
				if (!sizeof($tmp)) { break; }
				$element_values[$name.'_'.$ename] = array_shift($tmp);
			}
		} else {
			foreach($element_names as $ename) {
				if(isset($_REQUEST[$name.'_'.$ename])) {
					$element_values[$name.'_'.$ename] = $_REQUEST[$name.'_'.$ename];
				}
			}
		}

		$isset = $is_not_empty = false;
		$tmp = [];
		foreach($elements as $ename => $info) {
			if ($generate_for_search_form) {
				if ($element_values[$name.'_'.$ename] == '') {
					$tmp[$ename] = '';
					break;
				}
			}
			$tmp[$ename] = $element_values[$name.'_'.$ename];

			if (isset($element_values[$name.'_'.$ename])) {
				$isset = true;
			}
			if ($element_values[$name.'_'.$ename] != '') {
				$is_not_empty = true;
			}
			
			break;
		}

		return ($isset && $is_not_empty) ? $tmp : null;
	}
	# -------------------------------------------------------
	# Generated id number element
	# -------------------------------------------------------
	/**
	 *
	 */
	private function getElementWidth($element_info, $default=3) {
		$width = isset($element_info['width']) ? $element_info['width'] : 0;
		if ($width <= 0) { $width = $default; }

		return $width;
	}	
	# -------------------------------------------------------
	/**
	 *
	 */
	private function genNumberElement($element_name, $name, $value, $id_prefix=null, $generate_for_search_form=false, $options=null) {
		if (!($format = $this->getFormat())) {
			return null;
		}
		if (!($type = $this->getType())) {
			return null;
		}
		$element = '';
		$element_info = $this->formats[$format][$type]['elements'][$element_name];

		$element_form_name = $name.'_'.$element_name;
		$width = $this->getElementWidth($element_info, 3);
		if(caIsGUID($value)) {
			if ($element_info['editable'] || $generate_for_search_form) {
				$element .= '<input type="text" name="'.$element_form_name.'" id="'.$id_prefix.$element_form_name.'" value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'" size="'.$width.'" maxlength="'.$width.'"'.($options['readonly'] ? ' readonly="readonly" ' : '').'/>';
			} else {
				$element .= '<input type="hidden" name="'.$element_form_name.'" id="'.$id_prefix.$element_form_name.'" value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"/>'.$value;
			}
			return $element;
		}
		
		if(!($uuid = caGenerateGUID())){
			return '[Invalid element type]';
		}
		
		if ($element_info['editable'] || $generate_for_search_form) {
			$element .= '<input type="text" name="'.$element_form_name.'" id="'.$id_prefix.$element_form_name.'" value="'.htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8').'" size="'.$width.'" maxlength="'.$width.'"'.($options['readonly'] ? ' readonly="readonly" ' : '').'/>';
		} else {
			$element .= '<input type="hidden" name="'.$element_form_name.'" id="'.$id_prefix.$element_form_name.'" value="'.htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8').'"/>'.$uuid;
		}
		
		return $element;
	}
	# -------------------------------------------------------
	/**
	 * Return separator string for current format
	 *
	 * @return string An empty string is returned in all cases as UUID identifiers do not use separators.
	 */
	public function getSeparator() {
		return '';
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function isSerialFormat($format=null, $type=null) {
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
	 * @return bool Always returns false as UUID formats are always a single element.
	 */
	public function formatIsExtensionOf($format, $type='__default__') {
		return false;
	}
	# -------------------------------------------------------
}
