<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/MetadataAlerts/TriggerTypes/Base.php
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

require_once(__CA_LIB_DIR__.'/ca/MetadataAlerts/TriggerTypes/Modification.php');

abstract class Base {

	/**
	 * Get list of available settings for ca_metadata_alert_triggers model settings
	 * (depending on what type is selected)
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		// @todo are there any generic settings (i.e. available for all types) we need to add here!?
		return $this->getTypeSpecificSettings();
	}

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	abstract public function getTypeSpecificSettings();

	/**
	 * Returns available trigger types as list for HTML select
	 *
	 * @return array
	 */
	public static function getAvailableTypes() {
		return array(
			_t('Modification') => 'Modification',
			_t('List value chosen') => 'ListValue',
			_t('Date') => 'Date',
			_t('Conditions met') => 'Expression',
		);
	}

	/**
	 * Get instance
	 *
	 * @param string $ps_trigger_type
	 * @return Base
	 * @throws \Exception
	 */
	public static function getInstance($ps_trigger_type) {
		switch($ps_trigger_type) {
			case 'Modification':
				return new Modification();
			case 'ListValue':
			case 'Date':
			case 'Expression':
			default:
				throw new \Exception('Invalid trigger type');
		}
	}
}
