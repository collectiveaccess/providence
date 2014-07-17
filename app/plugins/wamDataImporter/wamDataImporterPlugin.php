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
	public function hookDataImportContentTree($pa_params){
		caDebug($pa_params['environment'], 'environment', true);
	}
}
