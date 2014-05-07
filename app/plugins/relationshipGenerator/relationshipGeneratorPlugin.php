<?php
/* ----------------------------------------------------------------------
 * relationshipGeneratorPlugin.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
 * This file originally contributed 2014 by Gaia Resources
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
 * ----------------------------------------------------------------------
 */

// For the caProcessTemplateForIDs() function
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

/**
 * The Relationship Generator plugin uses a set of configurable `rules` to automatically manage relationships for any
 * type of model that extends BundlableLabelableBaseModelWithAttributes, but does not extend BaseRelationshipModel
 * (relationships cannot be created from other relationships).
 *
 * Each rule specifies the table(s) (i.e. model type(s)) that the relationship will be created for, a template which
 * extracts a "value" from the record being saved (this might be a single field value, or a combination of field values
 * into a single string to check multiple fields), and a set of regular expressions that are matched against the
 * extracted "value".  Each rule also specifies the target table and record identifier (primary key value or idno),
 * plus the relationship type, which defines the type of relationship to be managed.
 *
 * If the plugin detects a relationship that does not exist for a record being saved, but should exist according to the
 * defined rules, it will create the relationship.  This behaviour can be disabled by setting the `add_matched` config
 * item to 0.
 *
 * If the plugin detects a relationship that exists for a record being saved, but should not exist according to the
 * defined rules, it will remove the relationship.  This behaviour can be disabled by setting the `remove_unmatched`
 * config item to 0.
 *
 * The plugin will run on both initial creation of the record, and modification of an existing record.  These
 * behaviours can be disabled individually by setting `process_on_insert` and `process_on_update` config items to 0.
 *
 * The plugin will normally notify the user of any added or removed relationships.  This can be disabled by setting the
 * `notify` config item to 0.
 */
class relationshipGeneratorPlugin extends BaseApplicationPlugin {

	const CONF_KEY_ENABLED = 'enabled';
	const CONF_KEY_PROCESS_ON_INSERT = 'process_on_insert';
	const CONF_KEY_PROCESS_ON_UPDATE = 'process_on_update';
	const CONF_KEY_ADD_MATCHED = 'add_matched';
	const CONF_KEY_REMOVE_UNMATCHED = 'remove_unmatched';
	const CONF_KEY_NOTIFY = 'notify';
	const CONF_KEY_RULES = 'rules';

