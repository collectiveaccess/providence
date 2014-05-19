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

class wamTitleGeneratorPlugin extends BaseApplicationPlugin {
	# -------------------------------------------------------
	/** @var Configuration */
	private $opo_config;

	private static $opa_processed_records = array();
	# -------------------------------------------------------
	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Generates title based upon rules you specify in the configuration file associated with the plugin');
		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/wamTitleGenerator.conf');
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true - the wamTitleGeneratorPlugin plugin always initializes ok
	 */
	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => ((bool)$this->opo_config->get('enabled'))
		);
	}
	# -------------------------------------------------------
	public static function getRoleActionList(){
		return array();
	}
	# -------------------------------------------------------
	public function hookAfterBundleInsert(&$pa_params) {
		return $this->_rewriteLabel($pa_params);
	}

	public function hookAfterBundleUpdate(&$pa_params) {
		return $this->_rewriteLabel($pa_params);
	}
	# -------------------------------------------------------
	private function _rewriteLabel(&$pa_params) {

		$vs_table_name = $pa_params['table_name'];
		$vn_id = $pa_params['id'];

		if (isset(self::$opa_processed_records[$vs_table_name]) && isset(self::$opa_processed_records[$vs_table_name][$vn_id]) && self::$opa_processed_records[$vs_table_name][$vn_id]) {
			return $pa_params;
		}

		/** @var BundlableLabelableBaseModelWithAttributes $vo_instance */
		$vo_instance = $pa_params['instance'];
		$vo_instance->setMode(ACCESS_WRITE);

		$va_formatters = $this->opo_config->getAssoc('title_formatters');
		if (isset($va_formatters[$vs_table_name]) && isset($va_formatters[$vs_table_name][$vo_instance->getTypeCode()])) {
			$va_templates = $va_formatters[$vs_table_name][$vo_instance->getTypeCode()];
			foreach ($va_templates as $vs_label_type => $ps_template) {
				$vs_new_label = caProcessTemplateForIDs($ps_template, $vs_table_name, array( $vn_id ));
				if ($vo_instance->getPreferredLabelCount() > 0) {
					$vo_instance->removeLabel($vo_instance->getPreferredLabelID(1));
				}
				$vo_instance->addLabel(array( $vs_label_type => $vs_new_label ), 1, null, true);
			}
		}

		if (!isset(self::$opa_processed_records[$vs_table_name])) {
			self::$opa_processed_records[$vs_table_name] = array();
		}
		self::$opa_processed_records[$vs_table_name][$vn_id] = true;

		$vo_instance->update();
		return $pa_params;
	}
	# -------------------------------------------------------
}
