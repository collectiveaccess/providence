<?php
/** ---------------------------------------------------------------------
 * app/plugins/pier21Tools/lib/pier21OralHistoryImportTool.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @subpackage AppPlugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__.'/ca/Utils/BaseApplicationTool.php');
require_once(__CA_LIB_DIR__.'/core/ModelSettings.php');
require_once(__CA_LIB_DIR__.'/core/Db.php');
require_once(__CA_MODELS_DIR__."/ca_storage_locations.php");	
require_once(__CA_MODELS_DIR__."/ca_objects.php");	
require_once(__CA_MODELS_DIR__."/ca_entities.php");
require_once(__CA_MODELS_DIR__."/ca_collections.php");
require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");
require_once(__CA_MODELS_DIR__."/ca_lists.php");
require_once(__CA_MODELS_DIR__."/ca_locales.php");
require_once(__CA_MODELS_DIR__."/ca_data_import_events.php");
require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 
	class pier21OralHistoryImportTool extends BaseApplicationTool {
		# -------------------------------------------------------
		
		/**
		 * Settings delegate - implements methods for setting, getting and using settings
		 */
		public $SETTINGS;
		
		/**
		 * Name of tool. Usuall the same as the class name. Must be unique to the tool
		 */
		protected $ops_tool_name = 'Pier21 Oral History Import Tool';
		
		/**
		 * Identifier for tool. Usually the same as the class name. Must be unique to the tool.
		 */
		protected $ops_tool_id = 'pier21OralHistoryImportTool';
		
		/**
		 * Description of tool for display
		 */
		protected $ops_description = 'Import oral history audio + transcripts CollectiveAccess';
		# -------------------------------------------------------
		/**
		 * Set up tool and settings specifications
		 */
		public function __construct($pa_settings=null, $ps_mode='CLI') {
			$this->opa_available_settings = array(
				'transcript_directory' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FILE_BROWSER,
					'width' => 100, 'height' => 1,
					'takesLocale' => false,
					'default' => '1',
					'label' => _t('Transcript directory'),
					'description' => _t('Directory containing transcripts to import.')
				),
				'audio_directory' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FILE_BROWSER,
					'width' => 100, 'height' => 1,
					'takesLocale' => false,
					'default' => '1',
					'label' => _t('Audio directory'),
					'description' => _t('Directory containing digital audio to import.')
				)
			);
			
			parent::__construct($pa_settings, $ps_mode, __CA_APP_DIR__.'/plugins/pier21Tools/conf/pier21Tools.conf');
		}
		# -------------------------------------------------------
		# Commands
		# -------------------------------------------------------
		/**
		 * Import oral histories from specified directory into CollectiveAccess database
		 */
		public function commandImportOralHistories() {
			$o_conf = $this->getToolConfig();
			
			// Get locale from config and translate to numeric code
			$t_locale = new ca_locales();
			$pn_locale_id = $t_locale->localeCodeToID($o_conf->get('locale'));
			
			$o_log = $this->getLogger();
			$o_progress = $this->getProgressBar(0);
			
			$vs_transcript_dir = $this->getSetting("transcript_directory");
			if (!is_readable($vs_transcript_dir)) {
				if ($o_log) { $o_log->logError($vs_err_msg = _t("Transcript directory %1 is not readable", $vs_transcript_dir)); }
				if ($o_progress) { $o_progress->setError($vs_err_msg); $o_progress->finish(); }
				return false;	
			}
			
			$vs_audio_dir = $this->getSetting("audio_directory");
			if (!is_readable($vs_audio_dir)) {
				if ($o_log) { $o_log->logError($vs_err_msg = _t("Audio directory %1 is not readable", $vs_audio_dir)); }
				if ($o_progress) { $o_progress->setError($vs_err_msg); $o_progress->finish(); }
				return false;	
			}
			
			if ($o_progress) { $o_progress->start("Starting oral history import"); }
			
			// ----------------------------------------------------------------------
			// process main data
			$r_dir = opendir($vs_transcript_dir);
	
			while(($vs_file = readdir($r_dir)) !== false) {
				if ($vs_file[0] == '.') { continue; }
		
				// Get markup and fix it up to be valid XML
				$vs_markup = file_get_contents($vs_transcript_dir.$vs_file);
				$vs_markup = preg_replace('!&!', '&amp;', $vs_markup);
				$vs_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><transcript>{$vs_markup}</transcript>";
		
				try {
					$o_xml = new SimpleXMLElement($vs_xml);
				} catch (Exception $e) {
					$o_log->logError("Could not parse XML transcript for {$vs_transcript_dir}{$vs_file}: ".$e->getMessage());
					continue;
				}
				$vs_idno = (string)$o_xml->identifier;
		
				if(!file_exists($vs_media_path = "{$vs_audio_dir}{$vs_idno}.mp3")) {
					$o_log->logError("No audio file found for {$vs_idno}. File path was {$vs_media_path}");
					continue;
				}
		
				$vs_title = (string)$o_xml->title;
				$vs_date_created = (string)$o_xml->datecreated;
				$vs_format = (string)$o_xml->format;
				$vs_medium = (string)$o_xml->medium;
				$vs_place_recorded = (string)$o_xml->placeRecorded;
				$vs_rights = (string)$o_xml->rights;
				$vs_extent = (string)$o_xml->extent;
				$vs_country = (string)$o_xml->countryOfOrigin;
		
				$va_interviewers = array();
				foreach($o_xml->interviewer as $o_interviewer) {
					$va_interviewers[(string)$o_interviewer->attributes()->abbreviation] = (string)$o_interviewer;
				}
		
				$va_participants = array();
				foreach($o_xml->participant as $o_participant) {
					$va_participants[(string)$o_participant->attributes()->abbreviation] = (string)$o_participant;
				}
		
				$va_observers = array();
				if ($o_xml->observer) {
					foreach($o_xml->observer as $o_observer) {
						$va_observers[] = (string)$o_observer;
					}
				}
		
				// Create object
				$t_object = new ca_objects();
				$t_object->setMode(ACCESS_WRITE);
				if (!$t_object->load(array('idno' => $vs_idno, 'deleted' => 0))) {
					$t_object->set('type_id', 'oral_history');
					$t_object->set('idno', $vs_idno);
					$t_object->set('status', 0);
					$t_object->set('access', 1);
				}
		
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'dc_format' => $vs_format), 'dc_format');
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'dates_value' => $vs_date_created, 'dc_dates_types' => 'created'), 'date');
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'medium' => $vs_medium), 'medium');
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'interview_location' => $vs_place_recorded), 'interview_location');
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'rights' => $vs_rights), 'rights');
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'extent' => $vs_extent), 'extent');
				$t_object->addAttribute(array('locale_id' => $pn_locale_id, 'countryOfOrigin' => $vs_country), 'countryOfOrigin');
		
				if (!$t_object->getPrimaryKey()) {
					$t_object->insert();
					DataMigrationUtils::postError($t_object, 'While inserting object');
					if ($t_object->numErrors()) { $o_log->logError("While adding object for {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
					$t_object->addLabel(array('name' => $vs_title), $pn_locale_id, null, true);
					DataMigrationUtils::postError($t_object, 'While adding object label');
					if ($t_object->numErrors()) { $o_log->logError("While adding object label for {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
				} else {
					$t_object->update();
					DataMigrationUtils::postError($t_object, 'While updating object');
					if ($t_object->numErrors()) { $o_log->logError("While updating object for {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
				}
		
		
				// add entities
				foreach($va_interviewers as $vs_abbr => $vs_name) {
					$vn_entity_id = DataMigrationUtils::getEntityID(DataMigrationUtils::splitEntityName($vs_name), 'ind', $pn_locale_id);
					$t_object->addRelationship('ca_entities', $vn_entity_id, 'interviewer');
					DataMigrationUtils::postError($t_object, "While adding interviewer {$vs_name} to object");
					if ($t_object->numErrors()) { $o_log->logError("While adding interview {$vs_name} to {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
				}
				foreach($va_participants as $vs_abbr => $vs_name) {
					$vn_entity_id = DataMigrationUtils::getEntityID(DataMigrationUtils::splitEntityName($vs_name), 'ind', $pn_locale_id);
					$t_object->addRelationship('ca_entities', $vn_entity_id, 'interviewee');
					DataMigrationUtils::postError($t_object, "While adding interviewee {$vs_name} to object");
					if ($t_object->numErrors()) { $o_log->logError("While adding interviee {$vs_name} to {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
				}
				foreach($va_observers as $vn_i => $vs_name) {
					$vn_entity_id = DataMigrationUtils::getEntityID(DataMigrationUtils::splitEntityName($vs_name), 'ind', $pn_locale_id);
					$t_object->addRelationship('ca_entities', $vn_entity_id, 'observer');
					DataMigrationUtils::postError($t_object, "While adding observer {$vs_name} to object");
					if ($t_object->numErrors()) { $o_log->logError("While adding observer {$vs_name} to {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
				}

				// Add media
		
				$t_rep = $t_object->addRepresentation($vs_media_path, "front", $pn_locale_id, 0, 1, true, array(), array('returnRepresentation' => true));
				DataMigrationUtils::postError($t_object, "While adding representation {$vs_media_path} to object");
				if ($t_object->numErrors()) { $o_log->logError("While adding representation {$vs_media_path} to {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_object->getErrors())); }
				if($t_object->numErrors()) { continue; }
		
				$va_clips = array();
				foreach($o_xml->clip as $o_clip) {
					$vs_content = nl2br(preg_replace('!^[\n\r\t]+!', '', trim((string)$o_clip->asXML())));
					$vs_start = (string)$o_clip->attributes()->start;
					$va_themes = $va_places = array();
					foreach($o_clip->children() as $o_node) {
						$vs_tag = (string)$o_node->getName();
				
						switch($vs_tag) {
							case 'place':
								$va_places[] = (string)$o_node;
								break;
							default:
								$va_themes[] = $vs_tag;
								break;
						}
					}
			
					$va_clips[] = array('start' => $vs_start, 'content' => $vs_content, 'themes' => $va_themes, 'places' => $va_places);
				}
		
				foreach($va_clips as $vn_i => $va_clip) {
					$vs_start = $va_clip['start'];
					if (!($vs_end = $va_clips[$vn_i + 1]['start'])) {
						$va_info = $t_rep->getMediaInfo('media', 'original');
						$vs_end = $va_info['PROPERTIES']['duration'];
					}
			
					//print "[$vs_start/$vs_end] (".join('/', $va_clip['themes'])."); (".join('/', $va_clip['places']).") ".substr($va_clip['content'], 0, 30)."\n\n\n";
					$t_annotation = $t_rep->addAnnotation("{$vs_start} ... {$vs_end}", $pn_locale_id, 1, 
						array('startTimecode' => $vs_start, 'endTimecode' => $vs_end), 
						0, 1,
						array('transcription' => $va_clip['content']),
						array('returnAnnotation' => true)
					);
					DataMigrationUtils::postError($t_rep, "While adding annotation to representation");
					if ($t_rep->numErrors()) { $o_log->logError("While adding annotation {$vs_start}/{$vs_end} to {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_rep->getErrors())); }
			
					if ($t_annotation) {
						foreach($va_clip['themes'] as $vs_theme) {
							$t_annotation->addRelationship('ca_list_items', $vs_theme, 'describes');
							DataMigrationUtils::postError($t_annotation, "While adding theme {$vs_theme} to annotation");
							if ($t_annotation->numErrors()) { $o_log->logError("While adding theme {$vs_theme} to annotation {$vs_start}/{$vs_end} for {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_annotation->getErrors())); }
			
						}
						foreach($va_clip['places'] as $vs_place) {
							if ($vn_place_id = ca_places::find(array('preferred_labels' => array('name' => $vs_place)), array('returnAs' => 'firstId'))) {
								$t_annotation->addRelationship('ca_places', $vn_place_id, 'describes');
								DataMigrationUtils::postError($t_annotation, "While adding place {$vs_place} to annotation");
								if ($t_annotation->numErrors()) { $o_log->logError("While adding place {$vs_place} to annotation {$vs_start}/{$vs_end} for {$vs_transcript_dir}{$vs_file}: ".join("; ", $t_annotation->getErrors())); }
							}
						}
					}
				}
		
				$o_log->logInfo("Imported {$vs_file}");
			}
			
			$o_progress->finish("Completed processing");
			if ($o_log) { $o_log->logDebug(_t("Ended oral history import")); }
			
			return true;
		}
		# -------------------------------------------------------
		# Help
		# -------------------------------------------------------
		/**
		 * Return short help text about a tool command
		 *
		 * @return string 
		 */
		public function getShortHelpText($ps_command) {
			switch($ps_command) {
				case 'ImportOralHistories':
				default:
				return _t('Import Pier21 digitized oral history audio + transcripts CollectiveAccess.');
			}
			return _t('No help available for %1', $ps_command);
		}
		# -------------------------------------------------------
		/**
		 * Return full help text about a tool command
		 *
		 * @return string 
		 */
		public function getHelpText($ps_command) {
			switch($ps_command) {
				case 'ImportOralHistories':
				default:
				return _t('Import Pier21 digitized oral history audio + transcripts CollectiveAccess. You must specify the directories for each type of file.');
			}
			return _t('No help available for %1', $ps_command);
		}
		# -------------------------------------------------------
	}
?>