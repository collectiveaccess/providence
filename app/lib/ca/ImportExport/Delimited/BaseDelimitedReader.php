<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ImportExport/Delimited/BaseDelimitedReader.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser.php");
 	require_once(__CA_MODELS_DIR__."/ca_data_import_events.php");
 	
	class BaseDelimitedReader {
		# -------------------------------------------------------
		protected $ops_base_url;
		protected $opo_processor;
		protected $opo_app_plugin_manager;
		protected $opo_data_import_event;
		
		protected $opb_debug = false;
		# -------------------------------------------------------
		/**
		 *
		 */
		public function __construct($ps_base_url=null, $po_processor=null, $pa_mappings_by_group=null, $po_instance=null, $pa_options=null) {
			$this->opo_app_plugin_manager = new ApplicationPluginManager();
			$this->opo_data_import_event = new ca_data_import_events();
			
			
			if (isset($pa_options['debug'])) {
				$this->debug((bool)$pa_options['debug']);
			}
			
			if ($ps_base_url) {
				$this->read($ps_base_url, $po_processor, $pa_mappings_by_group, $po_instance, $pa_options);
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
		public function read($ps_base_url, $po_processor, $pa_mappings_by_group, $po_instance, $pa_options) {
			global $AUTH_CURRENT_USER_ID;
			
			// set url
			$this->ops_base_url = $ps_base_url;
			$this->opo_processor = $po_processor;
			
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
			$this->opo_data_import_event->set('description', isset($pa_options['description']) ? $pa_options['description'] : "Import from {$ps_base_url}");
			$this->opo_data_import_event->set('type_code', $this->ops_name);
			$this->opo_data_import_event->set('source', $ps_base_url);
			
			$this->opo_data_import_event->insert();
		
			
			// call superclass record harvest method
			$o_parser = $this->parseRecords($this->ops_base_url);
		
			while($o_parser->nextRow()) {
				// If returned value is null then we skip the record
				if(is_null($va_record_data = $this->opo_app_plugin_manager->hookDelimitedPreprocessRecord(array('url' => $this->ops_base_url, 'record' => $va_record, 'metadata_prefix' => $this::METADATA_PREFIX, 'mapping_code' => $pa_options['mapping_code'])))) {
					continue;
				}
				$va_data = $va_record_data['record'];
				
				$va_raw_data = $o_parser->getRow();
				$va_data = array();
				
				// shift keys up by one (column numbers should be 1-based, not zero-based)
				foreach($va_raw_data as $vn_i => $vm_data) {
					$va_data[$vn_i + 1] = $vm_data;	
				}
				
				$va_errors =  DataImporter::importRecord($va_data, $this->opo_data_import_event, $pa_mappings_by_group, $po_instance, array_merge($pa_options, array('debug' => $this->opb_debug)));

				if (is_array($va_errors) && sizeof($va_errors)) {
					$va_error_messages = array();
					foreach($va_errors as $o_error) {
						$va_error_messages[] = $o_error->getErrorDescription();
					}
					print_R($va_error_messages);
				}

				$va_data = $this->opo_app_plugin_manager->hookDelimitedAfterRecordImport(array('url' => $this->ops_base_url, 'record' => $va_data, 'metadata_prefix' => $this::METADATA_PREFIX, 'mapping_code' => $pa_options['mapping_code']));
			}
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
		/** 
		 *
		 */
		public function xmlToArray($po_xml) {
		   if (@get_class($po_xml) == 'SimpleXMLElement') {
			   $attributes = $po_xml->attributes();
			   foreach($attributes as $k=>$v) {
				   if ($v) $a[$k] = (string) $v;
			   }
			   $x = $po_xml;
			   $po_xml = get_object_vars($po_xml);
		   }
		   if (is_array($po_xml)) {
			   if (count($po_xml) == 0) return (string) $x; // for CDATA
			   foreach($po_xml as $key=>$value) {
				   $r[$key] = $this->xmlToArray($value);
			   }
			   if (isset($a)) $r['@'] = $a;    // Attributes
			   return $r;
		   }
		   return (string) $po_xml;
		}
		# -------------------------------------------------------
	}
?>