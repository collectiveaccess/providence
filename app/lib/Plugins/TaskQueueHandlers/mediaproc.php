<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/mediaproc.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2026 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/Media.php");
include_once(__CA_LIB_DIR__."/Media/MediaVolumes.php");
include_once(__CA_LIB_DIR__."/Media/MediaProcessingSettings.php");
include_once(__CA_LIB_DIR__."/Datamodel.php");
include_once(__CA_LIB_DIR__."/ApplicationError.php");

/**
 * TaskQueue handler plugin for deferred processing of uploaded media in FT_MEDIA fields
 */	
class WLPlugTaskQueueHandlermediaproc Extends WLPlug Implements IWLPlugTaskQueueHandler {
	# --------------------------------------------------------------------------------
	
	public $error;

	# --------------------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->error = new ApplicationError();
		$this->error->setErrorOutput(0);
	}
	# --------------------------------------------------------------------------------
	/**
	 * Returns name/short description for this particular task queue plugin
	 *
	 * @return string Name - actually more of a short description - of this task queue plugin
	 */
	public function getHandlerName() {
		return _t("Media file processor");
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
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		$params = array();
		
		$params['input_format'] = array(
			'label' => _t('Format'),
			'value' => Media::getTypenameForMimetype($parameters["INPUT_MIMETYPE"])
		);
		if (file_exists($parameters["FILENAME"]) && ($size = filesize($parameters["FILENAME"]))) {
			$params['input_file_size'] = array(
				'label' => _t('Filesize'),
				'value' => sprintf("%s", caFormatFileSize($size))
			);
		}
		$params['table'] = array(
			'label' => _t('Source'),
			'value' => $parameters["TABLE"].':'.$parameters["FIELD"].':'.$parameters["PK_VAL"]
		);
		
		if (is_array($parameters["VERSIONS"])) {
			$params['version'] = array(
				'label' => _t('Versions'),
				'value' => join(", ", array_keys($parameters["VERSIONS"]))
			);
		}
		
		if (is_array($parameters["RULES"] ?? null) && sizeof($parameters["RULES"]) > 0) {
			$vs_rules = '';
		
			foreach($parameters["RULES"] as $vs_rule => $rule_info) {
				$vs_params .= "\t".$vs_rule." => ";
				$rule_list = array();
				foreach($rule_info as $vs_key => $vs_val) {
					$rule_list[] = "$vs_key = $vs_val";
				}
				$vs_rules = join("; ", $rule_list)."\n";
			}
			$params['rule_list'] = array(
				'label' => _t('Media processing rules'),
				'value' => $vs_rules
			);
		}
		
		return $params;
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
	public function process($parameters) {
		$table = 		$parameters["TABLE"];				// name of table of record we're processing
		$field = 		$parameters["FIELD"];				// name of field in record we're processing
		$pk = 			$parameters["PK"];					// Field name of primary key of record we're processing
		$id = 			$parameters["PK_VAL"];				// Value of primary key
		
		$o_log = caGetLogger();
		
		$input_mimetype = $parameters["INPUT_MIMETYPE"];		// Mimetype of input file
		$input_file = 		$parameters["FILENAME"];			// Full path to input file
		// Array of media versions to process; keys are version names, 
		// values are arrays with info about processing for that version
		// Currently the info array contains a single key, 'VOLUME', which indicates 
		// the volume the version should be written to
		$versions = 		$parameters["VERSIONS"];				
		
		$options = 		$parameters["OPTIONS"];				// Array of processing options; names of options to employ are keys, settings are values
		
		// If true, then input media is *not* deleted
		$dont_delete_original_media = (bool)$parameters["DONT_DELETE_OLD_MEDIA"];
		
		$report = array('errors' => array(), 'notes' => array());
		
		$o_media_volumes 			= new MediaVolumes();
		$o_media 					= new Media();
		$o_media_proc_settings 		= new MediaProcessingSettings($table, $field);
		
		$media_type 				= $o_media_proc_settings->canAccept($input_mimetype);
		$version_info 				= $o_media_proc_settings->getMediaTypeVersions($media_type);
		
		$report = ['errors' => [], 'notes' => [], 'count' => 0];
		
		if(!file_exists($input_file)) {
			$msg = _t("Record %1.field = file '%2' did not exist; queued file was discarded", $table, $input_file);
			$report['errors'][] = $msg;
			$o_log->logError("[TaskQueue] {$msg}");
			return $report;
		}
		
		if ($t_instance = Datamodel::getInstanceByTableName($table, true)) {
			if ($t_instance->hasField($field)) {
				if (!$t_instance->load($id)) {
					# record no longer exists
					if (!$dont_delete_original_media) {
						@unlink($input_file);
					}
					$o_media->cleanup();
					
					$msg = _t("Record %1.field = %2 did not exist; queued file was discarded", $table, $id);
					$report['errors'][] = $msg;
					$o_log->logError("[TaskQueue] {$msg}");
					
					return $report;
				}
			} else {
				# bad field name
				$msg = _t("Invalid media field '%1' in table '%2'", $field, $table);
				$o_log->logError("[TaskQueue] {$msg}");
				$this->error->setError(551, $msg,"mediaproc->process()");	
				return false;
			}
		} else {
			# bad table name
			$msg = _t("Invalid media field table '%1'", $table);
			$o_log->logError("[TaskQueue] {$msg}");
			$this->error->setError(550, $msg, "mediaproc->process()");	
			return false;
		}
		
		$old_media_to_delete = array();
		if(!file_exists($input_file)) {
			$msg = _t("Input media file '%1' does not exist", $input_file);
			$o_log->logError("[TaskQueue] {$msg}");
			$this->error->setError(505, $msg, "mediaproc->process()");
			$o_media->cleanup();
			return false;
		}
		if(!is_readable($input_file)) {
			$msg = _t("Denied permission to read input media file '%1'", $input_file);
			$o_log->logError("[TaskQueue] {$msg}");
			$this->error->setError(506, $msg,"mediaproc->process()");
			$o_media->cleanup();
			return false;
		}
		
		$media_desc = $t_instance->getMediaInfo('media');
		foreach($versions as $v => $version_settings) {
			$use_icon = null;
							
			if (!$o_media->read($input_file)) {
				$msg = _t("Could not process input media file '%1': %2", $input_file, join('; ', $o_media->getErrors()));
				$o_log->logError("[TaskQueue] {$msg}");
				$this->error->setError(1600, $msg, "mediaproc->process()");
				$o_media->cleanup();
				return false;
			}
			
			$rule 			= isset($version_info[$v]['RULE']) ? $version_info[$v]['RULE'] : '';
			$rules 			= $o_media_proc_settings->getMediaTransformationRule($rule);
			$volume_info 	= $o_media_volumes->getVolumeInformation($version_settings['VOLUME']);
			
			if (!is_array($rules) || (sizeof($rules) == 0)) { 
				$output_mimetype = $input_mimetype; 
				#
				# don't process this media, just copy the file
				#
				$ext = Media::getExtensionForMimetype($output_mimetype);
			
				if (!$ext) {
					$msg = _t("File could not be copied for %1; can't convert mimetype %2 to extension", $field, $output_mimetype);
					$o_log->logError("[TaskQueue] {$msg}");
					$this->error->setError(1600, $msg, "mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				
				if (($dirhash = $this->_getDirectoryHash($volume_info["absolutePath"], $id)) === false) {
					$msg = _t("Couldn't create subdirectory for file for %1", $field);
					$o_log->logError("[TaskQueue] {$msg}");
					$this->error->setError(1600, $msg, "mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				$magic = rand(0,99999);
				$filepath = $volume_info["absolutePath"]."/".$dirhash."/".$magic."_".$table."_".$field."_".$id."_".$v.".".$ext;
				
				if (!copy($input_file, $filepath)) {
					$msg =  _t("File could not be copied for %1", $field);
					$o_log->logError("[TaskQueue] {$msg}");
					$this->error->setError(504, $msg, "mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				
				if (is_array($volume_info["mirrors"]) && sizeof($volume_info["mirrors"]) > 0) {
					$entity_key = join("/", array($table, $field, $id, $v));
					$row_key = join("/", array($table, $id));
					foreach ($volume_info["mirrors"] as $mirror_code => $mirror_info) {
						$mirror_method = $mirror_info["method"];
						$queue = $mirror_method."mirror";
						$tq = new TaskQueue();
						if (!($tq->cancelPendingTasksForEntity($entity_key))) {
							$msg = _t("Could not cancel pending tasks");
							$o_log->logError("[TaskQueue] {$msg}");
							$this->error->setError(560, $msg, "mediaproc->process()");
							$o_media->cleanup();
							return false;
						}
						if ($tq->addTask(
							$queue,
							array(
								"MIRROR" => $mirror_code, 
								"VOLUME" => $version_settings['VOLUME'], 
								"FIELD" => $field, "TABLE" => $table,
								"VERSION" => $v, 
								"FILES" => array(
									array(
										"FILE_PATH" => $filepath,
										"ABS_PATH" => $volume_info["absolutePath"],
										"HASH" => $dirhash,
										"FILENAME" => $magic."_".$table."_".$field."_".$id."_".$v.".".$ext
									)
								),
								
								"MIRROR_INFO" => $mirror_info,
								
								"PK" => $pk,
								"PK_VAL" => $id
							), 
							array("priority" => 100, "entity_key" => $entity_key, "row_key" => $row_key))) 
						{
							continue;
						} else {
							$msg = _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $mirror_method, $v, $queue);
							$o_log->logError("[TaskQueue] {$msg}");
							$this->error->setError(100, $msg, "mediaproc->process()");
						}
				
					}
				}
				
				$media_desc[$v] = array(
					"VOLUME" => $version_settings['VOLUME'],	
					"MIMETYPE" => $output_mimetype,
					"WIDTH" => $o_media->get("width"),
					"HEIGHT" => $o_media->get("height"),
					"PROPERTIES" => $o_media->getProperties(),
					"FILENAME" => $table."_".$field."_".$id."_".$v.".".$ext,
					"HASH" => $dirhash,
					"MAGIC" => $magic,
					"EXTENSION" => $ext,
					"MD5" => md5_file($filepath),
					"FILE_LAST_MODIFIED" => filemtime($filepath)
				);
			} else {
				$o_media->set('version', $v);
				foreach($rules as $operation => $parameters){
					if ($operation === 'SET') {
						foreach($parameters as $pp => $pv) {
							if ($pp == 'format') {
								$output_mimetype = $pv;
							} else {
								$o_media->set($pp, $pv);
							}
						}
					} else {
						if(is_array($media_center = $t_instance->getMediaCenter($field))) {
							$parameters['_centerX'] = caGetOption('x', $media_center, 0.5);
							$parameters['_centerY'] = caGetOption('y', $media_center, 0.5);
					
							if (($parameters['_centerX'] < 0) || ($parameters['_centerX'] > 1)) { $parameters['_centerX'] = 0.5; }
							if (($parameters['_centerY'] < 0) || ($parameters['_centerY'] > 1)) { $parameters['_centerY'] = 0.5; }
						}
						if (!($o_media->transform($operation, $parameters))) {
						  $this->error = $o_media->errors[0];
						  
						  $msg = $this->error->getErrorMessage();
						  $o_log->logError("[TaskQueue] {$msg}");
						  $o_media->cleanup();
						  return false;
						}
					}
				}
				
				if (!$output_mimetype) { $output_mimetype = $input_mimetype; }
				
				if (!($ext = Media::getExtensionForMimetype($output_mimetype))) {
					$msg = _t("File could not be processed for %1; can't convert mimetype %2 to extension", $field, $output_mimetype);
					$o_log->logError("[TaskQueue] {$msg}");
					$this->error->setError(1600, $msg, "mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				
				if (($dirhash = $this->_getDirectoryHash($volume_info["absolutePath"], $id)) === false) {
					$msg = _t("Couldn't create subdirectory for file for %1", $field);
					$o_log->logError("[TaskQueue] {$msg}");
					$this->error->setError(1600, $msg, "mediaproc->process()");
					$o_media->cleanup();
					return false;
				}
				$magic = rand(0,99999);
				$filepath = $volume_info["absolutePath"]."/".$dirhash."/".$magic."_".$table."_".$field."_".$id."_".$v;
			
				if (!($output_file = $o_media->write($filepath, $output_mimetype, $options))) {
					$this->error = $o_media->errors[0];
					$o_media->cleanup();
					return false;
				} else {
					if (
							($output_file === __CA_MEDIA_VIDEO_DEFAULT_ICON__)
							||
							($output_file === __CA_MEDIA_AUDIO_DEFAULT_ICON__)
							||
							($output_file === __CA_MEDIA_DOCUMENT_DEFAULT_ICON__)
					) {
						$use_icon = $output_file;
					} else {
						$output_files[] = $output_file;
					}
				}
				
				if (is_array($volume_info["mirrors"]) && sizeof($volume_info["mirrors"]) > 0) {
					$entity_key = join("/", array($table, $field, $id, $v));
					$row_key = join("/", array($table, $id));
					foreach ($volume_info["mirrors"] as $mirror_code => $mirror_info) {
						$mirror_method = $mirror_info["method"];
						$queue = $mirror_method."mirror";
						$tq = new TaskQueue();
						if (!($tq->cancelPendingTasksForEntity($entity_key))) {
							$msg = _t("Could not cancel pending tasks");
							$o_log->logError("[TaskQueue] {$msg}");
							$this->error->setError(560, $msg, "mediaproc->process()");
							$o_media->cleanup();
							return false;
						}
						if ($tq->addTask(
							$queue,
							array(
								"MIRROR" => $mirror_code, 
								"VOLUME" => $version_settings['VOLUME'], 
								"FIELD" => $field, "TABLE" => $table,
								"VERSION" => $v, 
								"FILES" => array(
									array(
										"FILE_PATH" => $filepath,
										"ABS_PATH" => $volume_info["absolutePath"],
										"HASH" => $dirhash,
										"FILENAME" => $magic."_".$table."_".$field."_".$id."_".$v.".".$ext
									)
								),
								
								"MIRROR_INFO" => $mirror_info,
								
								"PK" => $pk,
								"PK_VAL" => $id
							), 
							array("priority" => 100, "entity_key" => $entity_key, "row_key" => $row_key))) 
						{
							continue;
						} else {
							$msg = _t("Couldn't queue mirror using '%1' for version '%2' (handler '%3')", $mirror_method, $v, $queue);
							$o_log->logError("[TaskQueue] {$msg}");
							$this->error->setError(100, $msg, "mediaproc->process()");
						}
				
					}
				}
				
				if ($use_icon) {
					$media_desc[$v] = array(
						"MIMETYPE" => $output_mimetype,
						"USE_ICON" => $use_icon,
						"WIDTH" => $o_media->get("width"),
						"HEIGHT" => $o_media->get("height")
					);
				} else {
					$media_desc[$v] = array(
						"VOLUME" => $version_settings['VOLUME'],					
						"MIMETYPE" => $output_mimetype,
						"WIDTH" => $o_media->get("width"),
						"HEIGHT" => $o_media->get("height"),
						"PROPERTIES" => $o_media->getProperties(),
						"FILENAME" => $table."_".$field."_".$id."_".$v.".".$ext,
						"HASH" => $dirhash,
						"MAGIC" => $magic,
						"EXTENSION" => $ext,
						"MD5" => md5_file($filepath.".".$ext),
						"FILE_LAST_MODIFIED" => filemtime($filepath.".".$ext)
					);
				}
			}
			if (!$dont_delete_original_media) {
				$old_media_path = $t_instance->getMediaPath($field, $v);
				if (($old_media_path) && ($filepath.".".$ext != $old_media_path) && ($input_file != vs_old_media_path)) {
					$old_media_to_delete[] = $old_media_path;
				}
			}
		}
		
		#
		# Update record
		#
		if ($t_instance->load($id)) {
			if (method_exists($t_instance, "useBlobAsMediaField")) {	// support for attributes - force field to be FT_MEDIA
				$t_instance->useBlobAsMediaField(true); 
			}
			$md = $t_instance->get($field, array('returnWithStructure' => true, 'returnAsArray' => true));
			$merged_media_desc = is_array($md) ? array_shift($md) : array();
			foreach($media_desc as $k => $v) {
				$merged_media_desc[$k] = $v;
			}
			
			$t_instance->setMediaInfo($field, $merged_media_desc);
			
			try {
				$t_instance->update(['force' => true, 'dontImportEmbeddedMetadata' => true]);
				if ($t_instance->numErrors()) {
					# get rid of files we just created
					foreach($output_files as $to_delete) {
						@unlink($to_delete); 
					}
					$msg = _t("Could not update %1.%2: %3", $table, $field, join(", ", $t_instance->getErrors()));
					$o_log->logError("[TaskQueue] {$msg}");
					$this->error->setError(560, $msg, "mediaproc->process()");
					$o_media->cleanup();
					return false;
				} 
				$report['notes'][] = _t("Processed file %1", $input_file);
			} catch(MediaExistsException $e) {
				$report['errors'][] = _t("Skipping file %1 because it already exists and duplicated are not allowed", $input_file);
				return $report;
			}
			
			
			// Generate preview frames for media that support that (Eg. video)
			// and add them as "multifiles" assuming the current model supports that (ca_object_representations does)
			$o_config = Configuration::load();
			if (((bool)$o_config->get('video_preview_generate_frames') || (bool)$o_config->get('document_preview_generate_pages')) && method_exists($t_instance, 'addFile')) {
				if (method_exists($t_instance, 'removeAllFiles')) {
					$t_instance->removeAllFiles();                // get rid of any previously existing frames (they might be hanging ar
				}
				$o_media->read($input_file);
				$preview_frame_list = $o_media->writePreviews(
					array(
						'writeAllPages' => true,
						'width' => $o_media->get("width"), 
						'height' => $o_media->get("height"),
						'numberOfFrames' => $o_config->get('video_preview_max_number_of_frames'),
						'numberOfPages' => $o_config->get('document_preview_max_number_of_pages'),
						'frameInterval' => $o_config->get('video_preview_interval_between_frames'),
						'pageInterval' => $o_config->get('document_preview_interval_between_pages'),
						'startAtTime' => $o_config->get('video_preview_start_at'),
						'endAtTime' => $o_config->get('video_preview_end_at'),
						'startAtPage' => $o_config->get('document_preview_start_page'),
						'outputDirectory' => __CA_TEMP_DIR__
					)
				);
				if (is_array($preview_frame_list)) {
					foreach($preview_frame_list as $time => $frame) {
						$t_instance->addFile($frame, $time, true);	// the resource path for each frame is it's time, in seconds (may be fractional) for video, or page number for documents
						@unlink($frame);		// clean up tmp preview frame file
					}
				}
			}
			
			
			
			if (!$dont_delete_original_media) {
				@unlink($input_file);
			}
			
			foreach($old_media_to_delete as $to_delete) {
				@unlink($to_delete);
			}
			
			$o_media->cleanup();
		} else {
			# record no longer exists
			if (!$dont_delete_original_media) {
				@unlink($input_file);
			}
			$msg = _t("Record {$table}.field = {$id} did not exist; queued file was discarded");
			$o_log->logError("[TaskQueue] {$msg}");
			$o_media->cleanup();
			
			$report['errors'][] = $msg;
		}
		return $report;
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
