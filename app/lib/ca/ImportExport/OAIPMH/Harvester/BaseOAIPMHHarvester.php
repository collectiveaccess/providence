<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ImportExport/OAI/BaseOAIHarvester.php :
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
 * @package CollectiveAccess
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 	require_once(__CA_LIB_DIR__.'/ca/ImportExport/OAIPMH/Harvester/OAIPMHClient.php');
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
 	require_once(__CA_MODELS_DIR__."/ca_data_import_events.php");
 	
	class BaseOAIPMHHarvester {
		# -------------------------------------------------------
		protected $opo_client;
		protected $ops_base_url;
		protected $opo_processor;
		protected $opo_app_plugin_manager;
		protected $opo_data_import_event;
		
		protected $opb_debug = false;
		protected $opa_skip_elements = array();
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct($ps_base_url=null, $opo_processor=null, $pa_options=null) {
			$this->opo_client = new OAIPMHClient();
			$this->opo_app_plugin_manager = new ApplicationPluginManager();
			$this->opo_data_import_event = new ca_data_import_events();
			
			if (isset($pa_options['skipElements']) && is_array($pa_options['skipElements'])) {
				$this->opa_skip_elements = $pa_options['skipElements'];
			}
			
			if (isset($pa_options['debug'])) {
				$this->debug((bool)$pa_options['debug']);
			}
			
			if ($ps_base_url) {
				$this->harvest($ps_base_url, $opo_processor, $pa_options);
			}
		}
		# -------------------------------------------------------
		/** 
		 * Set output of debugging messages
		 *
		 * @param $pb_debug bool If true, debugging messages will be output
		 * @return void
		 */
		public function debug($pb_debug) {
			$this->opb_debug = (bool)$pb_debug;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function harvest($ps_base_url, $opo_processor, $pa_options) {
			global $AUTH_CURRENT_USER_ID;
			
			// set url
			$this->ops_base_url = $ps_base_url;
			$this->opo_processor = $opo_processor;
			
			// set authentication
			if(isset($pa_options['username'])) {
				$this->opo_client->setLogin($pa_options['username'], $pa_options['password']);
			}
		
			// handle from/to
			
			// handle sets
			if(isset($pa_options['set'])) {
				$this->opo_client->setSet($pa_options['set']);
			}
			
			// handle date/time restriction
			if(isset($pa_options['dateRange'])) {
				if (!($this->opo_client->setDateTimeRestriction($pa_options['dateRange']))) {
					// Bad date/time restriction
					return null;
				}
			}
			
			$vn_user_id = null;
			if (isset($pa_options['user_id']) && ($pa_options['user_id'] > 0)) {
				$vn_user_id = $pa_options['user_id'];
			} else {
				if ($AUTH_CURRENT_USER_ID) {
					$vn_user_id = $AUTH_CURRENT_USER_ID;
				}
			}
			
			
			$this->opo_data_import_event->clear();
			$this->opo_data_import_event->setMode(ACCESS_WRITE);
			$this->opo_data_import_event->set('user_id', $vn_user_id );
			$this->opo_data_import_event->set('occurred_on', 'now');
			$this->opo_data_import_event->set('description', isset($pa_options['description']) ? $pa_options['description'] : "OAI import from {$ps_base_url}");
			$this->opo_data_import_event->set('type_code', 'OAI');
			$this->opo_data_import_event->set('source', $this->opo_client->getRequestURL($ps_base_url, array_merge(array('verb' => 'ListRecords', 'metadataPrefix' => $this::METADATA_PREFIX), $pa_options)));
			
			$this->opo_data_import_event->insert();
		
			$this->_harvest();
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		private function _harvest($ps_resumption_token=null) {
			$va_settings = array(
				'verb' => 'ListRecords', 'metadataPrefix' => $this::METADATA_PREFIX
			);
			
			if ($ps_resumption_token) {
				$va_settings['resumptionToken'] = $ps_resumption_token;
			}
			
			$va_res = $this->opo_client->fetch($this->ops_base_url, $va_settings);
			
			if (!($va_records = $this->opo_client->getRecords())) { return null; }
		
			$o_tep = new TimeExpressionParser();
			foreach ($va_records as $o_record) {
				// skip deleted records
				
				// call superclass record harvest method
				$va_data = $this->harvestRecord($o_record);
				
				// If returned value is null then we skip the record
				if(is_null($va_record_data = $this->opo_app_plugin_manager->hookOAIPreprocessRecord(array('url' => $this->ops_base_url, 'record' => $va_data, 'metadata_prefix' => $this::METADATA_PREFIX)))) {
					continue;
				}
				$va_data = $va_record_data['record'];
			
				// Has this record been updated since the last time we harvested?
				$o_tep->parse($va_data['oai_datestamp']);
				$va_datestamp = $o_tep->getUnixTimestamps();
		
				$va_data['_last_record_update'] = (int)$va_datestamp['start'];
				//print_r($va_data);
				if ($va_data['oai_status'] === 'deleted') {
					// TODO: do we want to support OAI-triggered deletion of records?
				} else {
					$this->opo_processor->importRecord($va_data, $this->opo_data_import_event, array('debug' => $this->opb_debug, 'skipElements' => $this->opa_skip_elements));
				}
				$va_data = $this->opo_app_plugin_manager->hookOAIAfterRecordImport(array('url' => $this->ops_base_url, 'record' => $va_data, 'metadata_prefix' => $this::METADATA_PREFIX));
			}
			
			if ($vs_resumption_token = $this->opo_client->getResumptionToken()) {
				$this->_harvest($vs_resumption_token);
			}
		
			return true;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function getMetadataPrefix() {
			return $this::METADATA_PREFIX;
		}
		# -------------------------------------------------------
		/** 
		 *
		 */
		public function getMetadataSchema() {
			return $this::METADATA_SCHEMA;
		}
		# -------------------------------------------------------
	}
?>