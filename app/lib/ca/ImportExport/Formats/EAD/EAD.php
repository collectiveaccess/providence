<?php
/** ---------------------------------------------------------------------
 * EAD.php : import/export module for EAD data format
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
	
	
global $g_ca_data_import_export_format_definitions;
$g_ca_data_import_export_format_definitions['EAD'] = array(
	'element_list' => array(
		'eadheader' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'eadid' 			=> array(),
				'filedesc'			=> array(
					'subElements' => array(
						'titlestmt' => array(
							'subElements' => array(
								'titleproper' => array()
							)
						),
						'publicationstmt' => array(
							'subElements' => array(
								'publisher' => array(),
								'date' => array(),
								'address' => array(
									'subElements' => array(
										'addressline' => array(
											'canRepeat' => true
										)
									)
								)
							)
						)
					)
				)
			)
		),
		'archdesc' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 0,
			'subElements' 	=> array(
				'did'	 			=> array(
					'subElements' => array(
						'head' => array(),
						'origination' => array(
							'subElements' => array(
								'persname' => array(
									'canRepeat' => true
								)
							)
						),
						'unitdate' => array(
							'canRepeat' => false
						),
						'repository' => array(
							'subElements' => array(
								'corpname' => array(
									'canRepeat' => false
								),
								'address' => array(
									'canRepeat' => false,
									'subElements' => array(
										'addressline' => array(
											'canRepeat' => true
										)
									)
								)
							)
						)
					)
				),
				'descgrp' => array(
					'subElements' => array(
						'accessrestrict' => array(
							'canRepeat' => true,
							'subElements' => array(
								'head' => array()
							)
						)
					)
				),
				'controlaccess' => array(
					'canRepeat' => false,
					'subElements' => array(
						'controlaccess' => array(
							'canRepeat' => true,
							'subElements' => array(
								'head' => array(),
								'subject' => array(
									'canRepeat' => true
								),
								'genreform' => array(
									'canRepeat' => true
								),
								'geogname' => array(
									'canRepeat' => true
								)
							)
						)
					)	
				),				
				'dsc' => array(
					'canRepeat' 	=> false,
					'minValues' 	=> 1,
					'subElements' 	=> array(
						'head' 			=> array(),
						'c'			=> array(
							'canRepeat' => true,
							'subElements' => array(
								'did' => array(
									'canRepeat' => true,
									'subElements' => array(
										'unittitle' => array(
											'canRepeat' => false
										)
									)
								)
							)
						)
					)
				)
			)
		)
	)
);

	class DataMoverEAD extends BaseXMLDataMover {
		# -------------------------------------------------------
		/** Name of format. Should be same as filename without extension */
		protected $ops_name = 'EAD';
		
		/** Short text describing format */ 
		protected $ops_description = '';
		
		/** Format version number */
		const VERSION = '2002';
		
		/** URL with information on format */
		const INFO_URL = 'http://www.loc.gov/ead/';
		
		/** Extension to use when outputting this format to a file */
		const EXTENSION = 'xml';
		
		/** Mimetype to use when outputting this format */
		const MIMETYPE = 'text/xml';
		
		 /** Metadata prefix */
		const METADATA_PREFIX = 'EAD';
		
		/** XML namespace uri for output format */
		const METADATA_NAMESPACE = 'urn:isbn:1-931666-22-9';
		
		/** XML schema uri for output format */
		const METADATA_SCHEMA = 'http://www.loc.gov/ead/ead.xsd';
		
		/** XML namespace uri for unqualified EAD */
		const EAD_NAMESPACE_URI = '';
		
		/** XML namespace URI for XML schema */
		const XML_SCHEMA_NAMESPACE_URI = 'http://www.w3.org/2001/XMLSchema-instance';
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_description = _t('Encoded Archival Description format');
		}
		# -------------------------------------------------------
		# Import
		# -------------------------------------------------------
		/**
		 * Read and parse metadata
		 *
		 * @param $pm_input mixed - A file path or file resource containing the metadata to be parsed
		 * @param $pa_options array - An array of parse options
		 */
		public function import($pm_input, $pa_options=null) {
		
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
			
			// Create EAD root tag
			$o_root_tag = $r_document->createElement('ead');
			//$o_root_tag->setAttribute('xmlns', DataMoverEAD::EAD_NAMESPACE_URI);
			$o_root_tag->setAttribute('xmlns:xsi', DataMoverEAD::XML_SCHEMA_NAMESPACE_URI);
			$o_root_tag->setAttribute('xsi:schemaLocation', DataMoverEAD::METADATA_NAMESPACE.' '.DataMoverEAD::METADATA_SCHEMA);
			$r_document->appendChild($o_root_tag);
			
			return parent::output($r_document, $pm_target, $pa_options);
		}
		# -------------------------------------------------------
	}
?>