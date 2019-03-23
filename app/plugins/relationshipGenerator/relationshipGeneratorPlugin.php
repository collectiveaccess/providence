<?php
/* ----------------------------------------------------------------------
 * relationshipGeneratorPlugin.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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
 * Each rule specifies the table(s) (i.e. model type(s)) that the relationship will be created for, and a set of
 * triggers.  Each trigger specifies a field, a match type, and some match type-specific criteria (see below).  Each
 * rule also specifies the target table and record identifier (primary key value or idno), plus the relationship type,
 * which defines the type of relationship to be managed.
 *
 * The plugin combines triggers for multiple fields according to the configured `default_field_combination_operator`,
 * or a per-rule `field_combination_operator` override.  Similarly the plugin combines matches multiple values from
 * trigger fields (where a field for a given record has more than one value) using a per-rule or per-trigger
 * `value_combination_operator`, falling back to a global `default_value_combination_operator` where no override is
 * given.  Finally, the match types can be globally set using `default_match_type`, and then overridden per-rule and
 * per-trigger with a `match_type` setting.  The combination of configurable match types and combination operators
 * gives the ability to generate (and remove) relationships based on a wide range of criteria.
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
 * `notify` config item to 0.  The notification text can be overridden using the `default_add_relationship_notification`
 * and `default_remove_relationship_notification` config items for all rules, or `add_relationship_notification` and
 * `remove_relationship_notification` per-rule settings.
 */
class relationshipGeneratorPlugin extends BaseApplicationPlugin {

	/** @var Configuration */
	private $opo_config;

