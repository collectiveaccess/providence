<?php
/* ----------------------------------------------------------------------
 * wamDataImporterPlugin.php : 
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

/**
 * The WAM Data Importer performs various tasks relating to the import of records
 */
class wamDataImporterPlugin extends BaseApplicationPlugin {

	/** @var Configuration */
	private $opo_config;

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Performs tasks relating to data import');
		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/wamDataImporter.conf');
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

	/**
	 * Hook into the content tree import
	 * @param $pa_params array with the following keys:
	 * 'content_tree' => &$va_content_tree.
	 * 'idno' => &$vs_idno
	 * 'transaction' => &$o_trans
	 * 'log' => &$o_log
	 * 'reader' => $o_reader
	 * 'environment' => $va_environment
	 * @return array
	 */
	public function hookDataImportContentTree(&$pa_params){
		foreach ($pa_params['content_tree'] as $vs_table_name => $va_table_content_tree) {

			foreach ($va_table_content_tree as $vs_table_content_index => $va_table_content) {
				$this->_processBundles($pa_params, $va_table_content, $vs_table_name, $vs_table_content_index);
				if(isset($va_table_content['_interstitial'])){
					$this->_processBundles($pa_params, $va_table_content, $vs_table_name, $vs_table_content_index, '_interstitial');
				}
			}
		}

		return $pa_params;
	}

	/**
	 * @param $pa_params array parameters passed to the data importer plugin
	 * @param $pa_table_content
	 * @param $ps_table_name string the target table name
	 * @param $pn_table_content_index int
	 * @param null/string $ps_root_element the root of the table content array to process
	 * @return array
	 */
	protected function _processBundles(&$pa_params, $pa_table_content, $ps_table_name, $pn_table_content_index, $ps_root_element = null) {
		/** @var KLogger $o_log */
		$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
		$o_import_event = caGetOption('importEvent', $pa_params, null);
		$va_event_source = caGetOption('importEventSource', $pa_params, null);
		// intertitials have their values stored under an _interstitials key, whereas ordinary attributes have them stored under the root.
		$va_table_content = isset($ps_root_element) && isset($pa_table_content[$ps_root_element]) ? $pa_table_content[$ps_root_element] : $pa_table_content;

		if (isset($va_table_content) && isset($va_table_content['_translations'])) {
			// Apply all translations
			foreach ($va_table_content['_translations'] as $vs_name => $vs_translation_settings_json) {

				$va_translation_settings = json_decode($vs_translation_settings_json, true);


				if(json_last_error() !== JSON_ERROR_NONE){
					if($o_log){
						$o_log->logError(sprintf('Failed to parse JSON string "%s" while proccessing "%s" for idno "%s"', $vs_translation_settings_json,  $vs_name, $pa_params['idno']));
					}
					continue;
				}
				if (isset($va_table_content[$vs_name])) {
					if (isset($va_translation_settings['delimiters'])) {
						// Ensure we have an array
						if (!is_array($va_translation_settings['delimiters'])) {
							$va_translation_settings['delimiters'] = array($va_translation_settings['delimiters']);
						}
						// Quote the delimiters for preg_split
						$va_translation_settings['delimiters'] = array_map(
								function ($delimiter) {
									return preg_quote($delimiter, '!');
								},
								$va_translation_settings['delimiters']
						);
						// Split the value based on given delimiters
						$va_table_content[$vs_name] = preg_split("!(" . join("|", $va_translation_settings['delimiters']) . ")!", $va_table_content[$vs_name]);
					}
					foreach ($va_table_content[$vs_name] as $value_index => $value) {
						if(isset($va_translation_settings['element']) && $va_translation_settings['element'] !== $value_index){
							//We only want to set the value of a specific element therefore we can skip this value
							continue;
						}
						switch ($va_translation_settings['table']) {
							case 'ca_entities':
								$this->_processEntityBundle($va_table_content, $va_translation_settings, $value, $vs_name, $value_index, $o_import_event, $va_event_source);
								break;
							case 'ca_list_items':
								$this->_processListItemBundle($va_table_content, $va_translation_settings, $value, $vs_name, $value_index, $o_import_event, $va_event_source, $pa_params, $o_log);
								break;

							default:
								if ($o_log) {
									$o_log->logError(sprintf('Unknown "%s" translation type "%s" on "%s" for idno "%s"', $va_translation_settings['table'], $vs_name, $pa_params['idno']));
								}
						}
					}
				} else {
					if ($o_log) {
						$o_log->logError(sprintf('Unknown "%s" name "%s" specified in translation of type "%s" for idno "%s"',  $vs_name, $va_translation_settings['table'], $pa_params['idno']));
					}
				}
			}

			// Save the translated value back into the content tree
			if(isset($ps_root_element)){
				$pa_params['content_tree'][$ps_table_name][$pn_table_content_index][$ps_root_element] = $va_table_content;
				// Remove translations special key so it is not added as an interstitial
				unset($pa_params['content_tree'][$ps_table_name][$pn_table_content_index][$ps_root_element]['_translations']);
			} else {
				$pa_params['content_tree'][$ps_table_name][$pn_table_content_index] = $va_table_content;
				// Remove translations special key so it is not added as an interstitial
				unset($pa_params['content_tree'][$ps_table_name][$pn_table_content_index]['_translations']);
			}
		}
	}

