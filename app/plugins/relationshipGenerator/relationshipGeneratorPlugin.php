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
		$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'wamRelationshipGenerator.conf');
	}

	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => (bool)$this->opo_config->get('enabled')
		);
	}

	public function hookBeforeBundleInsert(&$pa_params) {
		if ($this->opo_config->getBoolean('assign_on_insert') && $pa_params['instance'] instanceof BundlableLabelableBaseModelWithAttributes) {
			$this->_process($pa_params);
		}
	}

	public function hookBeforeBundleUpdate(&$pa_params) {
		if ($this->opo_config->getBoolean('assign_on_update') && $pa_params['instance'] instanceof BundlableLabelableBaseModelWithAttributes) {
			$this->_process($pa_params);
		}
	}

	private function _process(&$pa_params) {
		foreach ($this->opo_config->getAssoc('triggers') as $vo_trigger) {
			$vb_hasRelationship = $pa_params['instance']->relationshipExists($vo_trigger['related_table'], $vo_trigger['related_record']);
			$vb_matches = in_array($pa_params['table_name'], $vo_trigger['source_tables']) && in_array(caProcessTemplateForIDs($vo_trigger['trigger_template'], $pa_params['table_name'], array( $pa_params['id'] )), $vo_trigger['trigger_values']);
			if (!$vb_hasRelationship && $vb_matches) {
				$pa_params['instance']->addRelationship($vo_trigger['related_table'], $vo_trigger['related_record'], $vo_trigger['relationship_type']);
			} elseif ($vb_hasRelationship && !$vb_matches) {
				$pa_params['instance']->removeRelationship($vo_trigger['related_table'], $vo_trigger['related_record'], $vo_trigger['relationship_type']);
			}
		}
	}

	static function getRoleActionList() {
		return array();
	}
}
