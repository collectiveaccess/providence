<?php
/** ---------------------------------------------------------------------
 * PBCore.php : import/export module for PBCore data format (http://www.pbcore.org)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
  
	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/BaseXMLDataMover.php');
	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/PBCore/PBCoreReader.php');
	
	
global $g_ca_data_import_export_format_definitions;
$g_ca_data_import_export_format_definitions['PBCore'] = array(
	'element_list' => array(
		'pbcoreIdentifier' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'identifier' 			=> array(),
				'identifierSource' 		=> array()
			)
		),
		'pbcoreTitle' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'title' 				=> array(),
				'titleType'				=> array()
			)
		),
		'pbcoreCoverage' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'coverage' 				=> array(),
				'coverageType'			=> array()
			)
		),
		'pbcoreCreator' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'creator' 				=> array(),
				'creatorRole'			=> array()
			)
		),
		'pbcoreInstantiation' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'dateCreated' 			=> array(),
				'dateIssued'			=> array(),
				'formatDuration'		=> array(),
				'formatLocation'		=> array(),
				'formatID'				=> array(
					'subElements' => array(
						'formatIdentifier' => array(),
						'formatIdentifierSource' => array()
					)
				)
				
			)
		),
		'pbcoreSubject' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'subject'	 			=> array(),
				'subjectAuthorityUsed'	=> array()
				
			)
		),
		'pbcoreExtension' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'extension'	 				=> array(),
				'extensionAuthorityUsed'	=> array()
				
			)
		),
		'pbcoreRelation' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'relationType'	 			=> array(),
				'relationIdentifier'		=> array()
				
			)
		),
	)
);

	class DataMoverPBCore extends BaseXMLDataMover {
		# -------------------------------------------------------
		/** Name of format. Should be same as filename without extension */
		protected $ops_name = 'PBCore';
		
		/** Short text describing format */ 
		protected $ops_description = '';
		
		/** Format version number */
		const VERSION = '1.2';
		
		/** URL with information on format */
		const INFO_URL = 'http://www.pbcore.org';
		
		/** Extension to use when outputting this format to a file */
		const EXTENSION = 'xml';
		
		/** Mimetype to use when outputting this format */
		const MIMETYPE = 'text/xml';
		
		 /** Metadata prefix */
		const METADATA_PREFIX = 'PBCore';
		
		/** XML namespace uri for output format */
		const METADATA_NAMESPACE = '';
		
		/** XML schema uri for output format */
		const METADATA_SCHEMA = 'http://pbcore.org/PBCore/PBCoreXSD_Ver_1-2-1.xsd';
		
		/** XML namespace uri */
		const PBCORE_NAMESPACE_URI = 'http://www.pbcore.org/PBCore/PBCoreNamespace.html';
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_description = _t('Public Broadcasting Core data format; used for data interchange between audio/video archives');
		}
		# -------------------------------------------------------
		# Import
		# -------------------------------------------------------
		/**
		 * Read and parse metadata
		 *
		 * @param $ps_url_or_path string - URL or directory path to a PBCore XML file 
		 * @param $po_caller DataImporter - Instance of DataImporter to call importRecord() on for each processed PBCore record 
		 * @param $pa_mappings_by_group array - Array of import mappings keyed on mapping group name
		 * @param $po_instance - An instance of the model class for the table we're importing into
		 * @param $pa_options array - An array of options to use
		 */
		public function import($ps_url_or_path, $po_caller, $pa_mappings_by_group, $po_instance, $pa_options=null) {
			$o_reader = new PBCoreReader($ps_url_or_path, $po_caller, $pa_mappings_by_group, $po_instance, $pa_options);
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param $pm_target string|file resource - a file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @param $pa_options array -
		 * @return boolean|string - true on success, false on failure; if $pm_target is null or 'returnOutput' option is set to true then string representation of output metadata will be returned
		 */
		public function output($pm_target, $pa_options=null) {
			$r_document = new DomDocument();
			
			// Create PBCore root tag
			$o_root_tag = $r_document->createElement('PBCoreDescriptionDocument');
			$o_root_tag->setAttribute('xmlns', DataMoverPBCore::PBCORE_NAMESPACE_URI);
			$r_document->appendChild($o_root_tag);
			
			return parent::output($r_document, $pm_target, $pa_options);
		}
		# -------------------------------------------------------
	}
?>