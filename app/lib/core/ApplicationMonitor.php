<?php
/** ---------------------------------------------------------------------
 * app/lib/core/ApplicationMonitor.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
   	if (!defined('__CA_MICROTIME_START_OF_REQUEST__')) { define("__CA_MICROTIME_START_OF_REQUEST__", microtime()); }
   	if (!defined('__CA_BASE_MEMORY_USAGE__')) { define("__CA_BASE_MEMORY_USAGE__", memory_get_usage(true)); }
   	
   require_once(__CA_LIB_DIR__.'/core/Configuration.php');
   
	class ApplicationMonitor {
		# ------------------------------------------------------------------------------------------------
		/**
		 * @var Configuration object for application monitor configuration file
		 */
		private $opo_monitor_config;
		
		/**
		 * @var Array containing log output
		 */
		private $opa_query_log;
		private $opa_time_log;
		
		/**
		 *
		 */
		private $opn_log_queries_taking_long_than = 0;
		private $opb_enabled = false;
		
		# ------------------------------------------------------------------------------------------------
		public function __construct() {
			$o_config = Configuration::load();
			$this->opo_monitor_config = Configuration::load($o_config->get('application_monitor_config'));
			
			$this->opb_enabled = (bool)$this->opo_monitor_config->get('enabled');
			$this->opn_log_queries_taking_long_than = $this->opo_monitor_config->get('log_queries_taking_long_than');
			$this->clearLog();
		}
		# ------------------------------------------------------------------------------------------------
		/**
		 *
		 */
		public function getElapsedTime() {
			if (!$this->opb_enabled) { return null; }
		
		}
		# ------------------------------------------------------------------------------------------------
		/**
		 *
		 */
		public function getLogOutput() {
			if (!$this->opb_enabled) { return null; }
			return array(
				'queries' => $this->opa_query_log,
				'time' => $this->opa_time_log
			);
		}
		# ------------------------------------------------------------------------------------------------
		/**
		 *
		 */
		public function logQuery($ps_query, $pa_params, $pn_execution_time, $pn_num_hits=null) {
			if (!$this->opb_enabled) { return null; }
			if ($pn_execution_time >= $this->opn_log_queries_taking_long_than) {
				$this->opa_query_log[] = array(
					'query' => $ps_query,
					'params' => $pa_params,
					'time' => $pn_execution_time,
					'numHits' => $pn_num_hits,
					'trace' => debug_backtrace()
				);
			}
		}
		# ------------------------------------------------------------------------------------------------
		/**
		 *
		 */
		public function logTime($ps_location, $ps_description=null) {
			if (!$this->opb_enabled) { return null; }
		
		}
		# ------------------------------------------------------------------------------------------------
		/**
		 *
		 */
		public function clearLog() {
			$this->opa_query_log = array();
			$this->opa_time_log = array();
		}
		# ------------------------------------------------------------------------------------------------
		/**
		 *
		 */
		public function isEnabled() {
			return $this->opb_enabled;
		}
		# ------------------------------------------------------------------------------------------------
	}
?>