	/**
	 * @param $table_content array
	 * @param $translation_settings array
	 * @param $value mixed
	 * @param $vs_name string name
	 * @param $value_index int
	 * @param null/ca_data_import_events $po_import_event
	 * @param null/array $pa_import_event_source
	 */
	protected function _processEntityBundle(&$table_content, $translation_settings, $value, $vs_name, $value_index, $po_import_event = null, $pa_import_event_source = null) {
		global $g_ui_locale_id;
		$vs_entity_type = caGetOption('entityType', $translation_settings, 'ind');
		$table_content[$vs_name][$value_index] = DataMigrationUtils::getEntityID(
				DataMigrationUtils::splitEntityName($value),
				$vs_entity_type,
				$g_ui_locale_id,
				null,
				array('matchOnDisplayName' => true, 'importEvent' => $po_import_event, 'importEventSource' => $pa_import_event_source)
		);
	}

	/**
	 * @param $pa_table_content array
	 * @param $pa_translation_settings array
	 * @param $value mixed
	 * @param $vs_name string name
	 * @param $value_index int
	 * @param $o_import_event null/ca_data_import_events
	 * @param $pa_event_source null/array
	 * @param $pa_params array
	 * @param $o_log null/KLogger
	 * @internal int $g_ui_locale_id
	 */
	protected function _processListItemBundle(&$pa_table_content, $pa_translation_settings, $value, $vs_name, $value_index, $o_import_event, $pa_event_source, &$pa_params, $o_log) {
		global $g_ui_locale_id;
		$vs_list_code = caGetOption('list', $pa_translation_settings);
		if (!isset($vs_list_code) && $o_log) {
			$o_log->logError(sprintf('No list set in "%s" translation type "%s" on "%s" for idno "%s"', $pa_translation_settings['table'], $vs_name, $pa_params['idno']));
			return;
		}
		$vn_list_id = caGetListID($vs_list_code);
		if (!$vn_list_id) {
			// No list = bail!
			if ($o_log) {
				$o_log->logError(_t('[_proccesListItemBundle] Could not find list %1; item was skipped', $vs_list_code));
			}
			return;
		}
		//We almost always want 'concepts'
		$ps_type = caGetOption('type', $pa_translation_settings, 'concept');
		$va_attr_vals_with_parent = is_array($value) ? $value : array('preferred_labels' => array('name_singular' => $value));
		$vn_parent_id = caGetOption('parent_id', $pa_params, caGetListRootID($vs_list_code));

		$va_attr_vals_with_parent['parent_id'] = $vn_parent_id;
		$va_attr_vals_with_parent['is_enabled'] = 1;
		$vs_item_idno = caGetOption('idno', $va_attr_vals_with_parent, is_array($value) ? null : preg_replace('/\W+/', '_', $value));

		$pa_options = array(
				'matchOn' => array('label', 'idno'),
				'importEvent' => $o_import_event,
				'importEventSource' => $pa_event_source,
		);

		$pa_table_content[$vs_name][$value_index] = DataMigrationUtils::getListItemID($vs_list_code, $vs_item_idno, $ps_type, $g_ui_locale_id, $va_attr_vals_with_parent, $pa_options);
	}
}
