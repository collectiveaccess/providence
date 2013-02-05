<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Import/DataReaderManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/ca/Import/BaseDataReader.php');
 
	class DataReaderManager {
		# -------------------------------------------------------
		/** 
		 * @var Global flag indicating whether we've required() widgets yet
		 */
		static $s_data_reader_manager_did_do_reader_init = false;
		
		/** 
		 * 
		 */
		static $s_data_reader_instances = array();
		# -------------------------------------------------------
		public function __construct() {
			DataReaderManager::initDataReaders();
		}
		# -------------------------------------------------------
		#
		# -------------------------------------------------------
		/**
		 * Loads readers
		 */
		public static function initDataReaders() {
			if (DataReaderManager::$s_data_reader_manager_did_do_reader_init) { return true; }
			
			$vs_base_reader_dir = __CA_LIB_DIR__.'/ca/Import/DataReaders';
			
			$va_readers = DataReaderManager::getDataReaderNames();
			foreach($va_readers as $vs_reader) {
				if (!file_exists("{$vs_base_reader_dir}/{$vs_reader}.php")) { continue; }
				require_once("{$vs_base_reader_dir}/{$vs_reader}.php");
				$vs_reader_classname = "{$vs_reader}";
				
				$o_instance = new $vs_reader_classname();
				
				$va_status = $o_instance->checkStatus();
				
				if (!isset($va_status['available']) || !$va_status['available']) { continue;}
				
				DataReaderManager::$s_data_reader_instances[$vs_reader] = $o_instance;	
			}
			
			DataReaderManager::$s_data_reader_manager_did_do_reader_init = true;
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all readers
		 */
		public static function getDataReaderInstance($ps_reader) {
			DataReaderManager::initDataReaders();
			if (isset(DataReaderManager::$s_data_reader_instances[$ps_reader]) && DataReaderManager::$s_data_reader_instances[$ps_reader]) {
				return DataReaderManager::$s_data_reader_instances[$ps_reader];
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns names of all readers
		 */
		public static function getDataReaderNames() {
			$vs_base_reader_dir = __CA_LIB_DIR__.'/ca/Import/DataReaders';
			
			$va_readers = array();
			if (is_resource($r_dir = opendir($vs_base_reader_dir))) {
				while (($vs_reader = readdir($r_dir)) !== false) {
					if (file_exists($vs_base_reader_dir.'/'.$vs_reader) && preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*)\.php$/", $vs_reader, $va_matches)) {
						$va_readers[] = $va_matches[1];
					}
				}
			}
			
			sort($va_readers);
			
			return $va_readers;
		}
		# ----------------------------------------------------------
		/**
		 * Return status info for specified reader
		 */
		public static function checkDataReaderStatus($ps_reader_name) {
			DataReaderManager::initDataReaders();
			
			if(isset(DataReaderManager::$s_data_reader_instances[$ps_reader_name]) && is_object(DataReaderManager::$s_data_reader_instances[$ps_reader_name])) {
				return DataReaderManager::$s_data_reader_instances[$ps_reader_name]->checkStatus();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return display title for specified reader
		 */
		public static function getDataReaderTitle($ps_reader_name) {
			DataReaderManager::initDataReaders();
			
			if(isset(DataReaderManager::$s_data_reader_instances[$ps_reader_name]) && is_object(DataReaderManager::$s_data_reader_instances[$ps_reader_name])) {
				return DataReaderManager::$s_data_reader_instances[$ps_reader_name]->getTitle();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Return description for specified refinery
		 */
		public static function getDataReaderDescription($ps_reader_name) {
			DataReaderManager::initDataReaders();
			
			if(isset(DataReaderManager::$s_data_reader_instances[$ps_reader_name]) && is_object(DataReaderManager::$s_data_reader_instances[$ps_reader_name])) {
				return DataReaderManager::$s_data_reader_instances[$ps_reader_name]->getDescription();
			}
			
			return null;
		}
		# -------------------------------------------------------
		/**
		 * 
		 * @param string $ps_format
		 * @return BaseDataReader 
		 */
		public static function getDataReaderForFormat($ps_format) {
			DataReaderManager::initDataReaders();
			
			$va_readers = DataReaderManager::getDataReaderNames();
			foreach($va_readers as $vs_reader) {
				if ($o_reader = DataReaderManager::getDataReaderInstance($vs_reader)) {
					if ($o_reader->canReadFormat($ps_format)) {
						return $o_reader;
					}
				}
			}
			
			return null;
		}
		# -------------------------------------------------------
	}
?>