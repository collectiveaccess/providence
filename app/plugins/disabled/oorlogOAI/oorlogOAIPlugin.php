<?php
/* ----------------------------------------------------------------------
 * titleGeneratorPlugin.php : 
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_list_item_labels.php');
 	
 
	class oorlogOAIPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Transforms OAI input as needed for the Oorlog in Blik project.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/oorlogOAI.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the titleGeneratorPlugin plugin always initializes ok
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
		 * 
		 */
		public function hookOAIPreprocessRecord(&$pa_params) {
			$t_list = new ca_lists();
			
			$va_record = $pa_params['record'];
			
			$vn_yes_id = $t_list->getItemIDFromList('yes_no', 'yes');
			$vn_no_id = $t_list->getItemIDFromList('yes_no', 'no');
					
			switch($pa_params['url']) {
				// ------------------------------------
				// Rotterdam
				// ------------------------------------
				case 'http://hosting25.deventit.nl/rdam/AtlantisPubliek/oai.axd':	
				
	
		//http://hosting25.deventit.nl/rdam/httphandler/BB-1908.mov?file=135414891
					// clean up relations
					if(is_array($va_record['relation'])) {
						$va_checked_relations = array();
						foreach($va_record['relation'] as $vn_i => $vs_url) {
							$vs_url = preg_replace("![^\w\.\-\_:\/\?\&\=]+!", "", $vs_url);
							if (preg_match('!\.mp4!', $vs_url) || preg_match('!\.mov!', $vs_url)) {
								$va_checked_relations[] = $vs_url;
								$va_record['url'] = $vs_url;
								break;
							}
						}
						if (!sizeof($va_checked_relations)) { 
							// If no valid video then skip record
							return false;
						}
						$va_record['relation'] = $va_checked_relations;
					} else {
						// If no valid video then skip record
						return false;
					}
								
					// source_id
					$va_record['source_id'] = $t_list->getItemIDFromList('object_sources', 'rotterdam');
					
										
					// Transform "color" value into yes/no list item_ids
					$vb_has_color = false;
					$vb_has_sound = false;
					if(is_array($va_record['format'])) {
						foreach($va_record['format'] as $vn_i => $vs_format) {
							if (preg_match('!kleur!i', $vs_format)) {
								$vb_has_color = true;
							}
							if (preg_match('!geluid!i', $vs_format)) {
								$vb_has_sound = true;
							}
						}
					}
					
					$va_record['medium'] = $vb_has_color ? $vn_yes_id : $vn_no_id;
					$va_record['sound'] = $vb_has_sound ? $vn_yes_id : $vn_no_id;
					
					$va_record['_alt_identifer'] = $va_record['oai_identifier'];		// allow lookup of existing records using OAI header IDs (to fix earlier harvest)
					break;
				// ------------------------------------
				//  De Ree
				// ------------------------------------
				case 'http://www.archieven.nl/pls/test/!pck_oai_pmh.OAIHandler':	
					// clean up relations
					if(is_array($va_record['relation'])) {
						$va_checked_relations = $va_thumbnail_urls = array();
						foreach($va_record['relation'] as $vn_i => $vs_url) {
							$vs_url = preg_replace("![^\w\.\-\_:\/\?\&\=]+!", "", $vs_url);
							
							if (preg_match('!videothumb\.php!i', $vs_url)) {
								//$va_thumbnail_urls[] = $vs_url;
								continue;
							}
							
							if (preg_match('!\.mp4!', $vs_url) || preg_match('!\.mov!', $vs_url)) {
								$va_checked_relations[] = $vs_url;
								$va_thumbnail_urls = $va_record['url'] = $vs_url;
							}
						}
						
						if (!sizeof($va_checked_relations)) { 
							// If no valid video then skip record
							return false;
						}
						$va_record['relation'] = $va_thumbnail_urls;
						
						// Transform "color" value into yes/no list item_ids
						$vb_has_color = false;
						$vb_has_sound = false;
						if(is_array($va_record['format.medium'])) {
							foreach($va_record['format.medium'] as $vn_i => $vs_format) {
								if (preg_match('!kleur!i', $vs_format)) {
									$vb_has_color = true;
								}
							}
						}
						
						if(is_array($va_record['format.sound'])) {
							foreach($va_record['format.sound'] as $vn_i => $vs_format) {
								if (preg_match('!ja!i', $vs_format)) {
									$vb_has_sound = true;
								}
							}
						}
						
						if(is_array($va_record['description'])) {
							$vs_condensed_desc = '';
							foreach($va_record['description'] as $vn_i => $vs_desc) {
								$vs_condensed_desc .= $vs_desc."\n";
							}
							$va_record['description'] = $vs_condensed_desc;
						}
						
						$va_record['extent'] = $va_record['format.extent'];
						
						$va_record['medium'] = $vb_has_color ? $vn_yes_id : $vn_no_id;
						$va_record['sound'] = $vb_has_sound ? $vn_yes_id : $vn_no_id;	
						
						// source_id
						$va_record['source_id']  = null;
						if (is_array($va_record['publisher']) && sizeof($va_record['publisher'])) {
							$vs_inst_name = array_shift($va_record['publisher']);
							switch($vs_inst_name) {
								case 'Drents Archief':
									$vs_inst = 'drents';
									break;
								case 'Noord-Hollands Archief':
									$vs_inst = 'noord_holland';
									break;
								case 'Fries Film Archief':
									$vs_inst = 'fries';
									break;
								case 'Groninger Archieven':
									$vs_inst = 'gronings';
									break;
								case 'Gelders Archief':
									$vs_inst = 'Gelders';
									break;
								default:
									$vs_inst = '';
									break;
								
							}
							
							if ($vs_inst) {
								$t_item = new ca_list_items();
								if ($t_item->load(array('idno' => $vs_inst))) {
									$va_record['source_id'] = $t_item->get('item_id');
								}
							} else {
								$va_record['source_id'] = null;
							}
							
							if (!$va_record['identifier']) { $va_record['identifier'] = $va_record['oai_identifier']; }
						}
					} else {
						// If no valid video then skip record
						return false;
					}
					break;
				// ------------------------------------
			}
			
			$pa_params['record'] = $va_record;
			//print_R($pa_params);
			
			return $pa_params;
		}
		# -------------------------------------------------------
	}
?>