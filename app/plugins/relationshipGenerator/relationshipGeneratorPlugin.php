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
		if ($this->opo_config->getBoolean('assign_on_insert') && $pa_params['instance'] instanceof BundlableLabelableBaseModelWithAttributes) {
			$this->_process($pa_params);
		}
	}

	public function hookAfterBundleUpdate(&$pa_params) {
		if ($this->opo_config->getBoolean('assign_on_update') && $pa_params['instance'] instanceof BundlableLabelableBaseModelWithAttributes) {
			$this->_process($pa_params);
		}
	}

	private function _process(&$pa_params) {
//		$test = new ca_collections(array('idno' => 'single_individual'));


		foreach ($this->opo_config->getAssoc('triggers') as $vo_trigger) {
			error_log($vo_trigger['related_table']);
			error_log($vo_trigger['related_record']);
			/** @var $vo_relatedModel ca_objects */
			$vo_relatedModel = $this->_getRelatedModel($vo_trigger);
			error_log(print_r($vo_relatedModel->get('collection_id'), true));
//			echo '<pre>'; print_r($vo_relatedModel); exit;
			if (sizeof($vo_relatedModel->getFieldValuesArray()) > 0) {
				error_log('related model exists');
				$vb_hasRelationship = $pa_params['instance']->relationshipExists($vo_trigger['related_table'], $vo_trigger['related_record']);
				$vb_matches = $this->_hasMatch($pa_params, $vo_trigger);
				if (!$vb_hasRelationship && $vb_matches) {
					error_log('***************');
					error_log('adding relationship');
					error_log($vo_trigger['relationship_type']);
					error_log('***************');
					$pa_params['instance']->addRelationship($vo_trigger['related_table'], $vo_trigger['related_record'], $vo_trigger['relationship_type']);
				} elseif ($vb_hasRelationship && !$vb_matches) {
					error_log('removing relationship');
					$pa_params['instance']->removeRelationship($vo_trigger['related_table'], $vo_trigger['related_record'], $vo_trigger['relationship_type']);
				}
			}
		}
	}

	private function _getRelatedModel($po_trigger) {
		$vm_constructorParams = $po_trigger['related_record'];
		if (is_string($vm_constructorParams)) {
			$vm_constructorParams = array( 'idno' => $vm_constructorParams );
		}
		return new $po_trigger['related_table']($vm_constructorParams);
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