	/** @var Configuration */
	private $opo_config;

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Automatically assigns an object to a collection, based upon rules you specify in the configuration file associated with the plugin');
		$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'relationshipGenerator.conf');
	}

	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => (bool)$this->opo_config->getBoolean(self::CONF_KEY_ENABLED)
		);
	}

	public function hookAfterBundleInsert(&$pa_params) {
		if ($this->opo_config->getBoolean(self::CONF_KEY_PROCESS_ON_INSERT) && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
	}

	public function hookAfterBundleUpdate(&$pa_params) {
		if ($this->opo_config->getBoolean(self::CONF_KEY_PROCESS_ON_UPDATE) && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
	}

	/**
	 * Only operate on the appropriate models; specifically exclude relationships as it leads to infinite recursion.
	 * @param $po_instance object
	 * @return bool True if the parameter object is the relevant type of model (i.e. bundlable and labelable, but not
	 *   a relationship model), otherwise false.
	 */
	protected function _isRelevantInstance($po_instance) {
		return ($po_instance instanceof BundlableLabelableBaseModelWithAttributes)
			&& !($po_instance instanceof BaseRelationshipModel);
	}

	/**
	 * Main processing method, both hooks (insert and update) delegate to this.
	 * @param $pa_params array As given to the hook method.
	 */
	protected function _process(&$pa_params) {
		// Configuration items used multiple times
		$vb_addMatched = $this->opo_config->getBoolean(self::CONF_KEY_ADD_MATCHED);
		$vb_removeUnmatched = $this->opo_config->getBoolean(self::CONF_KEY_REMOVE_UNMATCHED);
		$vo_notifications = $this->opo_config->getBoolean(self::CONF_KEY_NOTIFY) ? new NotificationManager($this->getRequest()) : null;
		// Process each rule in order specified
		foreach ($this->opo_config->getAssoc(self::CONF_KEY_RULES) as $va_rule) {
			// Ensure the related model record exists
			$vo_relatedModel = $this->_getRelatedModel($va_rule['related_table'], $va_rule['related_record']);
			if (sizeof($vo_relatedModel->getFieldValuesArray()) > 0) {
				// Determine whether a relationship already exists, and whether the rule matches the source object
				$vn_relationshipId = $this->_getRelationshipId($pa_params['instance'], $va_rule['related_table'], $va_rule['related_record'], $va_rule['relationship_type']);
				$vb_matches = $this->_hasMatch($pa_params, $va_rule);
				// Add relationship where one does not already exist, and the rule matches
				if ($vb_addMatched && $vb_matches && is_null($vn_relationshipId)) {
					$pa_params['instance']->addRelationship($va_rule['related_table'], $va_rule['related_record'], $va_rule['relationship_type']);
					if (!is_null($vo_notifications)) {
						$vo_notifications->addNotification('Automagically added new relationship to ' . $vo_relatedModel->getTypeName() . ' ' . $vo_relatedModel->getListName(), __NOTIFICATION_TYPE_INFO__);
					}
				}
				// Remove relationship where one exists, and the rule does not match
				if ($vb_removeUnmatched && !$vb_matches && !is_null($vn_relationshipId)) {
					$pa_params['instance']->removeRelationship($va_rule['related_table'], $vn_relationshipId);
					if (!is_null($vo_notifications)) {
						$vo_notifications->addNotification('Automagically removed previously extant relationship to ' . $vo_relatedModel->getTypeName() . ' ' . $vo_relatedModel->getListName(), __NOTIFICATION_TYPE_INFO__);
					}
				}
			}
		}
	}

	/**
	 * Retrieve the model object instance based on the given table name and record identifier (primary key or idno).
	 * @param $ps_relatedTable string Name of the related table / model type.
	 * @param $pm_relatedRecord string|int Record identifier, either a number (primary key), string (idno) or array of
	 *   attribute values to match against.
	 * @return object The model instance.
	 */
	protected function _getRelatedModel($ps_relatedTable, $pm_relatedRecord) {
		return new $ps_relatedTable(
			is_string($pm_relatedRecord) ?
				array( 'idno' => $pm_relatedRecord ) :
				$pm_relatedRecord
		);
	}

	/**
	 * Retrieve the relationship id based on the given source instance, related table name, record identifier (primary
	 * key or idno) and relationship type.  Only relationships that match all of these properties will be returned,
	 * unless relationshipType is null, in which case relationships of any type will be returned.  If there are
	 * multiple relationships of the given type, the id of an arbitrarily selected relationship will be returned.
	 *
	 * @param $po_instance BundlableLabelableBaseModelWithAttributes
	 * @param $ps_relatedTable string
	 * @param $pm_relatedRecord string|int
	 * @param $ps_relationshipType string
	 *
	 * @return int|null
	 */
	protected function _getRelationshipId($po_instance, $ps_relatedTable, $pm_relatedRecord, $ps_relationshipType) {
		$va_items = $po_instance->getRelatedItems($ps_relatedTable, array(
			'restrict_to_types' => array( $ps_relatedTable ),
			'restrict_to_relationship_types' => array( $ps_relationshipType ),
			'where' => is_array($pm_relatedRecord) ?
					$pm_relatedRecord : (
					is_string($pm_relatedRecord) ?
						array( 'idno' => $pm_relatedRecord ) :
						array( 'id' => $pm_relatedRecord ))
		));
		return sizeof($va_items) > 0 ? array_keys($va_items)[0] : null;
	}

	/**
	 * Determine whether the given hook parameters (defining the record being saved) match against the given rule.
	 * @param $pa_params array As given to the hook method.
	 * @param $pa_rule array Rule from configuration to test against.
	 * @return bool True if the parameters match against the rule, otherwise false.
	 */
	protected function _hasMatch($pa_params, $pa_rule) {
		$vs_value = caProcessTemplateForIDs($pa_rule['trigger_template'], $pa_params['table_name'], array( $pa_params['id'] ));
		$vb_matches = in_array($pa_params['table_name'], $pa_rule['source_tables']);
		foreach ($pa_rule['trigger_value_patterns'] as $vn_pattern => $vs_pattern) {
			$vb_matches = $vb_matches && preg_match($vs_pattern, $vs_value);
		}
		return $vb_matches;
	}

	static function getRoleActionList() {
		return array();
	}
}
