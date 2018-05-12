<?php
/** ---------------------------------------------------------------------
 * app/lib/MetadataAlerts/TriggerTypes/Base.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__.'/MetadataAlerts/TriggerTypes/Modification.php');
require_once(__CA_LIB_DIR__.'/MetadataAlerts/TriggerTypes/Date.php');

define('__CA_MD_ALERT_CHECK_TYPE_SAVE__', 0);
define('__CA_MD_ALERT_CHECK_TYPE_PERIODIC__', 1);

abstract class Base {

	/**
	 * Value array for given trigger
	 * @var array
	 */
	protected $opa_trigger_values;

	/**
	 * Base constructor.
	 * @param array $pa_trigger_values
	 */
	public function __construct(array $pa_trigger_values) {
		$this->opa_trigger_values = $pa_trigger_values;
	}

	/**
	 * @return array
	 */
	public function getTriggerValues() {
		return $this->opa_trigger_values;
	}

	/**
	 * @param array $pa_trigger_values
	 */
	public function setTriggerValues($pa_trigger_values) {
		$this->opa_trigger_values = $pa_trigger_values;
	}
	
	/**
	 * Return query criteria for BundlableLabelableBaseModelWithAttributes::find() to generate a set of records that may require 
	 * notifications. This base implementation just returns null.
	 *
	 * @param array $pa_trigger_values
	 *
	 * @return array Query parameters for use with BundlableLabelableBaseModelWithAttributes::find(), false if trigger is invalid or null if criteria not support for trigger type.
	 */
	public function getTriggerQueryCriteria($pa_trigger_values) {
		return null;
	}
	
	/**
	 * Unique key for trigger event
	 *
	 * @param BaseModel $t_instance
	 *
	 * @return string Always returns null
	 */
	public function getEventKey($t_instance) {
		return null;
	}
	
	/**
	 * Extra data to attach to notification for triggered event
	 *
	 * @param BaseModel $t_instance
	 *
	 * @return string Always returns null
	 */
	public function getData($t_instance) {
		return null;
	}

	/**
	 * Get list of available settings for ca_metadata_alert_triggers model settings
	 * (depending on what type is selected)
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		$va_generic_settings = [
			'notificationTemplate' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'default' => '',
				'width' => 90, 'height' => 4,
				'label' => _t('Notification template'),
				'validForRootOnly' => 1,
				'description' => _t('Message for the alert notification sent to users. This is a display template with access to all metadata in the affected record.')
			),
			'notificationDeliveryOptions' => [
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'multiple' => true,
				'options' => [_t('by email') => 'EMAIL', _t('to dashboard') => 'INBOX'],
				'default' => 'INBOX',
				'label' => _t('Deliver notifications'),
				'description' => _t('Choose methods to deliver notifications here.')
			]
		];

		return array_merge($this->getTypeSpecificSettings(), $va_generic_settings);
	}

	/**
	 * Check if this trigger fired
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return bool
	 */
	abstract public function check(&$t_instance);

	/**
	 * This should return a list of type specific settings in the usual ModelSettings format
	 *
	 * @return array
	 */
	abstract public function getTypeSpecificSettings();

	/**
	 * Should return one of the constants
	 *
	 * 		__CA_MD_ALERT_CHECK_TYPE_SAVE__
	 * 		__CA_MD_ALERT_CHECK_TYPE_PERIODIC__
	 *
	 * Sometimes this can be baked into the Trigger type implementation, other times
	 * it will have to depend on the settings of the rule/trigger the user set up
	 *
	 * @return int
	 */
	abstract public function getTriggerType();

	/**
	 * Get notification message
	 * @param \BundlableLabelableBaseModelWithAttributes $t_instance
	 * @return string
	 */
	public function getNotificationMessage(&$t_instance) {
        global $g_request;

        if(!$g_request) {
            $g_request = new \RequestHTTP(null, ['no_headers' => true, 'simulateWith' => ['REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => 'index.php']]);
        }
        
		if(!($vs_template = $this->getTriggerValues()['settings']['notificationTemplate'])) {
			$t_rule = new \ca_metadata_alert_rules($this->getTriggerValues()['rule_id']);

			return _t(
				"Metadata alert rule '%1' triggered for record %2",
				$t_rule->getLabelForDisplay(),
				caEditorLink($g_request, $t_instance->get($t_instance->tableName().".preferred_labels"), '', $t_instance->tableName(), $t_instance->getPrimaryKey(), [], ['absolute' => true])
			);
		} else {
			return $t_instance->getWithTemplate($vs_template, ['absolute' => true]);
		}
	}
	
	/**
	 * List of data types to restrict metadata element list to. 
	 * Return null to disable filtering and show full element list
	 *
	 * @return array
	 */
	public function getElementDataTypeFilters() {
		return null;
	}
	
	/**
	 * List of elements to add to standard list of element. Return null if
	 * no elements are to be added.
	 *
	 * @return array
	 */
	public function getAdditionalElementList() {
		return null;
	}
	
	/**
	 * Return additional filter values for specified metadata element. Return null for no filtering.
	 *
	 * @return string
	 */
	public function getElementFilters($pn_element_id, $ps_prefix_id) {
		return null;
	}

	/**
	 * Returns available trigger types as list for HTML select
	 *
	 * @return array
	 */
	public static function getAvailableTypes() {
		return array(
			_t('Modification') => 'Modification',
			_t('Date') => 'Date',
			//_t('List value chosen') => 'ListValue',
			//_t('Conditions met') => 'Expression',
		);
	}

	/**
	 * Get instance
	 *
	 * @param string $ps_trigger_type
	 * @param array $pa_values
	 * @return Base
	 * @throws \Exception
	 */
	public static function getInstance($ps_trigger_type, array $pa_values = []) {
		switch($ps_trigger_type) {
			case 'Modification':
				return new Modification($pa_values);
			case 'Date':
				return new Date($pa_values);
			case 'ListValue':
			case 'Expression':
				// @todo
			default:
				throw new \Exception('Invalid trigger type');
		}
	}
}
