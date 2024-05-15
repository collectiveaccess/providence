<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/ConfigurationCheckController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Search/SearchEngine.php");
include_once(__CA_LIB_DIR__."/Print/PDFRenderer.php");

class ConfigurationCheckController extends ActionController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		if(!$po_request || !$po_request->isLoggedIn() || !$po_request->user->canDoAction('can_view_configuration_check')) {
			throw new AccessException(_t('Access denied'));
		}
	}
	# ------------------------------------------------
	public function DoCheck(){
		define('__CA_DONT_CACHE_EXTERNAL_APPLICATION_PATHS__', true);	// Force all plugins to reload their paths
		AssetLoadManager::register('tableList');

		// latest log id
		$this->getView()->setVar('last_change_log_id', ca_change_log::getLastLogID());

		// Search engine
		$vo_search_config_settings = SearchEngine::checkPluginConfiguration();
		$this->view->setVar('search_config_settings',$vo_search_config_settings);
		$this->view->setVar('search_config_engine_name',  SearchEngine::getPluginEngineName());
		
		// Media
		$t_media = new Media();
		$va_plugin_names = $t_media->getPluginNames();
		$va_plugins = array();
		foreach($va_plugin_names as $vs_plugin_name) {
			if ($va_plugin_status = $t_media->checkPluginStatus($vs_plugin_name)) {
				$va_plugins[$vs_plugin_name] = $va_plugin_status;
			}
		}
		
		$this->view->setVar('media_config_plugin_list',  $va_plugins);
		
		// PDF Rendering
		$t_pdf_renderer = new PDFRenderer();
		$va_plugin_names = PDFRenderer::getAvailablePDFRendererPlugins();
		$va_plugins = array();
		foreach($va_plugin_names as $vs_plugin_name) {
			if ($va_plugin_status = $t_pdf_renderer->checkPluginStatus($vs_plugin_name)) {
				$va_plugins[$vs_plugin_name] = $va_plugin_status;
			}
		}
		
		$this->view->setVar('pdf_renderer_config_plugin_list',  $va_plugins);
		
		// Application plugins
		$va_plugin_names = ApplicationPluginManager::getPluginNames();
		$va_plugins = array();
		foreach($va_plugin_names as $vs_plugin_name) {
			if ($va_plugin_status = ApplicationPluginManager::checkPluginStatus($vs_plugin_name)) {
				$va_plugins[$vs_plugin_name] = $va_plugin_status;
			}
		}
		$this->view->setVar('application_config_plugin_list',  $va_plugins);
		
		// Barcode generation
		$vb_gd_is_available = caMediaPluginGDInstalled(true);
		$va_barcode_components = array();
		$va_gd = array('name' => 'GD', 'description' => _t('GD is a graphics processing library required for all barcode generation.'));
		if (!$vb_gd_is_available) {
			$va_gd['errors'][] = _t('Is not installed; barcode printing will not be possible.');
		}
		$va_gd['available'] = $vb_gd_is_available;
		$va_barcode_components['GD'] = $va_gd;
		$this->view->setVar('barcode_config_component_list',  $va_barcode_components);
		
		$va_md_extraction_components = [
			'EXIFTool' => [
				'available' => false,
				'description' => _t('A tool for extraction of embedded metadata in images, documents and other media. See https://exiftool.org for additional information.'),
				'warnings' => [],
				'errors' => []
			],
			'MediaInfo' => [
				'available' => false,
				'description' => _t('A tool for extraction of embedded metadata from audio and video files. See https://mediaarea.net/en/MediaInfo for additional information.'),
				'warnings' => [],
				'errors' => []
			]
		];
		if (caExifToolInstalled()) {
			$va_md_extraction_components['EXIFTool']['available'] = true;
		}
		if (caMediaInfoInstalled()) {
			$va_md_extraction_components['MediaInfo']['available'] = true;
		}
		
		$this->view->setVar('metadata_extraction_config_component_list',  $va_md_extraction_components);
		

		// General system configuration issues
		if (!(bool)$this->request->config->get('dont_do_expensive_configuration_checks_in_web_ui')) {
			ConfigurationCheck::performExpensive();
			if(ConfigurationCheck::foundErrors()){
				$this->view->setVar('configuration_check_errors', ConfigurationCheck::getErrors());
			}
		}

		$this->render('config_check_html.php');
	}
	# ------------------------------------------------
}
