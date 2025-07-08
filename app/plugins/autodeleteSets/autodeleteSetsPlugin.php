<?php
/* ----------------------------------------------------------------------
 * autodeleteSetsPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
class autodeleteSetsPlugin extends BaseApplicationPlugin {
	# -------------------------------------------------------
	private $config;
	/** @var KLogger  */
	private $log;
	# -------------------------------------------------------
	public function __construct($plugin_path) {
		$this->description = _t('Auto-delete sets');
		$this->config = Configuration::load($plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'plugin.conf');
		$this->log = caGetLogger(['logDirectory' => __CA_APP_DIR__.'/log']);
		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true - the autodeleteSets plugin always initializes ok
	 */
	public function checkStatus() {
		return [
			'description' => $this->getDescription(),
			'errors' => [],
			'warnings' => [],
			'available' => (bool)$this->config->get('enable')
		];
	}
	# -------------------------------------------------------
	/**
	 * Run periodic tasks
	 */
	public function hookPeriodicTask(?array $options=null) {
		if($tasks = caGetOption('limit-to-tasks', $options, null)) {
			if(!in_array('autodeleteSets', $tasks)) { return true; }
		}
		
		// Check for sets to autodelete
		$delete_count = ca_sets::autodeleteSets();
		
		if($delete_count > 0) {
			$this->log->logInfo(__CLASS__ . ': '._t('Auto-deleted %1 sets', $delete_count));
		}
		
		// Return true to allow following plugins to run
		return true;
	}
	# -------------------------------------------------------
}
