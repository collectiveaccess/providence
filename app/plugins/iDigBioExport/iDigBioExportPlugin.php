<?php
/* ----------------------------------------------------------------------
 * app/plugins/WorldCatPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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

    require_once(__CA_LIB_DIR__.'/Logging/Eventlog.php');
    require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
	require_once(__CA_APP_DIR__.'/helpers/utilityHelpers.php');
	require_once(__CA_LIB_DIR__.'/Search/ObjectSearch.php');

	class iDigBioExportPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		/**
		 *
		 */
		protected $description = null;

		/**
		 *
		 */
		private $opo_config;

		/**
		 *
		 */
		private $ops_plugin_path;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->ops_plugin_path = $ps_plugin_path;
			$this->description = _t('Export Configuration for records through iDigBio RSS feed');

			parent::__construct();

			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/iDigBio.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the statisticsViewerPlugin always initializes ok... (part to complete)
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
        # -------------------------------------------------------
		/**
		 * Create source CSV files and RSS feed periodically
		 */

        public function hookPeriodicTask(){
			// Get the Public url
            $publicURL = $this->opo_config->get('urlRoot').$this->opo_config->get('rssPublicPath');
			// Get the target directory
            $rssDirectory = $this->opo_config->get('rssAbsolutePath');
            // Check if that directory exists and if not, create it
            if(!file_exists($rssDirectory)){
                $vb_dir_result = mkdir($rssDirectory);
                if(!$vb_dir_result){
                    return False;
                }
                $vb_eml = mkdir($rssDirectory.'eml');
                $vb_archive = mkdir($rssDirectory.'exportArchive');
            }
            
            // Get the RSS file name
            $rssFile = $this->opo_config->get('rssFeedName');

            // Get static fields from iDigBioExport plugin conf
			$headerFields = $this->opo_config->get('headers');
			
			// Get info for collections set up for export
            $va_collections = $this->opo_config->get('collections');

            // Generate the XML file
			$xml = new DOMDocument('1.0', 'utf-8');
            $xml->formatOutput = true;
            $rss = $xml->createElement("rss");
            $rssNode = $xml->appendChild($rss);
            $rssNode->setAttribute("version", "2.0");
            $rssNode->setAttribute("xmlns:ipt", "http://ipt.gbif.org/");

            $channel = $xml->createElement("channel");
            $rssNode->appendChild($channel);
            foreach($headerFields as $vs_field => $vs_value){
                $tempEl = $xml->createElement($vs_field, $vs_value);
                $channel->appendChild($tempEl);
            }
            $ve_mainLink = $xml->createElement("link", $this->opo_config->get('urlRoot').$this->opo_config->get('rssPublicPath').$rssFile.".xml");
            $channel->appendChild($ve_mainLink);
            foreach($va_collections as $vs_collection_code => $va_collection){
            	$vs_file_name = $this->getCSVExport($va_collection, $vs_collection_code, $rssDirectory, $va_collection['recordtype']);

                $tempItem = $xml->createElement('item');
                foreach($va_collection['rss_fields'] as $vs_field => $vs_value){
                    $tempEl = $xml->createElement($vs_field, $vs_value);
                    $tempItem->appendChild($tempEl);
                }
                $csv = $xml->createElement('link', $publicURL.$vs_file_name);
				$tempItem->appendChild($csv);
                
                $ve_recordtype = $xml->createElement('recordtype', $va_collection['recordtype']);
                $tempItem->appendChild($ve_recordtype);
                
                $date = $xml->createElement('pubDate', date("D, d M Y G:i:s"));
                $tempItem->appendChild($date);
                
                //Move EML file and create link
                $vs_eml_filename = basename($va_collection['eml_file']);
                if(!file_exists($rssDirectory.'eml/')){
                    mkdir($rssDirectory.'eml/');
                }
                copy($va_collection['eml_file'], $rssDirectory.'eml/'.$vs_eml_filename);
                $ve_eml = $xml->createElement('ipt:eml', $publicURL.'eml/'.$vs_eml_filename);
                $tempItem->appendChild($ve_eml);
                $channel->appendChild($tempItem);
            }
            $ipt_rss = $xml->saveXML();

            // Store the RSS in a publically available directory
            file_put_contents($rssDirectory.$rssFile.'.xml', $ipt_rss);
        }

		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		static function getRoleActionList() {
			return array(
				'can_export_iDigBio' => array(
						'label' => _t('Can configure export to iDigBio'),
						'description' => _t('User can configure settings and records to include in export to iDigBio.')
					)
			);
		}
		# -------------------------------------------------------
        /**
         * Generate Export CSV File
        */
        private function getCSVExport($va_collection, $vs_collection_code, $vs_rss_dir, $vs_type){
            $t_log = new Eventlog();
			$vs_file_name_extension = '_collection_data.csv';
			
			//Extract relevant info from collection config
			$vs_query = $va_collection['filterQuery'];
			$vf_exporter = $va_collection['exporter'];
			$vs_exporter_dir = $va_collection['exporter_directory'];
			// Search collections get all specimens flagged for publication
            $o_search = new ObjectSearch();
            $qr_search_result = $o_search->search($vs_query);
            if($vs_type === 'multimedia'){
                $va_mediaIDs = [];
                while($qr_search_result->nextHit()){
                    $vo_record = new ca_objects($qr_search_result->get("object_id"));
                    $va_repIDs = $vo_record->getRepresentationIDs();
                    $va_mediaIDs = array_merge($va_mediaIDs, array_keys($va_repIDs));
                }
                if(count($va_mediaIDs) > 0){
                    $qr_search_result = caMakeSearchResult("ca_object_representations", $va_mediaIDs);
                }
            }
            $vs_file_name = $vs_collection_code.$vs_file_name_extension;
            // Move current export file to archive, labelling it with the previous week's date
            if(!file_exists($vs_rss_dir.'exportArchive')){
                mkdir($vs_rss_dir.'exportArchive');
            }
            if(file_exists($vs_rss_dir.$vs_file_name)){
                $vs_file_mod_time = date("Ymd\_H:i:s\_", filemtime($vs_rss_dir.$vs_file_name));
                rename($vs_rss_dir.$vs_file_name, $vs_rss_dir.'exportArchive/'.$vs_file_mod_time.$vs_file_name);
            }
            // Export new data
            print "Exporting data for {$vs_collection_code}\n";
            $va_errors = [];
            $vo_exporter = ca_data_exporters::loadExporterFromFile($vs_exporter_dir.'/'.$vf_exporter, $va_errors);
            $vs_exporter_code = $vo_exporter->get('exporter_code');
            ca_data_exporters::exportRecordsFromSearchResult($vs_exporter_code, $qr_search_result, $vs_rss_dir.$vs_file_name, ['logLevel' => KLogger::DEBUG, 'logDirectory' => __CA_BASE_DIR__.'/app/plugins/iDigBioExport/logs', 'showCLIProgressBar' => True]);
			return $vs_file_name;
        }
	}
