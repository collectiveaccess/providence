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

/**
 * The WAM Title Generator plugin is a configurable title generator, which uses templates to create titles (preferred
 * labels) for any type of bundle containing objects of any type.
 *
 * The top-level configuration item `enabled` flag switches the plugin on or off.
 *
 * The only other top-level configuration item is `title_generators`, which maps to a hash where the keys are record
 * types (e.g. ca_objects, ca_collections) and the values are also hashes.  The hashes in these child keys are ignored,
 * they are only hashes instead of lists due to a limitation of the CA configuration module.  The values of these
 * hashes are also hashes, which contain a `types` key and a `templates` key.  The `types` key is mapped to a list of
 * type codes (idno values) to which the templates are applied.  The `templates` key maps to a hash where the keys are
 * the field names and the values are templates used to determine the new value for those fields.
 */
class wamTitleGeneratorPlugin extends BaseApplicationPlugin {

	/** @var Configuration */
	private $opo_config;

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Generates title based upon rules you specify in the configuration file associated with the plugin');
		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/wamTitleGenerator.conf');
	}

	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => ((bool)$this->opo_config->get('enabled'))
		);
	}

	public static function getRoleActionList(){
		return array();
	}

	public function hookSaveItem($pa_params) {
		return $this->_rewriteLabel($pa_params);
	}

	public function hookAfterBundleInsert($pa_params) {
		return $this->_rewriteLabel($pa_params);
	}

	public function hookAfterBundleUpdate($pa_params) {
		return $this->_rewriteLabel($pa_params);
	}

	private function _rewriteLabel(&$pa_params) {
		$vs_table_name = $pa_params['table_name'];
		$vn_id = $pa_params['id'];
		$vb_modified_any = false;
		$vn_locale_id = ca_locales::getDefaultCataloguingLocaleID();

		/** @var BundlableLabelableBaseModelWithAttributes $vo_instance */
		$vo_instance = $pa_params['instance'];
		$vo_instance->setMode(ACCESS_WRITE);

		// Iterate through formatters until we get a match on table name and type code, then process
		$va_formatters = $this->opo_config->getAssoc('title_formatters');
		if (isset($va_formatters[$vs_table_name])) {
			foreach ($va_formatters[$vs_table_name] as $va_formatters_for_table) {
				if (in_array($vo_instance->getTypeCode(), $va_formatters_for_table['types'])) {
					$va_new_labels = array();
					foreach ($va_formatters_for_table['templates'] as $vs_label_field => $ps_template) {
						// Determine whether to edit an existing label or create a new label
						$vs_new_label_value = caProcessTemplateForIDs($ps_template, $vs_table_name, array( $vn_id ));
						$va_bounds = $vo_instance->getLabelTableInstance()->getFieldInfo($vs_label_field, 'BOUNDS_LENGTH');
						if(isset($va_bounds[1]) && $va_bounds[1]){
							$vs_new_label_value = mb_substr($vs_new_label_value, 0, $va_bounds[1]);
						}
						$va_new_labels[$vs_label_field] = $vs_new_label_value;
					}
					if ($vo_instance->getPreferredLabelCount() > 0 && $va_new_labels) {
						$va_existing_labels = $vo_instance->getPreferredLabels(array( $vn_locale_id ));
						$vb_edit_label = false;
						if (empty($va_existing_labels)){
							$vb_edit_label = true;
						} else {
							foreach($va_new_labels as $vs_label_field => $vs_new_label_value){
								if($vs_new_label_value !== $va_existing_labels[$vn_id][$vn_locale_id][0][$vs_label_field]){
									$vb_edit_label = true;
								}
							}
						}
						if ($vb_edit_label){
							$vo_instance->editLabel($vo_instance->getPreferredLabelID($vn_locale_id), $va_new_labels, $vn_locale_id, null, true);
							$vb_modified_any = true;
						}
					} else {
						$vo_instance->addLabel($va_new_labels, $vn_locale_id, null, true);
						$vb_modified_any = true;
					}
				}
			}
		}

		// Only save if we actually changed something, otherwise we will end up in an infinite loop
		if ($vb_modified_any) {
			$vo_instance->update();
		}
		return $pa_params;
	}
}
