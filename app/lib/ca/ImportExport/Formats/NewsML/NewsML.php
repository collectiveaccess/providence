<?php
/** ---------------------------------------------------------------------
 * NewsML.php : import/export module for NewsML data format (http://www.NewsML.org)
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
$g_ca_data_import_export_format_definitions['NewsML'] = array(
	'element_list' => array(
		'itemMeta' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'title' 			=> array(),
				'versionCreated'	=> array(),
				'itemClass'			=> array(),
				'provider'			=> array(),
				'pubStatus'			=> array()
			)
		),
		'contentMeta' => array(
			'canRepeat' 	=> false,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'by' 				=> array(),
				'creator'			=> array(
					'subElements' => array(
						'name' 			=> array()
					)
				),
				'contributor'		=> array(
					'subElements' => array(
						'name' 			=> array()
					)
				),
				'contentCreated' 	=> array(),
				'subject'			=> array(
					'canRepeat'		=> true,
					'subElements' => array(
						'name'			=> array()
					)
				),
				'located'			=> array(
					'subElements' => array(
						'name'			=> array()
					)
				),
				'description' 		=> array()
			)
		),
		'contentSet' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'inlineData' 			=> array()
			)
		),
		'rightsInfo' => array(
			'canRepeat' 	=> true,
			'minValues' 	=> 1,
			'subElements' 	=> array(
				'copyrightHolder'	 	=> array()
				
			)
		)
	),
	'name' 				=> _t('NewsML'),
	'version' 			=> '1.2',
	'description' 		=> _t('NewsML is designed to provide a media-type-independent, structural framework for multi-media news. Beyond exchanging single items it can also convey packages of multiple items in a structures layout.'),
	'url' 				=> 'http://www.NewsML.org',
	'output_mimetype'	=> 'text/xml',
	'file_extension'	=> 'xml',
	
	'start_tag'			=> '<newsItem xmlns="http://iptc.org/std/nar/2006-10-01/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/ http://www.iptc.org/std/NewsML-G2/2.4/specification/NewsML-G2_2.4-spec-NewsItem-Core.xsd" standard="NewsML-G2" standardversion="2.2" guid="urn:newsml:npr.org:20100609:testcontent">
    <!-- for GUID (required) using format guid="urn:newsml:[ProviderId]:[DateId]:[NewsItemId]" other globally unique ID could be used-->
    <catalogRef href="http://www.iptc.org/std/catalog/catalog.IPTC-G2-Standards_11.xml"></catalogRef>',
    
);

	class DataMoverNewsML extends BaseXMLDataMover {
		# -------------------------------------------------------
		/** Name of format. Should be same as filename without extension */
		protected $ops_name = 'NewsML';
		
		/** Short text describing format */ 
		protected $ops_description = '';
		
		/** Format version number */
		const VERSION = '2.2';
		
		/** URL with information on format */
		const INFO_URL = 'http://www.NewsML.org';
		
		/** Extension to use when outputting this format to a file */
		const EXTENSION = 'xml';
		
		/** Mimetype to use when outputting this format */
		const MIMETYPE = 'text/xml';
		
		 /** Metadata prefix */
		const METADATA_PREFIX = 'NewsML';
		
		/** XML namespace uri for output format */
		const METADATA_NAMESPACE = 'http://iptc.org/std/nar/2006-10-01/';
		
		/** XML schema uri for output format */
		const METADATA_SCHEMA = 'http://www.iptc.org/std/NewsML-G2/2.4/specification/NewsML-G2_2.4-spec-NewsItem-Core.xsd';
		
		/** XML namespace uri for unqualified Dublin Core */
		const NEWSML_NAMESPACE_URI = 'urn:isbn:1-931666-22-9" xmlns:slink="http://www.w3.org/1999/xlink';
		
		/** XML namespace URI for XML schema */
		const XML_SCHEMA_NAMESPACE_URI = 'http://www.w3.org/2001/XMLSchema-instance';
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_description = _t('NewsML is designed to provide a media-type-independent, structural framework for multi-media news. Beyond exchanging single items it can also convey packages of multiple items in a structures layout.');
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
			
			// Create NewsML root tag
			$o_root_tag = $r_document->createElement('newsItem');
			$o_root_tag->setAttribute('xsi:schemaLocation', DataMoverNewsML::NEWSML_NAMESPACE_URI.' '.DataMoverEAD::METADATA_SCHEMA);
			$o_root_tag->setAttribute('xmlns:xsi', DataMoverNewsML::XML_SCHEMA_NAMESPACE_URI);
			$o_root_tag->setAttribute('xmlns', DataMoverNewsML::NEWSML_NAMESPACE_URI);
			$o_root_tag->setAttribute('standard', "NewsML-G2");
			$o_root_tag->setAttribute('standardversion', "2.2");
			$o_root_tag->setAttribute('guid', microtime(true).ran(1000));
			$r_document->appendChild($o_root_tag);
			
			$o_cat_ref = $r_document->createElement('catalogRef');
			$o_cat_ref->setAttribute('href', 'http://www.iptc.org/std/catalog/catalog.IPTC-G2-Standards_11.xml');
			$r_document->appendChild($o_cat_ref);
			
			return parent::output($r_document, $pm_target, $pa_options);
		}
		# -------------------------------------------------------
	}
?>