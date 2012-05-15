<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ImportExport/OAI/OAIDCHarvester.php :
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
 	require_once(__CA_LIB_DIR__.'/ca/ImportExport/OAIPMH/Harvester/BaseOAIPMHHarvester.php');
 	
 	/**
 	 * OAI Harvester for use with OAI DublinCore metadata
 	 */
	class OAIPMHHarvesterOaiDC extends BaseOAIPMHHarvester {
		# -------------------------------------------------------
		const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
		const METADATA_PREFIX = 'oai_dc';
		
		const OAI_DC_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
		const DUBLIN_CORE_NAMESPACE = 'http://purl.org/dc/elements/1.1/';
		
		private $opa_dc_elements = array(
			'contributor', 'coverage', 'creator', 'date', 'description', 'format', 
			'identifier', 'language', 'publisher', 'relation', 'rights', 'source',
			'subject', 'title', 'type'
		);
		# -------------------------------------------------------
		/**
		 * @param $ps_base_url string Base URL for OAI-PMH provider
		 * @param $po_processor object Instance of class implementing 
		 * @param $pa_options array Harvesting options
		 */
		public function __construct($ps_base_url=null, $po_processor=null, $pa_options=null) {
			parent::__construct($ps_base_url, $po_processor, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * @param $po_record_xml 
		 */
		protected function harvestRecord($po_record_xml){
			$va_record = $po_record_xml->metadata->children(self::OAI_DC_NAMESPACE)->children(self::DUBLIN_CORE_NAMESPACE);
			
			$va_data = array();
			foreach($this->opa_dc_elements as $vs_element) {
				 if (isset($va_record->$vs_element)) {
				 	foreach($va_record->$vs_element as $vs_value) {
				 		$va_data[$vs_element][] = (string)$vs_value;
				 	}
				 }
			}
			
			// Add header fields to returned record data
			$va_data['oai_identifier'] = $po_record_xml->header->identifier;
			$va_data['oai_datestamp'] = $po_record_xml->header->datestamp;
			$va_data['oai_status'] = $po_record_xml->header->status;
			return $va_data;
		}
		# -------------------------------------------------------
	}
?>