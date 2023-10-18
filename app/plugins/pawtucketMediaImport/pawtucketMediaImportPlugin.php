<?php
/* ----------------------------------------------------------------------
 * pawtucketMediaImportPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
  
	class pawtucketMediaImportPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		/** @var KLogger  */
		private $opo_log;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Pawtucket Media Import Processor');
			$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'plugin.conf');
			$this->opo_log = caGetLogger(['logDirectory' => __CA_APP_DIR__.'/log']);
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the pawtucketMediaImport plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => [],
				'warnings' => [],
				'available' => true
			);
		}
		# -------------------------------------------------------
		/**
		 * Run periodic tasks
		 */
		public function hookPeriodicTask() {
			$ret = ca_media_upload_sessions::processSessions(['limit' => 20]);
			$this->opo_log->logInfo(__CLASS__ . ": Processed $ret sessions");
			// Allow plugins after pawtuckeMediaImport to also process
			return true;
		}
		# -------------------------------------------------------
	}