	/** @var NotificationManager */
	private $opo_notifications;

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Automatically assigns an object to a collection, based upon rules you specify in the configuration file associated with the plugin');
		$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'relationshipGenerator.conf');
	}

	public function checkStatus() {
		$va_errors = array();
		$vo_config = $this->opo_config;
		$this->_testConfigurationSection(
			_t('top level'),
			self::_getTopLevelConfigurationRequirements(),
			function ($key) use ($vo_config) { return $vo_config->get($key); },
			$va_errors
		);
		$va_rules = $this->opo_config->get('rules');
		if (is_array($va_rules) && !empty($va_rules)) {
			foreach ($va_rules as $vn_rule_index => $va_rule) {
				$this->_testConfigurationSection(
					_t('rule %1', $vn_rule_index),
					self::_getRuleConfigurationRequirements(),
					function ($key) use ($va_rule) { return $va_rule[$key]; },
					$va_errors
				);
				$va_triggers = isset($va_rule['triggers']) ? $va_rule['triggers'] : null;
				if (is_array($va_triggers) && !empty($va_triggers)) {
					foreach ($va_triggers as $vs_trigger_field => $va_trigger) {
						$this->_testConfigurationSection(
							_t('trigger field %1 on rule %2', $vs_trigger_field, $vn_rule_index),
							self::_getTriggerConfigurationRequirements(),
							function ($key) use ($va_trigger) { return $va_trigger[$key]; },
							$va_errors
						);
					}
				}
			}
		}
		return array(
			'description' => $this->getDescription(),
			'errors' => $va_errors,
			'warnings' => array(),
			'available' => (bool)$this->opo_config->getBoolean('enabled')
		);
	}

	public function hookAfterBundleInsert(&$pa_params) {
		if ($this->opo_config->getBoolean('process_on_insert') && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
		return $pa_params;
	}

	public function hookAfterBundleUpdate(&$pa_params) {
		if ($this->opo_config->getBoolean('process_on_update') && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
		return $pa_params;
	}

	/**
	 * Determine if the given object is an appropriate model to process; specifically exclude relationships as it leads
	 * to infinite recursion.
	 *
	 * @param $po_instance object
	 *
	 * @return bool True if the parameter object is the relevant type of model (i.e. bundlable and labelable, but not
	 *   a relationship model), otherwise false.
	 */
	protected static function _isRelevantInstance($po_instance) {
		return ($po_instance instanceof BundlableLabelableBaseModelWithAttributes)
			&& !($po_instance instanceof BaseRelationshipModel);
	}

	/**
	 * Main processing method, both hooks (insert and update) delegate to this.
	 *
	 * @param $pa_params array As given to the hook method.
	 */
	protected function _process(&$pa_params) {
		/** @var BundlableLabelableBaseModelWithAttributes $vo_instance */
		$vo_instance = $pa_params['instance'];

		// Configuration items used multiple times
		$vb_add_matched = $this->opo_config->getBoolean('add_matched');
		$vb_remove_unmatched = $this->opo_config->getBoolean('remove_unmatched');

		// Process each rule in order specified
		foreach ($this->opo_config->getAssoc('rules') as $va_rule) {
			$vs_related_table = $va_rule['related_table'];
			$vm_related_record = $va_rule['related_record'];
			$vs_relationship_type = $va_rule['relationship_type'];

			// Ensure the related model record exists
			/** @var BundlableLabelableBaseModelWithAttributes $vo_related_model */
			$vo_related_model = new $vs_related_table(is_string($vm_related_record) && !is_numeric($vm_related_record) ? array( 'idno' => $vm_related_record ) : $vm_related_record);
			if (sizeof($vo_related_model->getFieldValuesArray()) > 0) {
				// Determine whether a relationship already exists, and whether the rule matches the source object
				$vn_relationship_id = self::_getRelationshipId($pa_params['instance'], $vs_related_table, $vm_related_record, $vs_relationship_type);
				$vb_matches = $this->_hasMatch($pa_params, $va_rule);

				// Add relationship where one does not already exist, and the rule matches
				if ($vb_add_matched && $vb_matches && is_null($vn_relationship_id)) {
					$vo_instance->addRelationship($vs_related_table, $vm_related_record, $vs_relationship_type);
					if ($this->opo_config->getBoolean('notify')) {
						$this->_notifications()->addNotification(
							_t(
								isset($va_rule['add_relationship_notification']) ? $va_rule['add_relationship_notification'] : $this->opo_config->get('default_add_relationship_notification'),
								$vo_related_model->getTypeName(),
								$vo_related_model->getListName()
							),
							__NOTIFICATION_TYPE_INFO__
						);
					}
				}

				// Remove relationship where one exists, and the rule does not match
				if ($vb_remove_unmatched && !$vb_matches && !is_null($vn_relationship_id)) {
					$vo_instance->removeRelationship($vs_related_table, $vn_relationship_id);
					if ($this->opo_config->getBoolean('notify')) {
						$this->_notifications()->addNotification(
							_t(
								isset($va_rule['remove_relationship_notification']) ? $va_rule['remove_relationship_notification'] : $this->opo_config->get('default_remove_relationship_notification'),
								$vo_related_model->getTypeName(),
								$vo_related_model->getListName()
							),
							__NOTIFICATION_TYPE_INFO__
						);
					}
				}
			}
		}
	}

	/**
	 * @return NotificationManager|null Note this function returns null if `notify` is off in configuration
	 */
	private function _notifications() {
		if (!$this->opo_notifications && $this->opo_config->getBoolean('notify')) {
			$this->opo_notifications = new NotificationManager($this->getRequest());
		}
		return $this->opo_notifications;
	}

	/**
	 * Determine whether the given hook parameters (defining the record being saved) match against the given rule.
	 *
	 * @param $pa_params array As given to the hook method.
	 * @param $pa_rule array Rule from configuration to test against.
	 *
	 * @return bool True if the parameters match against the rule, otherwise false.
	 */
	protected function _hasMatch($pa_params, $pa_rule) {
		// Skip tables that aren't relevant to this rule
		if (!in_array($pa_params['table_name'], $pa_rule['source_tables'])) {
			return false;
		}

		// Settings for the rule, falling back to top-level defaults if not configured
		$vs_field_combination_operator = self::_getOperatorMethodName(isset($pa_rule['field_combination_operator']) ? $pa_rule['field_combination_operator'] : $this->opo_config->get('default_field_combination_operator'));
		$vs_default_value_combination_operator = self::_getOperatorMethodName(isset($pa_rule['value_combination_operator']) ? $pa_rule['value_combination_operator'] : $this->opo_config->get('default_value_combination_operator'));
		$vs_default_match_type = self::_getMatchTypeMethodName(isset($pa_rule['match_type']) ? $pa_rule['match_type'] : $this->opo_config->get('default_match_type'));
		$va_default_match_options = isset($pa_rule['match_options']) ? $pa_rule['match_options'] : $this->opo_config->get('default_match_options');

		$vb_matches = self::$vs_field_combination_operator();
		foreach ($pa_rule['triggers'] as $vs_field => $va_trigger) {
			// Settings for the trigger, falling back to defaults if not specified
			$va_trigger = array_merge($va_default_match_options, $va_trigger);
			$vs_value_combination_operator = isset($va_trigger['value_combination_operator']) ? self::_getOperatorMethodName($va_trigger['value_combination_operator']) : $vs_default_value_combination_operator;
			$vs_match_type = isset($va_trigger['match_type']) ? self::_getMatchTypeMethodName($va_trigger['match_type']) : $vs_default_match_type;
			$vs_value_converter = isset($va_trigger['value_converter']) && function_exists($va_trigger['value_converter']) ? $va_trigger['value_converter'] : null;

			// Track match status
			$vb_field_matches = self::$vs_value_combination_operator();
			foreach (self::_getValues($pa_params['table_name'], $pa_params['id'], $vs_field) as $vm_value) {
				if (!is_null($vs_value_converter)) {
					$vm_value = call_user_func($vs_value_converter, $vm_value);
				}
				$vb_field_matches = self::$vs_value_combination_operator($vb_field_matches, self::$vs_match_type($vm_value, $va_trigger));
			}
			$vb_matches = self::$vs_field_combination_operator($vb_matches, $vb_field_matches);
		}
		return $vb_matches;
	}

	/**
	 * Retrieve the relationship id based on the given source instance, related table name, record identifier (primary
	 * key or idno) and relationship type.  Only relationships that match all of these properties will be returned,
	 * unless relationshipType is null, in which case relationships of any type will be returned.  If there are
	 * multiple relationships of the given type, the id of an arbitrarily selected relationship will be returned.
	 *
	 * @param $po_instance BundlableLabelableBaseModelWithAttributes
	 * @param $ps_related_table string
	 * @param $pm_related_record string|int
	 * @param $ps_relationship_type string
	 *
	 * @return int|null
	 */
	protected static function _getRelationshipId($po_instance, $ps_related_table, $pm_related_record, $ps_relationship_type) {
		$va_items = $po_instance->getRelatedItems($ps_related_table, array(
			'restrict_to_types' => array( $ps_related_table ),
			'restrict_to_relationship_types' => array( $ps_relationship_type ),
			'where' => is_array($pm_related_record) ?
					$pm_related_record : (
					is_string($pm_related_record) ?
						array( 'idno' => $pm_related_record ) :
						array( 'id' => $pm_related_record ))
		));
		//$va_keys = is_array($va_items) ? array_keys($va_items) : null;
		
		$va_keys = array();
		foreach($va_items as $va_item) {
			$va_keys[] = $va_item[$va_item['_key']];
		}
		
		return sizeof($va_keys) > 0 ? $va_keys[0] : null;
	}

	/**
	 * Get the internal operator method name for the given operator.
	 *
	 * @param $ps_operator string
	 *
	 * @return string
	 */
	protected static function _getOperatorMethodName($ps_operator) {
		return '_' . str_replace(' ', '', lcfirst(ucwords($ps_operator))) . 'Operator';
	}

	/**
	 * Get the internal match method name for the given match type.
	 *
	 * @param $ps_match_type string
	 *
	 * @return string
	 */
	protected static function _getMatchTypeMethodName($ps_match_type) {
		return '_' . str_replace(' ', '', lcfirst(ucwords($ps_match_type))) . 'Match';
	}

	/**
	 * Get array of values for the given field from the given table's record with the given id.
	 *
	 * @param $ps_table string
	 * @param $pn_id int
	 * @param $ps_field string
	 *
	 * @return array
	 */
	protected static function _getValues($ps_table, $pn_id, $ps_field) {
		$va_result = array();
		/** @var BundlableLabelableBaseModelWithAttributes $vo_object */
		$vo_object = new $ps_table($pn_id);
		$va_values = $vo_object->get($ps_field, array( 'returnAsArray' => true ));
		if (is_array($va_values)) {
			foreach ($va_values as $va_v) {
				$va_result = array_merge($va_result, is_array($va_v) ? $va_v : array( $va_v ));
			}
		}
		return $va_result;
	}

	/**
	 * Operator method implementing "and" operator.
	 *
	 * @param $a null|bool
	 * @param $b null|bool
	 *
	 * @return bool
	 */
	protected static function _andOperator($a = null, $b = null) {
		return (is_null($a) || is_null($b)) ? true : $a && $b;
	}

	/**
	 * Operator method implementing "or" operator.
	 *
	 * @param $a null|bool
	 * @param $b null|bool
	 *
	 * @return bool
	 */
	protected static function _orOperator($a = null, $b = null) {
		return (is_null($a) || is_null($b)) ? false : $a || $b;
	}

	/**
	 * Match method implementing "regex" match method.
	 *
	 * @param $pm_value mixed
	 * @param $pa_trigger array
	 *
	 * @return bool
	 */
	protected static function _regexMatch($pm_value, $pa_trigger) {
		$vs_modifiers = $pa_trigger['case_insensitive'] ? 'i' : '';
		$vb_match = false;
		foreach ($pa_trigger['regexes'] as $vs_pattern) {
			$vs_escaped_pattern = str_replace('/', '\\/', $vs_pattern);
			$vb_match = $vb_match || preg_match('/' . $vs_escaped_pattern . '/' . $vs_modifiers, strval($pm_value));
		}
		return $vb_match;
	}

	/**
	 * Match method implementing "exact" match method.
	 *
	 * @param $pm_value mixed
	 * @param $pa_trigger array
	 *
	 * @return bool
	 */
	protected static function _exactMatch($pm_value, $pa_trigger) {
		return strcmp($pa_trigger['value'], strval($pm_value)) === 0;
	}

	/**
	 * Match method implementing "caseInsensitive" match method.
	 *
	 * @param $pm_value mixed
	 * @param $pa_trigger array
	 *
	 * @return bool
	 */
	protected static function _caseInsensitiveMatch($pm_value, $pa_trigger) {
		return strcasecmp($pa_trigger['value'], strval($pm_value)) === 0;
	}

	/**
	 * @return array Definition of requirements for top-level configuration items
	 */
	private static function _getTopLevelConfigurationRequirements() {
		return array(
			'default_field_combination_operator' => array(
				'required' => true,
				'type' => 'string',
				'call' => 'operator'
			),
			'default_value_combination_operator' => array(
				'required' => true,
				'type' => 'string',
				'call' => 'operator'
			),
			'default_match_type' => array(
				'required' => true,
				'type' => 'string',
				'call' => 'match type'
			),
			'default_match_options' => array(
				'required' => true,
				'type' => 'array'
			),
			'rules' => array(
				'required' => true,
				'type' => 'array'
			)
		);
	}

	/**
	 * @return array Definition of requirements for per-rule configuration items
	 */
	private static function _getRuleConfigurationRequirements() {
		return array(
			'source_tables' => array(
				'required' => true,
				'type' => 'array'
			),
			'triggers' => array(
				'required' => true,
				'type' => 'array'
			),
			'related_table' => array(
				'required' => true,
				'type' => 'string'
			),
			'related_record' => array(
				'required' => true
			),
			'relationship_type' => array(
				'required' => true,
				'type' => 'string'
			),
			'field_combination_operator' => array(
				'type' => 'string',
				'call' => 'operator'
			),
			'value_combination_operator' => array(
				'type' => 'string',
				'call' => 'operator'
			),
			'match_type' => array(
				'type' => 'string',
				'call' => 'match type'
			)
		);
	}

	/**
	 * @return array Definition of requirements for per-trigger configuration items
	 */
	private static function _getTriggerConfigurationRequirements() {
		return array(
			'value_combination_operator' => array(
				'type' => 'string',
				'call' => 'operator'
			),
			'match_type' => array(
				'type' => 'string',
				'call' => 'match type'
			)
		);
	}

	/**
	 * Test the given configuration section against the given requirements.
	 * @param $ps_section string Name of the section, for error messages
	 * @param $pa_requirements array Key-value array of requirements, keys are passed to the given callback
	 * @param $pf_get_value_callback callback Callback which returns a value for a given key
	 * @param $pa_errors array By-reference array of errors to append to
	 */
	private function _testConfigurationSection($ps_section, $pa_requirements, $pf_get_value_callback, &$pa_errors) {
		foreach ($pa_requirements as $key => $requirement) {
			$value = $pf_get_value_callback($key);
			if (isset($requirement['required']) && $requirement['required'] && is_null($value)) {
				$pa_errors[] = _t('Required configuration item `%1` missing from %2', $key, $ps_section);
			}
			if (isset($requirement['type']) && is_string($requirement['type']) && !is_null($value) && gettype($value) !== $requirement['type']) {
				$pa_errors[] = _t('Configuration item `%1` in %2 has incorrect type %3, expected %4', $key, $ps_section, gettype($value), $requirement['type']);
			}
			if (isset($requirement['call']) && is_string($requirement['call']) && !is_null($value)) {
				$call = '_get' . str_replace(' ', '', ucwords($requirement['call'])) . 'MethodName';
				if (!method_exists($this, $this->$call($value))) {
					$pa_errors[] = _t('Configuration item `%1` in %2 has value "%3", which is an invalid %4', $key, $ps_section, $value, $requirement['call']);
				}
			}
		}
	}
}
