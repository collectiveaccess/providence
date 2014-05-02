<?php
/* ----------------------------------------------------------------------
 * wamTitleGeneratorPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 * The relationship generator plugin uses a set of configurable rules to automatically manage relationships for any
 * type of model that extends BundlableLabelableBaseModelWithAttributes, but does not extend BaseRelationshipModel
 * (relationships cannot be created from other relationships).
 *
 * Each rule specifies the table(s) (i.e. model type(s)) that the relationship will be created for, a template which
 * extracts a "value" from the record being saved, and a set of regular expressions that, if the value matches against,
 * will cause a "match" on the given rule.  Each rule also specifies the target table and record identifier (primary
 * key value or idno), plus the relationship type, which defines the relationship to be created.
 *
 * If the plugin detects a relationship that does not exist for a record being saved, but should exist according to the
 * defined rules, it will create the relationship.  This behaviour can be disabled by setting the `add_matched` config
 * item to 0.
 *
 * If the plugin detects a relationship that exists for a record being saved, but should not exist according to the
 * defined rules, it will remove the relationship.  This behaviour can be disabled by setting the `remove_unmatched`
 * config item to 0.
 *
 * The plugin will run on both initial creation of the record, and modification of an existing record.  These behaviours
 * can be disabled individually by setting the `process_on_insert` and `process_on_update` config items to 0.
 */
class relationshipGeneratorPlugin extends BaseApplicationPlugin {

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
			'available' => (bool)$this->opo_config->getBoolean('enabled')
		);
	}

	public function hookAfterBundleInsert(&$pa_params) {
		if ($this->opo_config->getBoolean('process_on_insert') && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
	}

	public function hookAfterBundleUpdate(&$pa_params) {
		if ($this->opo_config->getBoolean('process_on_update') && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
	}

	/**
	 * Only operate on the appropriate models; specifically exclude relationships as it leads to infinite recursion.
	 * @param $po_instance object
	 * @return bool True if the parameter object is the relevant type of model (i.e. bundlable and labelable, but not
	 *   a relationship model), otherwise false.
	 */
	private function _isRelevantInstance($po_instance) {
		return ($po_instance instanceof BundlableLabelableBaseModelWithAttributes) && !($po_instance instanceof BaseRelationshipModel);
	}

	/**
	 * Main processing method, both hooks (insert and update) delegate to this.
	 * @param $pa_params array As given to the hook method.
	 */
	private function _process(&$pa_params) {
		foreach ($this->opo_config->getAssoc('rules') as $va_rule) {
			/** @var $vo_instance BundlableLabelableBaseModelWithAttributes */
			$vo_instance = $pa_params['instance'];
			// Ensure the related record actually exists (i.e. skip over bad config)
			/** @var $vo_relatedModel BundlableLabelableBaseModelWithAttributes */
			$vo_relatedModel = $this->_getRelatedModel($va_rule['related_table'], $va_rule['related_record']);
			if (sizeof($vo_relatedModel->getFieldValuesArray()) > 0) {
				/** @var BaseRelationshipModel $vo_relationship */
				$vo_relationship = $vo_instance->getRelationshipInstance($va_rule['related_table']);
				$vb_hasRelationship = sizeof($vo_relationship->getFieldValuesArray()) > 0;
				$vb_matches = $this->_hasMatch($pa_params, $va_rule);

//				error_log($vo_rule['related_table'] . '/' . $vo_rule['related_record']);
//				error_log('relationship = ' . print_r($vo_relationship->getFieldValuesArray(), true));
//				error_log('hasRelationship = ' . $vb_hasRelationship);
//				error_log('matches = ' . $vb_matches);
//				error_log('-------------');

				if (!$vb_hasRelationship && $vb_matches && $this->opo_config->getBoolean('add_matched')) {
					error_log('we are adding a relationship');
					$vo_instance->addRelationship($va_rule['related_table'], $va_rule['related_record'], $va_rule['relationship_type']);
				} elseif ($vb_hasRelationship && !$vb_matches && $this->opo_config->getBoolean('remove_unmatched')) {
					error_log('we are removing a relationship');
					$vo_instance->removeRelationship($va_rule['related_table'], $vo_relationship->getPrimaryKey());
				}
			}
		}
	}

	/**
	 * Retrieve the model object instance based on the given table name and record identifier (primary key or idno).
	 * @param $ps_relatedTable string Name of the related table / model type.
	 * @param $pm_relatedRecord mixed Record identifier, either a number (primary key), string (idno) or array of
	 *   attribute values to match against.
	 * @return object The model instance.
	 */
	private function _getRelatedModel($ps_relatedTable, $pm_relatedRecord) {
		return new $ps_relatedTable(is_string($pm_relatedRecord) ? array( 'idno' => $pm_relatedRecord ) : $pm_relatedRecord);
	}

	/**
	 * Determine whether the given hook parameters (defining the record being saved) match against the given rule.
	 * @param $pa_params array As given to the hook method.
	 * @param $pa_rule array Rule from configuration to test against.
	 * @return bool True if the parameters match against the rule, otherwise false.
	 */
	private function _hasMatch($pa_params, $pa_rule) {
		$vs_value = caProcessTemplateForIDs($pa_rule['trigger_template'], $pa_params['table_name'], array( $pa_params['id'] ));
		$vb_matches = in_array($pa_params['table_name'], $pa_rule['source_tables']);
		foreach ($pa_rule['trigger_value_patterns'] as $vs_pattern) {
			$vb_matches = $vb_matches && preg_match($vs_pattern, $vs_value);
		}
		return $vb_matches;
	}

	static function getRoleActionList() {
		return array();
	}
}
