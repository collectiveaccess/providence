<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseApplicationTool.php : 
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
require_once(__CA_MODELS_DIR__.'/ca_locales.php');
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 
	class pbcImportSipTool extends BaseApplicationTool {
		# -------------------------------------------------------
		
		/**
		 * Settings delegate - implements methods for setting, getting and using settings
		 */
		public $SETTINGS;
		
		/**
		 * Name for tool. Must be unique to the tool.
		 */
		protected $ops_tool_name = 'PBC Tools';
		
		/**
		 * Identifier for tool. Usually the same as the class name. Must be unique to the tool.
		 */
		protected $ops_tool_id = 'pbcImportSIPTool';
		
		/**
		 * Description of tool for display
		 */
		protected $ops_description = 'Import PBC SIP files (ZIP format) into CollectiveAccess';
		# -------------------------------------------------------
		/**
		 * Set up tool and settings specifications
		 */
		public function __construct($pa_settings=null, $ps_mode='CLI') {
			$this->opa_available_settings = array(
				'import_directory' => array(
					'formatType' => FT_TEXT,
					'displayType' => DT_FILE_BROWSER,
					'width' => 100, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'label' => _t('Import directory'),
					'description' => _t('Directory containing SIPS to import.')
				)
			);
			
			parent::__construct($pa_settings, $ps_mode, __CA_APP_DIR__.'/plugins/pbcTools/conf/pbcTools.conf');
		}
		# -------------------------------------------------------
		# Commands
		# -------------------------------------------------------
		/**
		 * Import SIPs from specified directory into CollectiveAccess Database
		 */
		public function commandImportSIPs() {
			$o_conf = $this->getToolConfig();
			
			// Get locale from config and translate to numeric code
			$t_locale = new ca_locales();
			$pn_locale_id = $t_locale->localeCodeToID($o_conf->get('locale'));
			
			$o_log = $this->getLogger();
			$o_progress = $this->getProgressBar(0);
			
			if ($o_progress) { $o_progress->start("Starting import"); }
			
			$vs_import_directory = $this->getSetting("import_directory");
			if (!is_readable($vs_import_directory)) {
				if ($o_log) { $o_log->logError($vs_err_msg = _t("Import directory %1 is not readable", $vs_import_directory)); }
				if ($o_progress) { $o_progress->setError($vs_err_msg); $o_progress->finish(); }
				return false;	
			}
			if ($o_log) { $o_log->logDebug(_t("Started SIP import")); }
			
			// Look for ZIP files
			$va_files = caGetDirectoryContentsAsList($vs_import_directory);
			
			$vn_count = 0;
			foreach($va_files as $vs_file) {
				if (!preg_match("!\.zip$!i", $vs_file)) { continue; }
				$vn_count++;
			}
			$o_progress->setTotal($vn_count);
			
			foreach($va_files as $vs_file) {
				if (!preg_match("!\.zip$!i", $vs_file)) { continue; }
				
				$o_progress->setMessage(_t('Processing %1', $vs_file));
				
				// unpack ZIP 
				$va_package_files = caGetDirectoryContentsAsList('phar://'.$vs_file.'/', false, false, false, true);
foreach($va_package_files as $vs_package_path) {
				$va_tmp = explode("/", $vs_package_path);
				$vs_package_dir = array_pop($va_tmp);
				$va_archive_files = caGetDirectoryContentsAsList('phar://'.$vs_file.'/'.$vs_package_dir, true);
						
				// Does it look like a SIP?
				$vb_is_sip = false;
				$vs_idno = $vs_zip_path = $vs_category = null;
				$va_sides = array();
			
				foreach($va_archive_files as $vs_archive_file) {
					if ($o_log) { $o_log->logDebug(_t("Testing file %1 for SIP-ness", $vs_archive_file)); }
					if (preg_match("!category.txt$!", $vs_archive_file)) {
						$vb_is_sip = true;
						$va_tmp = explode("/", $vs_archive_file);
						array_pop($va_tmp);	// pop category.txt
						$vs_idno = array_pop($va_tmp);
						$vs_category = file_get_contents($vs_archive_file);
						
						$vs_zip_path = join("/", $va_tmp);
						
						if ($o_log) { $o_log->logInfo(_t("Found SIP %1 with category %2", $vs_archive_dir, $vs_category)); }
						continue;
					}
				}
				if(!$vb_is_sip) {
					if ($o_progress) { $o_progress->setError(_t('File %1 is not a valid SIP', $vs_file)); }
					if ($o_log) { $o_log->logWarn(_t('File %1 is not a valid SIP', $vs_file)); }
					continue; 
				}
				
				$va_track_audio_by_side = $va_track_xml_by_side = $va_side_audio = $va_side_xml = $va_artifacts = array();
				
				// Reset total # of SIPS with contents of ZIP
				$o_progress->setTotal($o_progress->getTotal() - 1 + sizeof($va_archive_files));
			
				foreach($va_archive_files as $vs_archive_file) {
					
				$o_progress->next(_t('Processing %1', $vs_archive_file));
					$vs_file_in_zip = str_replace("phar://{$vs_file}/{$vs_idno}", "", $vs_archive_file);
					
					$va_tmp = explode("/", $vs_file_in_zip);
					
					switch($va_tmp[1]) {
						case 'sides':
							if ($va_tmp[4]) {
								$vs_ext = pathinfo($va_tmp[4], PATHINFO_EXTENSION);
								switch($vs_ext) {
									case 'mp3':
										$va_track_audio_by_side[$va_tmp[2]][] = "phar://{$vs_file}/{$vs_idno}/sides/".$va_tmp[2]."/".$va_tmp[3]."/".$va_tmp[4];
										break;
									case 'xml':
										$va_track_xml_by_side[$va_tmp[2]][] = "phar://{$vs_file}/{$vs_idno}/sides/".$va_tmp[2]."/".$va_tmp[3]."/".$va_tmp[4];
										break;
								}
							} else {
								if ($va_tmp[2]) {
									$vs_ext = pathinfo($va_tmp[3], PATHINFO_EXTENSION);
									
									switch($vs_ext) {
										case 'mp3':
											$va_side_audio[$va_tmp[2]] = $va_tmp[3];
											break;
										case 'xml':
											$va_side_xml[$va_tmp[2]] = $va_tmp[3];
											break;
									}
								}
							}
							break;
						case 'artifacts':
							if (sizeof($va_tmp) == 3) {
								$va_artifacts[] = "phar://{$vs_file}/{$vs_idno}/artifacts/".$va_tmp[2];
							}
							break;
					}
				}
				
				
				// Process
				// Create parent record
				
				$o_progress->setMessage(_t("Creating reel for %1", $vs_idno));
				$va_ids = ca_objects::find(array('idno' => $vs_idno, 'deleted' => 0, 'type_id' => 'reel'), array('returnAs' => 'ids'));
				if (!is_array($va_ids) || !sizeof($va_ids)) { 
					$t_object = new ca_objects();
					$t_object->setMode(ACCESS_WRITE);
					$t_object->set(array(
						'type_id' => 'reel',
						'idno' => $vs_idno
					));
					$vn_object_id = $t_object->insert();
										
					if ($t_object->numErrors()) {
						if ($o_log) { $o_log->logError(_t("Could not add reel record %1: %2", $vs_idno, join("; ", $t_object->getErrors()))); }
					}
					
					$t_object->addLabel(array('name' => $vs_idno), $pn_locale_id, null, true);
					
					if ($t_object->numErrors()) {
						if ($o_log) { $o_log->logError(_t("Could not add label to reel record %1: %2", $vs_idno, join("; ", $t_object->getErrors()))); }
					}
					
					if ($t_object->numErrors()) {
						if ($o_log) { $o_log->logError(_t("Could not add artifct media %1 to reel %2: %3", pathinfo($vs_artifact, PATHINFO_BASENAME), $vs_idno, join("; ", $t_object->getErrors()))); }
					}
				} else {
					if ($o_log) { $o_log->logDebug(_t("Found existing reel record %1 for %2", $va_ids[0], $vs_idno)); }
					$t_object = new ca_objects($va_ids[0]);
					
					if (($vn_image_count = $t_object->numberOfRepresentationsOfClass("image")) > 0) {
						// skip reels that have images already
						if ($o_log) { $o_log->logDebug(_t("Skipped existing reel record %1 because it already has %2 images", $vs_idno, $vn_image_count)); }
						if ($o_progress) { $o_progress->setError(_t("Skipped existing reel record %1 because it already has %2 images", $vs_idno, $vn_image_count)); }
						
						continue;
					}
					
					$t_object->setMode(ACCESS_WRITE);
				}

				
				$o_progress->setMessage(_t("Linking artifact images for %1", $vs_idno));
				foreach($va_artifacts as $vs_artifact) {
					copy($vs_artifact, $vs_tmp_filepath = "/tmp/pbc".md5(time()));
					$t_object->addRepresentation($vs_tmp_filepath, 'front', $pn_locale_id, 0, 1, 1);
					$o_progress->setMessage(_t("Added artifact image %1 for %2", $vs_artifact, $vs_idno));
					
					if ($t_object->numErrors()) {
						if ($o_log) { $o_log->logError(_t("Could not add artifact media %1 to reel %2: %3", pathinfo($vs_artifact, PATHINFO_BASENAME), $vs_idno, join("; ", $t_object->getErrors()))); }
					}
						
					// remove tmp file
					unlink($vs_tmp_filepath);
				}
				
				// Create tracks
				
				$o_progress->setMessage(_t("Creating tracks for %1", $vs_idno));
				foreach($va_track_audio_by_side as $vs_side => $va_tracks) {
					foreach($va_tracks as $vn_i => $vs_audio) {
						$vs_ext = pathinfo($vs_audio, PATHINFO_EXTENSION);
						copy($vs_audio, $vs_tmp_filepath = "/tmp/pbc".md5(time()).".{$vs_ext}");
						
						$vs_track_idno = pathinfo($vs_audio, PATHINFO_FILENAME);
					
						// Does track already exist?
						$va_track_ids = ca_objects::find(array('idno' => $vs_track_idno, 'deleted' => 0), array('returnAs' => 'ids'));
						
				$o_progress->setMessage(_t("Creating %2 track for %1", $vs_track_idno, $vs_category));
						if (!is_array($va_track_ids) || !sizeof($va_track_ids)) { 
							// Create track record
							$t_track = new ca_objects();
							$t_track->setMode(ACCESS_WRITE);
							$va_tmp = explode("/", $vs_audio);
							$t_track->set(array(
								'type_id' => $vs_category,
								'idno' => $vs_track_idno,
								'parent_id' => $vn_object_id
							));
							$vn_track_id = $t_track->insert();
							if ($t_track->numErrors()) {
								if ($o_log) { $o_log->logError(_t("Could not add track %1: %2", $vs_track_idno, join("; ", $t_track->getErrors()))); }
							}
						
							$t_track->addLabel(array('name' => "{$vs_track_idno}"), $pn_locale_id, null, true);
							if ($t_track->numErrors()) {
								if ($o_log) { $o_log->logError(_t("Could not add label to track %1: %2", $vs_track_idno, join("; ", $t_track->getErrors()))); }
							}
						} else {
							if ($o_log) { $o_log->logDebug(_t("Found existing track record %1 for %2", $va_track_ids[0], $vs_track_idno)); }
							$t_track = new ca_objects($va_track_ids[0]);
							if (($vn_audio_count = $t_track->numberOfRepresentationsOfClass("audio")) > 0) {
								// skip tracks that have audio already
								if ($o_log) { $o_log->logDebug(_t("Skip existing track record %1 because it already has %2 audio files", $vs_track_idno, $vn_audio_count)); }
								continue;
							}
						}
						
						//
						// Add track XML
						//
						$o_progress->setMessage(_t("Adding track XML for %1", $vs_track_idno));
						copy($va_track_xml_by_side[$vs_side][$vn_i], $vs_xml_tmppath = "./".pathinfo($va_track_xml_by_side[$vs_side][$vn_i], PATHINFO_BASENAME));
						$t_track->replaceAttribute(
							array(
								'sip_metadata' => $vs_xml_tmppath, 
								'locale_id' => $pn_locale_id
							), 'sip_metadata'
						);
						$t_track->update();
						if ($t_track->numErrors()) {
							if ($o_log) { $o_log->logError(_t("Could not import XML file %1 into track %2: %3", pathinfo($va_track_xml_by_side[$vs_side][$vn_i], PATHINFO_BASENAME), $vs_track_idno, join("; ", $t_track->getErrors()))); }
						}
		
						//
						// Add track audio
						//
						$o_progress->setMessage(_t("Adding track audio for %1", $vs_track_idno));
						
						$t_track->addRepresentation($vs_tmp_filepath, 'front', $pn_locale_id, 0, 1, 1);
						
						if ($t_track->numErrors()) {
							if ($o_log) { $o_log->logError($vs_err_msg = _t("Could not import audio file %1 into track %2: %3", pathinfo($vs_audio, PATHINFO_BASENAME), $vs_track_idno, join("; ", $t_track->getErrors()))); }
							if ($o_progress) { $o_progress->setError($vs_err_msg); }
						}
						
						// Remove tmp files
						unlink($vs_tmp_filepath);	
						unlink($vs_xml_tmppath);
					}
				}
}
			}
			
			
			$o_progress->finish("Completed processing");
			if ($o_log) { $o_log->logDebug(_t("Ended SIP import")); }
			
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
				case 'ImportSIPs':
				default:
				return _t('Import PBC SIP audio digitization packages into CollectiveAccess');
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
				case 'ImportSIPs':
				default:
				return _t('Import PBC SIP audio digitization packages into CollectiveAccess.');
			}
			return _t('No help available for %1', $ps_command);
		}
		# -------------------------------------------------------
	}
?>
