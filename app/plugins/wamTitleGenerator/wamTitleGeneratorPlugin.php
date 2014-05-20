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

	public function hookSaveItem($pa_params) {
		return $this->_rewriteLabel($pa_params);
	}
	# -------------------------------------------------------
	private function _rewriteLabel(&$pa_params) {
		$vs_table_name = $pa_params['table_name'];
		$vn_id = $pa_params['id'];
		$vb_modified_any = false;
		$vn_locale_id = ca_locales::getDefaultCataloguingLocaleID();

		/** @var BundlableLabelableBaseModelWithAttributes $vo_instance */
		$vo_instance = $pa_params['instance'];
		$vo_instance->setMode(ACCESS_WRITE);

		$va_formatters = $this->opo_config->getAssoc('title_formatters');
		if (isset($va_formatters[$vs_table_name]) && isset($va_formatters[$vs_table_name][$vo_instance->getTypeCode()])) {
			$va_templates = $va_formatters[$vs_table_name][$vo_instance->getTypeCode()];
			foreach ($va_templates as $vs_label_field => $ps_template) {
				$vs_new_label_value = caProcessTemplateForIDs($ps_template, $vs_table_name, array( $vn_id ));
				if ($vo_instance->getPreferredLabelCount() > 0) {
					$vs_existing_labels = $vo_instance->getPreferredLabels(array( $vn_locale_id ));
					if (empty($vs_existing_labels) || $vs_new_label_value != $vs_existing_labels[$vn_id][$vn_locale_id][0][$vs_label_field]) {
						$vo_instance->editLabel($vo_instance->getPreferredLabelID($vn_locale_id), array( $vs_label_field => $vs_new_label_value ), $vn_locale_id, null, true);
						$vb_modified_any = true;
					}
				} else {
					$vo_instance->addLabel(array( $vs_label_field => $vs_new_label_value ), $vn_locale_id, null, true);
					$vb_modified_any = true;
				}
			}
		}

		// Only save if we actually changed something, otherwise we will end up in an infinite loop
		if ($vb_modified_any) {
			$vo_instance->update();
		}
		return $pa_params;
	}
	# -------------------------------------------------------
}
