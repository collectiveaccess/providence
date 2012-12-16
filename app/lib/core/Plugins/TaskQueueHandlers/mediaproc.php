<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/TaskQueueHandlers/mediaproc.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2012 Whirl-i-Gig
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
 * @subpackage TaskQueue
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
/**
 * TaskQueue handler plugin for deferred processing of uploaded media in FT_MEDIA fields
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaProcessingSettings.php");
include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Error.php");
include_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
	
	class WLPlugTaskQueueHandlermediaproc Extends WLPlug Implements IWLPlugTaskQueueHandler {
		# --------------------------------------------------------------------------------
		
		public $error;
	
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function __construct() {
			$this->error = new Error();
			$this->error->setErrorOutput(0);
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns name/short description for this particular task queue plugin
		 *
		 * @return string Name - actually more of a short description - of this task queue plugin
		 */
		public function getHandlerName() {
			return _t("Background media file processor");
		}
		# --------------------------------------------------------------------------------
		/**
		 * Extracts and returns printable parameters for the queue record passed in $pa_rec
		 * This is used by utilties that present information on the task queue to show details of each queued task
		 * without having to know specifics about the type of task.
		 *
		 * @param array $pa_rec An raw database record array for the queued task (eg. each key a field in ca_task_queue and the values are raw database data that has not been manipulated or unserialized)
		 * @return array An array of printable parameters for the task; array keys are parameter codes, values are arrays with two keys: 'label' = a printable parameter description'; 'value' is a printable parameter setting
		 */
		public function getParametersForDisplay($pa_rec) {
			$va_parameters = caUnserializeForDatabase($pa_rec["parameters"]);
			$va_params = array();
			
			$va_params['input_format'] = array(
				'label' => _t('Input format'),
				'value' => $va_parameters["INPUT_MIMETYPE"]
			);
			if (file_exists($va_parameters["FILENAME"])) {
				$va_params['input_file_size'] = array(
					'label' => _t('Input file size'),
					'value' => sprintf("%4.2dM", filesize($va_parameters["FILENAME"])/(1024 * 1024))
				);
			}
			$va_params['table'] = array(
				'label' => _t('Data source'),
				'value' => $va_parameters["TABLE"].':'.$va_parameters["FIELD"].':'.$va_parameters["PK_VAL"]
			);
			$va_params['temporary_filename'] = array(
				'label' => _t('Temporary filename'),
				'value' => $va_parameters["FILENAME"]
			);
			
			if (is_array($va_parameters["VERSIONS"])) {
				$va_params['version'] = array(
					'label' => _t('Versions output'),
					'value' => join(", ", array_keys($va_parameters["VERSIONS"]))
				);
			}
			
			if (is_array($va_parameters["RULES"]) && sizeof($va_parameters["RULES"]) > 0) {
				$vs_rules = '';
			
				foreach($va_parameters["RULES"] as $vs_rule => $va_rule_info) {
					$vs_params .= "\t".$vs_rule." => ";
					$va_rule_list = array();
					foreach($va_rule_info as $vs_key => $vs_val) {
						$va_rule_list[] = "$vs_key = $vs_val";
					}
					$vs_rules = join("; ", $va_rule_list)."\n";
				}
				$va_params['rule_list'] = array(
					'label' => _t('Media processing rules'),
					'value' => $vs_rules
				);
			}
			
			return $va_params;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Method invoked when the task queue needs to actually execute the task. For mediaproc this means
		 * actually doing the processing of media!
		 *
		 * Return false on failure/error and sets the error property with an error description. Returns an array
		 * with processing details on success.
		 *
		 * @param array $pa_parameters An unserialized parameters array for the current task (eg. unserialized data from ca_task_queue.parameters)
		 * @return array Returns false on error, or an array with processing details on success
		 */
		public function process($pa_parameters) {
			$vs_table = 		$pa_parameters["TABLE"];				// name of table of record we're processing
			$vs_field = 		$pa_parameters["FIELD"];				// name of field in record we're processing
			$vs_pk = 			$pa_parameters["PK"];					// Field name of primary key of record we're processing
			$vn_id = 			$pa_parameters["PK_VAL"];				// Value of primary key
			
			
			$vs_input_mimetype = $pa_parameters["INPUT_MIMETYPE"];		// Mimetype of input file
			$vs_input_file = 		$pa_parameters["FILENAME"];			// Full path to input file
			// Array of media versions to process; keys are version names, 
			// values are arrays with info about processing for that version
			// Currently the info array contains a single key, 'VOLUME', which indicates 
			// the volume the version should be written to
			$va_versions = 		$pa_parameters["VERSIONS"];				
			
			$va_options = 		$pa_parameters["OPTIONS"];				// Array of processing options; names of options to employ are keys, settings are values
			
			// If true, then input media is *not* deleted
			$vb_dont_delete_original_media = (bool)$pa_parameters["DONT_DELETE_OLD_MEDIA"];
			
			$va_report = array('errors' => array(), 'notes' => array());
			
			$o_dm 						= Datamodel::load();
			$o_media_volumes 			= new MediaVolumes();
			$o_media 					= new Media();
			$o_media_proc_settings 		= new MediaProcessingSettings($vs_table, $vs_field);
			
			$vs_media_type 				= $o_media_proc_settings->canAccept($vs_input_mimetype);
			$va_version_info 			= $o_media_proc_settings->getMediaTypeVersions($vs_media_type);
			
			if(!file_exists($vs_input_file)) {
				$o_eventlog = new EventLog();
				$o_eventlog->log(array(
					"CODE" => "DEBG",
					"SOURCE" => "TaskQueue->mediaproc->process()",
					"MESSAGE" => "Record $vs_table.field = file '$vs_input_file' did not exist; queued file was discarded"
				));
				$va_report['errors'][] = _t("Record %1.field = file '%2' did not exist; queued file was discarded", $vs_table, $vs_input_file);
				return $va_report;
			}
			
			if ($t_instance = $o_dm->getInstanceByTableName($vs_table, true)) {
				if ($t_instance->hasField($vs_field)) {
					if (!$t_instance->load($vn_id)) {
						# record no longer exists
						if (!$vb_dont_delete_original_media) {
							@unlink($vs_input_file);
						}
						
						$o_eventlog = new EventLog();
						$o_eventlog->log(array(
							"CODE" => "DEBG",
							"SOURCE" => "TaskQueue->mediaproc->process()",
							"MESSAGE" => "Record $vs_table.field = $vn_id did not exist; queued file was discarded"
						));
						$o_media->cleanup();
						$va_report['errors'][] = _t("Record %1.field = %2 did not exist; queued file was discarded", $vs_table, $vn_id);
						return $va_report;
					}
				} else {
					# bad field name
					$this->error->setError(551, _t("Invalid media field '%1' in table '%2'", $vs_field, $vs_table),"mediaproc->process()");	
					return false;
				}
			} else {
				# bad table name
				$this->error->setError(550, _t("Invalid media field table '%1'", $vs_table),"mediaproc->process()");	
				return false;
			}
			
			$va_old_media_to_delete = array();
			
			foreach($va_versions as $v => $va_version_settings) {
				$vs_use_icon = null;
				
				if(!file_exists($vs_input_file)) {
					$this->error->setError(505, _t("Input media file '%1' does not exist", $vs_input_file),"mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				if(!is_readable($vs_input_file)) {
					$this->error->setError(506, _t("Denied permission to read input media file '%1'", $vs_input_file),"mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				if (!$o_media->read($vs_input_file)) {
					$this->error->setError(1600, _t("Could not process input media file '%1': %2", $vs_input_file, join('; ', $o_media->getErrors())),"mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				
				$vs_rule 			= isset($va_version_info[$v]['RULE']) ? $va_version_info[$v]['RULE'] : '';
				$va_rules 			= $o_media_proc_settings->getMediaTransformationRule($vs_rule);
				$va_volume_info 	= $o_media_volumes->getVolumeInformation($va_version_settings['VOLUME']);
				
				if (sizeof($va_rules) == 0) { 
					$vs_output_mimetype = $vs_input_mimetype; 
					#
					# don't process this media, just copy the file
					#
					$vs_ext = $o_media->mimetype2extension($vs_output_mimetype);
				
					if (!$vs_ext) {
						$this->error->setError(1600, _t("File could not be copied for %1; can't convert mimetype %2 to extension", $vs_field, $vs_output_mimetype),"mediaproc->process()");
						$o_media->cleanup();
						return false;
					}
					
					if (($vs_dirhash = $this->_getDirectoryHash($va_volume_info["absolutePath"], $vn_id)) === false) {
						$this->error->setError(1600, _t("Couldn't create subdirectory for file for %1", $vs_field),"mediaproc->process()");
						$o_media->cleanup();
						return false;
					}
					$vs_magic = rand(0,99999);
					$vs_filepath = $va_volume_info["absolutePath"]."/".$vs_dirhash."/".$vs_magic."_".$vs_table."_".$vs_field."_".$vn_id."_".$v.".".$vs_ext;
					
					if (!copy($vs_input_file, $vs_filepath)) {
						$this->error->setError(504, _t("File could not be copied for %1", $vs_field),"mediaproc->process()");
						$o_media->cleanup();
						return false;
					}
					
					if (is_array($va_volume_info["mirrors"]) && sizeof($va_volume_info["mirrors"]) > 0) {
						$entity_key = join("/", array($vs_table, $vs_field, $vn_id, $v));
						$row_key = join("/", array($vs_table, $vn_id));
						foreach ($va_volume_info["mirrors"] as $vs_mirror_code => $va_mirror_info) {
							$vs_mirror_method = $va_mirror_info["method"];
							$vs_queue = $vs_mirror_method."mirror";
							$tq = new TaskQueue();
							if (!($tq->cancelPendingTasksForEntity($entity_key))) {
								$this->error->setError(560, _t("Could not cancel pending tasks"),"mediaproc->process()");
								$o_media->cleanup();
								return false;
							}
							if ($tq->addTask(
								$vs_queue,
								array(
									"MIRROR" => $vs_mirror_code, 
									"VOLUME" => $va_version_settings['VOLUME'], 
									"FIELD" => $vs_field, "TABLE" => $vs_table,
									"VERSION" => $v, 
									"FILES" => array(
										array(
											"FILE_PATH" => $vs_filepath,
											"ABS_PATH" => $va_volume_info["absolutePath"],
											"HASH" => $vs_dirhash,
											"FILENAME" => $vs_magic."_".$vs_table."_".$vs_field."_".$vn_id."_".$v.".".$vs_ext
										)
									),
									
									"MIRROR_INFO" => $va_mirror_info,
									
									"PK" => $vs_pk,
									"PK_VAL" => $vn_id
								), 
								array("priority" => 100, "entity_key" => $entity_key, "row_key" => $row_key))) 
							{
								continue;
							} else {
								$this->error->setError(100, _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $vs_mirror_method, $v, $vs_queue),"mediaproc->process()");
							}
					
						}
					}
					
					$media_desc[$v] = array(
						"VOLUME" => $va_version_settings['VOLUME'],	
						"MIMETYPE" => $vs_output_mimetype,
						"WIDTH" => $o_media->get("width"),
						"HEIGHT" => $o_media->get("height"),
						"PROPERTIES" => $o_media->getProperties(),
						"FILENAME" => $vs_table."_".$vs_field."_".$vn_id."_".$v.".".$vs_ext,
						"HASH" => $vs_dirhash,
						"MAGIC" => $vs_magic,
						"EXTENSION" => $vs_ext,
						"MD5" => md5_file($vs_filepath)
					);
				} else {
					$o_media->set('version', $v);
					while(list($operation, $pa_parameters) = each($va_rules)) {
						if ($operation === 'SET') {
							foreach($pa_parameters as $pp => $pv) {
								if ($pp == 'format') {
									$vs_output_mimetype = $pv;
								} else {
									$o_media->set($pp, $pv);
								}
							}
						} else {
							if (!($o_media->transform($operation, $pa_parameters))) {
							  $this->error = $o_media->errors[0];
							  $o_media->cleanup();
							  return false;
							}
						}
					}
					
					if (!$vs_output_mimetype) { $vs_output_mimetype = $vs_input_mimetype; }
					
					if (!($vs_ext = $o_media->mimetype2extension($vs_output_mimetype))) {
						$this->error->setError(1600, _t("File could not be processed for %1; can't convert mimetype %2 to extension", $vs_field, $vs_output_mimetype),"mediaproc->process()");
						$o_media->cleanup();
						return false;
					}
					
					if (($vs_dirhash = $this->_getDirectoryHash($va_volume_info["absolutePath"], $vn_id)) === false) {
						$this->error->setError(1600, _t("Couldn't create subdirectory for file for %1", $vs_field),"mediaproc->process()");
						$o_media->cleanup();
						return false;
					}
					$vs_magic = rand(0,99999);
					$vs_filepath = $va_volume_info["absolutePath"]."/".$vs_dirhash."/".$vs_magic."_".$vs_table."_".$vs_field."_".$vn_id."_".$v;
												
					if (!($vs_output_file = $o_media->write($vs_filepath, $vs_output_mimetype, $va_options))) {
						$this->error = $o_media->errors[0];
						$o_media->cleanup();
						return false;
					} else {
						if (
								($vs_output_file === __CA_MEDIA_VIDEO_DEFAULT_ICON__)
								||
								($vs_output_file === __CA_MEDIA_AUDIO_DEFAULT_ICON__)
								||
								($vs_output_file === __CA_MEDIA_DOCUMENT_DEFAULT_ICON__)
						) {
							$vs_use_icon = $vs_output_file;
						} else {
							$va_output_files[] = $vs_output_file;
						}
					}
					
					if (is_array($va_volume_info["mirrors"]) && sizeof($va_volume_info["mirrors"]) > 0) {
						$entity_key = join("/", array($vs_table, $vs_field, $vn_id, $v));
						$row_key = join("/", array($vs_table, $vn_id));
						foreach ($va_volume_info["mirrors"] as $vs_mirror_code => $va_mirror_info) {
							$vs_mirror_method = $va_mirror_info["method"];
							$vs_queue = $vs_mirror_method."mirror";
							$tq = new TaskQueue();
							if (!($tq->cancelPendingTasksForEntity($entity_key))) {
								$this->error->setError(560, _t("Could not cancel pending tasks"),"mediaproc->process()");
								$o_media->cleanup();
								return false;
							}
							if ($tq->addTask(
								$vs_queue,
								array(
									"MIRROR" => $vs_mirror_code, 
									"VOLUME" => $va_version_settings['VOLUME'], 
									"FIELD" => $vs_field, "TABLE" => $vs_table,
									"VERSION" => $v, 
									"FILES" => array(
										array(
											"FILE_PATH" => $vs_filepath,
											"ABS_PATH" => $va_volume_info["absolutePath"],
											"HASH" => $vs_dirhash,
											"FILENAME" => $vs_magic."_".$vs_table."_".$vs_field."_".$vn_id."_".$v.".".$vs_ext
										)
									),
									
									"MIRROR_INFO" => $va_mirror_info,
									
									"PK" => $vs_pk,
									"PK_VAL" => $vn_id
								), 
								array("priority" => 100, "entity_key" => $entity_key, "row_key" => $row_key))) 
							{
								continue;
							} else {
								$this->error->setError(100, _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $vs_mirror_method, $v, $vs_queue),"mediaproc->process()");
							}
					
						}
					}
					
					if ($vs_use_icon) {
						$media_desc[$v] = array(
							"MIMETYPE" => $vs_output_mimetype,
							"USE_ICON" => $vs_use_icon,
							"WIDTH" => $o_media->get("width"),
							"HEIGHT" => $o_media->get("height")
						);
					} else {
						$media_desc[$v] = array(
							"VOLUME" => $va_version_settings['VOLUME'],					
							"MIMETYPE" => $vs_output_mimetype,
							"WIDTH" => $o_media->get("width"),
							"HEIGHT" => $o_media->get("height"),
							"PROPERTIES" => $o_media->getProperties(),
							"FILENAME" => $vs_table."_".$vs_field."_".$vn_id."_".$v.".".$vs_ext,
							"HASH" => $vs_dirhash,
							"MAGIC" => $vs_magic,
							"EXTENSION" => $vs_ext,
							"MD5" => md5_file($vs_filepath.".".$vs_ext)
						);
					}
				}
				if (!$vb_dont_delete_original_media) {
					$vs_old_media_path = $t_instance->getMediaPath($vs_field, $v);
					if (($vs_old_media_path) && ($vs_filepath.".".$vs_ext != $vs_old_media_path) && ($vs_input_file != vs_old_media_path)) {
						//@unlink($t_instance->getMediaPath($vs_field, $v));
						$va_old_media_to_delete[] = $vs_old_media_path;
					}
				}
			}
			
			#
			# Update record
			#
			if ($t_instance->load($vn_id)) {
				if (method_exists($t_instance, "useBlobAsMediaField")) {	// support for attributes - force field to be FT_MEDIA
					$t_instance->useBlobAsMediaField(true); 
				}
				$md = $t_instance->get($vs_field);
				$va_merged_media_desc = is_array($md) ? $md : array();
				foreach($media_desc as $vs_k => $va_v) {
					$va_merged_media_desc[$vs_k] = $va_v;
				}
				$t_instance->setMode(ACCESS_WRITE);
				$t_instance->setMediaInfo($vs_field, $va_merged_media_desc);
				
				$t_instance->update();
				
				if ($t_instance->numErrors()) {
					# get rid of files we just created
					foreach($va_output_files as $vs_to_delete) {
						@unlink($vs_to_delete); 
					}
					$this->error->setError(560, _t("Could not update %1.%2: %3", $vs_table, $vs_field, join(", ", $t_instance->getErrors())), "mediaproc->process()");
					$o_media->cleanup();
					return false;
				} 
				
				$va_report['notes'][] = _t("Processed file %1", $vs_input_file);
				
				
				
				// Generate preview frames for media that support that (Eg. video)
				// and add them as "multifiles" assuming the current model supports that (ca_object_representations does)
				$o_config = Configuration::load();
				if (((bool)$o_config->get('video_preview_generate_frames') || (bool)$o_config->get('document_preview_generate_pages')) && method_exists($t_instance, 'addFile')) {
					$o_media->read($vs_input_file);
					$va_preview_frame_list = $o_media->writePreviews(
						array(
							'width' => $o_media->get("width"), 
							'height' => $o_media->get("height"),
							'numberOfFrames' => $o_config->get('video_preview_max_number_of_frames'),
							'numberOfPages' => $o_config->get('document_preview_max_number_of_pages'),
							'frameInterval' => $o_config->get('video_preview_interval_between_frames'),
							'pageInterval' => $o_config->get('document_preview_interval_between_pages'),
							'startAtTime' => $o_config->get('video_preview_start_at'),
							'endAtTime' => $o_config->get('video_preview_end_at'),
							'startAtPage' => $o_config->get('document_preview_start_page'),
							'outputDirectory' => __CA_APP_DIR__.'/tmp'
						)
					);
					$t_instance->removeAllFiles();		// get rid of any previously existing frames (they might be hanging around if we're editing an existing record)
					if (is_array($va_preview_frame_list)) {
						foreach($va_preview_frame_list as $vn_time => $vs_frame) {
							$t_instance->addFile($vs_frame, $vn_time, true);	// the resource path for each frame is it's time, in seconds (may be fractional) for video, or page number for documents
							@unlink($vs_frame);		// clean up tmp preview frame file
						}
					}
				}
				
				
				
				if (!$vb_dont_delete_original_media) {
					@unlink($vs_input_file);
				}
				
				foreach($va_old_media_to_delete as $vs_to_delete) {
					@unlink($vs_to_delete);
				}
				
				$o_media->cleanup();
				return $va_report;
			} else {
				# record no longer exists
				if (!$vb_dont_delete_original_media) {
					@unlink($vs_input_file);
				}
				
				$o_eventlog = new EventLog();
				$o_eventlog->log(array(
					"CODE" => "DEBG",
					"SOURCE" => "TaskQueue->mediaproc->process()",
					"MESSAGE" => "Record $vs_table.field = $vn_id did not exist; queued file was discarded"
				));
				$o_media->cleanup();
				
				$va_report['errors'][] = _t("Record {$vs_table}.field = {$vn_id} did not exist; queued file was discarded");
				return $va_report;
			}
		}
		# --------------------------------------------------------------------------------
		/**
		 * Cancel function - cancels queued task, doing cleanup and deleting task queue record
		 * all task queue handlers must implement this
		 *
		 * Returns true on success, false on error
		 */
		public function cancel($pn_task_id, $pa_parameters) {
			# delete tmp file
			@unlink($pa_parameters["FILENAME"]);
			
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		private function _getDirectoryHash ($ps_basepath, $pn_id) {
			$n = intval($pn_id / 100);
			$dirs = array();
			$l = strlen($n);
			for($i=0;$i<$l; $i++) {
				$dirs[] = substr($n,$i,1);
				if (!file_exists($ps_basepath."/".join("/", $dirs))) {
					if (!@mkdir($ps_basepath."/".join("/", $dirs))) {
						return false;
					}
				}
			}
			
			return join("/", $dirs);
		}
		# --------------------------------------------------------------------------------
	}
?>