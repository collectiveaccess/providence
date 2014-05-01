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

require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

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
		if ($this->opo_config->getBoolean('assign_on_insert') && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
	}

	public function hookAfterBundleUpdate(&$pa_params) {
		if ($this->opo_config->getBoolean('assign_on_update') && $this->_isRelevantInstance($pa_params['instance'])) {
			$this->_process($pa_params);
		}
	}

	private function _isRelevantInstance($po_instance) {
		// Only operate on the appropriate models; specifically exclude relationships as it leads to infinite recursion.
		return ($po_instance instanceof BundlableLabelableBaseModelWithAttributes) && !($po_instance instanceof BaseRelationshipModel);
	}

	private function _process(&$pa_params) {
		foreach ($this->opo_config->getAssoc('triggers') as $vo_trigger) {
			/** @var $vo_instance BundlableLabelableBaseModelWithAttributes */
			$vo_instance = $pa_params['instance'];
			// Ensure the related record actually exists (i.e. skip over bad config)
			/** @var $vo_relatedModel BundlableLabelableBaseModelWithAttributes */
			$vo_relatedModel = $this->_getRelatedModel($vo_trigger['related_table'], $vo_trigger['related_record']);
			if (sizeof($vo_relatedModel->getFieldValuesArray()) > 0) {
				/** @var BaseRelationshipModel $vo_relationship */
				$vo_relationship = $vo_instance->getRelationshipInstance($vo_trigger['related_table']);
				$vb_hasRelationship = sizeof($vo_relationship->getFieldValuesArray()) > 0;
				$vb_matches = $this->_hasMatch($pa_params, $vo_trigger);

//				error_log($vo_trigger['related_table'] . '/' . $vo_trigger['related_record']);
//				error_log('relationship = ' . print_r($vo_relationship->getFieldValuesArray(), true));
//				error_log('hasRelationship = ' . $vb_hasRelationship);
//				error_log('matches = ' . $vb_matches);
//				error_log('-------------');

				if (!$vb_hasRelationship && $vb_matches) {
					error_log('we are adding a relationship');
					$vo_instance->addRelationship($vo_trigger['related_table'], $vo_trigger['related_record'], $vo_trigger['relationship_type']);
				} elseif ($vb_hasRelationship && !$vb_matches) {
					error_log('we are removing a relationship');
					$vo_instance->removeRelationship($vo_trigger['related_table'], $vo_relationship->getPrimaryKey());
				}
			}
		}
	}

	private function _getRelatedModel($pm_relatedTable, $pm_relatedRecord) {
		return new $pm_relatedTable(is_string($pm_relatedRecord) ? array( 'idno' => $pm_relatedRecord ) : $pm_relatedRecord);
	}

	private function _hasMatch($pa_params, $po_trigger) {
		$vs_value = caProcessTemplateForIDs($po_trigger['trigger_template'], $pa_params['table_name'], array( $pa_params['id'] ));
		$vb_matches = in_array($pa_params['table_name'], $po_trigger['source_tables']);
		foreach ($po_trigger['trigger_value_patterns'] as $vs_pattern) {
			$vb_matches = $vb_matches && preg_match($vs_pattern, $vs_value);
		}
		return $vb_matches;
	}

	static function getRoleActionList() {
		return array();
	}
}
