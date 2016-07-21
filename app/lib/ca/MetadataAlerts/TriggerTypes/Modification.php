<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/MetadataAlerts/TriggerTypes/Modification.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage MetadataAlerts
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\MetadataAlerts\TriggerTypes;

require_once(__CA_MODELS_DIR__ . '/ca_metadata_elements.php');

class Modification extends Base {

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	public function getTypeSpecificSettings() {
		return [
			'trigger_fire' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'default' => 1,
				'width' => 60, 'height' => 1,
				'label' => _t('Trigger on change type'),
				'description' => _t('Defines when this trigger fires. '),
				'options' => array(
					_t('When element chosen for this trigger is changed') => 'element',
					_t('Any change') => 'any',
				)
			),

			// all accessible items?
			// restrict by set?
		];
	}

	public function getTriggerType() {
		return __CA_MD_ALERT_CHECK_TYPE_SAVE__;
	}

	/**
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @param int $pn_check_type
	 * @return bool
	 */
	public function check(&$t_instance, $pn_check_type) {
		$va_values = $this->getTriggerValues();
		if(!sizeof($va_values)) { return false; }

		switch($va_values['settings']['trigger_fire']) {
			case 'any':
				return $t_instance->hasChangedSinceLoad();
			case 'element':
			default:
				// if trigger_fire is based on element, but no element is set,
				// just bail. trigger did not fire in that case
				if(!$va_values['element_id']) { return false; }

				$vs_code = \ca_metadata_elements::getElementCodeForId($va_values['element_id']);
				return $t_instance->elementHasChanged($vs_code);
		}
	}
}
