<?php
/** ---------------------------------------------------------------------
 * app/lib/core/ModelSettings.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__.'/core/BaseSettings.php');
require_once(__CA_LIB_DIR__.'/core/View.php');

class ModelSettings extends BaseSettings {
	# ------------------------------------------------------
	/**
	 *
	 */
	private $o_instance;

	/**
	 *
	 */
	protected $ops_settings_field;
	# ------------------------------------------------------
	public function __construct($t_instance, $ps_settings_field, $pa_settings_defs) {
		parent::__construct($pa_settings_defs);

		$this->o_instance = $t_instance;
		$this->ops_settings_field = $ps_settings_field;
	}
	# ------------------------------------------------------
	public function __destruct() {
		unset($this->o_instance);
	}
	# ------------------------------------------------------
	# Settings
	# ------------------------------------------------------
	/**
	 * Returns an associative array with the setting values for this restriction
	 * The keys of the array are setting codes, the values are the setting values
	 */
	public function getSettings() {
		return $this->o_instance->get($this->ops_settings_field);
	}
	# ------------------------------------------------------
	/**
	 * Set setting value
	 * (you must call insert() or update() to write the settings to the database)
	 */
	public function setSetting($ps_setting, $pm_value) {
		$this->o_instance->set($this->ops_settings_field, $vm_val = parent::setSetting($ps_setting, $pm_value));

		return $this->o_instance->numErrors() ? false : $vm_val;
	}
	# ------------------------------------------------------
	/**
	 * Sets and saves form element settings, taking parameters off of the request as needed. Does an update()
	 * on the ca_search_forms instance to save settings to the database
	 *
	 * @param RequestHTTP $po_request
	 * @param array|null $pa_options
	 * @return mixed
	 */
	public function setSettingsFromHTMLForm($po_request, $pa_options=null) {
		$va_locales = ca_locales::getLocaleList(array('sort_field' => '', 'sort_order' => 'asc', 'index_by_code' => true, 'available_for_cataloguing_only' => true));
		$va_available_settings = $this->getAvailableSettings();

		$this->o_instance->setMode(ACCESS_WRITE);
		$va_values = array();

		$vs_id_prefix = caGetOption('id', $pa_options, 'setting');
		$vs_placement_code = caGetOption('placement_code', $pa_options, '');

		foreach(array_keys($va_available_settings) as $vs_setting) {
			$va_properties = $va_available_settings[$vs_setting];
			if (isset($va_properties['takesLocale']) && $va_properties['takesLocale']) {
				foreach($va_locales as $vs_locale => $va_locale_info) {
					$va_values[$vs_setting][$va_locale_info['locale_id']] = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}_{$vs_locale}", pString);
				}
			} else {
				if (
					(isset($va_properties['useRelationshipTypeList']) && $va_properties['useRelationshipTypeList'] && ($va_properties['height'] > 1))
					||
					(isset($va_properties['useList']) && $va_properties['useList'] && ($va_properties['height'] > 1))
					||
					(isset($va_properties['showLists']) && $va_properties['showLists'] && ($va_properties['height'] > 1))
					||
					(isset($va_properties['showVocabularies']) && $va_properties['showVocabularies'] && ($va_properties['height'] > 1))
				) {
					$va_values[$vs_setting] = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}", pArray);
				} else {
					switch($va_properties['formatType']) {
						case FT_BIT:
							// skip bits if they're not set in the form; otherwise they default to 'No' even
							// though the default might be set to 'Yes' the settings definition.
							$vs_val = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}", pString);
							if($vs_val == '') { continue; }

							$va_values[$vs_setting] = $vs_val;
							break;
						default:
							$va_values[$vs_setting] = $po_request->getParameter("{$vs_placement_code}{$vs_id_prefix}{$vs_setting}", pString);
							break;
					}

				}
			}

			foreach($va_values as $vs_setting_key => $vs_value) {
				$this->setSetting($vs_setting, $vs_value);
			}
		}
		return $this->o_instance->update();
	}
	# ------------------------------------------------------
}
