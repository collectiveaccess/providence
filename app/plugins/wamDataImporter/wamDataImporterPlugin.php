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
	 */
	public function hookDataImportContentTree(&$pa_params){
		global $g_ui_locale_id;

		foreach ($pa_params['content_tree'] as $table_name => $table_content_tree) {
			foreach ($table_content_tree as $table_content_index => $table_content) {
				if (isset($table_content['_interstitial']) && isset($table_content['_interstitial']['_translations'])) {
					// Apply all translations
					foreach ($table_content['_interstitial']['_translations'] as $name => $translation_settings) {
						$translation_settings = json_decode($translation_settings, true);
						if (isset($table_content['_interstitial'][$name])) {
							if (isset($translation_settings['delimiters'])) {
								// Ensure we have an array
								if (!is_array($translation_settings['delimiters'])) {
									$translation_settings['delimiters'] = array( $translation_settings['delimiters'] );
								}
								// Quote the delimiters for preg_split
								$translation_settings['delimiters'] = array_map(
										function ($delimiter) {
											return preg_quote($delimiter, '!');
										},
										$translation_settings['delimiters']
								);
								// Split the value based on given delimiters
								$table_content['_interstitial'][$name] = preg_split("!(".join("|", $translation_settings['delimiters']).")!", $table_content['_interstitial'][$name]);
							}
							foreach ($table_content['_interstitial'][$name] as $value_index => $value) {
								switch ($translation_settings['type']) {
									case 'ca_entities':
										$table_content['_interstitial'][$name][$value_index] = DataMigrationUtils::getEntityID(
												DataMigrationUtils::splitEntityName($value),
												'ind',
												$g_ui_locale_id,
												null,
												array( 'matchOnDisplayName' => true )
										);
										break;

									default:
										if (isset($pa_params['log'])) {
											$pa_params['log']->logError(sprintf('Unknown interstitial translation type "%s" on "%s" for idno "%s"', $translation_settings['type'], $name, $pa_params['idno']));
										}
								}
							}
						} else {
							if (isset($pa_params['log'])) {
								$pa_params['log']->logError(sprintf('Unknown interstitial name "%s" specified in translation of type "%s" for idno "%s"', $name, $translation_settings['type'], $pa_params['idno']));
							}
						}
					}

					// Save the translated value back into the content tree
					$pa_params['content_tree'][$table_name][$table_content_index] = $table_content;

					// Remove translations special key so it is not added as an interstitial
					unset($pa_params['content_tree'][$table_name][$table_content_index]['_interstitial']['_translations']);
				}
			}
		}

		return $pa_params;
	}
}
