<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/mediaTranscription.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 * TaskQueue handler plugin for transcription of uploaded AV media using OpenAI Whisper
 * See https://github.com/openai/whisper
 */

include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/Media.php");
include_once(__CA_LIB_DIR__."/Media/MediaVolumes.php");
include_once(__CA_LIB_DIR__."/Media/MediaProcessingSettings.php");
include_once(__CA_LIB_DIR__."/Datamodel.php");
include_once(__CA_LIB_DIR__."/ApplicationError.php");
include_once(__CA_LIB_DIR__."/Logging/Eventlog.php");

class WLPlugTaskQueueHandlermediaTranscription Extends WLPlug Implements IWLPlugTaskQueueHandler {
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
		return _t("Background media transcription generator");
	}
	# --------------------------------------------------------------------------------
	/**
	 * Extracts and returns printable parameters for the queue record passed in $rec
	 * This is used by utilties that present information on the task queue to show details of each queued task
	 * without having to know specifics about the type of task.
	 *
	 * @param array $rec A raw database record array for the queued task (eg. each key a field in ca_task_queue and the values are raw database data that has not been manipulated or unserialized)
	 * @return array An array of printable parameters for the task; array keys are parameter codes, values are arrays with two keys: 'label' = a printable parameter description'; 'value' is a printable parameter setting
	 */
	public function getParametersForDisplay($rec) {
		$parameters = caUnserializeForDatabase($rec["parameters"]);
		$params = array();
		
		$params['input_format'] = array(
			'label' => _t('Input format'),
			'value' => $parameters["INPUT_MIMETYPE"]
		);
		
		$params['table'] = array(
			'label' => _t('Data source'),
			'value' => $parameters["TABLE"].':'.$parameters["FIELD"].':'.$parameters["PK_VAL"]
		);
		
		
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
	 * @param array $parameters An unserialized parameters array for the current task (eg. unserialized data from ca_task_queue.parameters)
	 * @return array Returns false on error, or an array with processing details on success
	 */
	public function process($parameters) {
		$table = 		$parameters["TABLE"];				// name of table of record we're processing
		$field = 		$parameters["FIELD"];				// name of field in record we're processing
		$pk = 			$parameters["PK"];					// Field name of primary key of record we're processing
		$id = 			$parameters["PK_VAL"];				// Value of primary key
		
		$logger = caGetLogger(['logLevel' => 'INFO']);
		
		if(($t = Datamodel::getInstance($table)) && $t->load($id)) {
			$media_input = $t->getMediaPath($field, 'original');
			$vtt_output = caGetTempFileName('transcription', 'vtt', ['useAppTmpDir' => true]);
			
			if(!($app_path = caWhisperInstalled())) { 
				$logger->logError(_t("[TaskQueue::mediaTranscription::process] Whisper is not installed (see https://github.com/openai/whisper)", $table, $id));
				$this->error->setError(551, _t("Whisper is not installed (see https://github.com/openai/whisper)"),"mediaTranscription->process()");	
				return false;
			}
			
			caExec("{$app_path} --input={$media_input} --output={$vtt_output}", $output, $return);
			if($return == 0) {
				if(!$t->addCaptionFile($vtt_output, 'en_US')) {
					$logger->logError(_t('[TaskQueue::mediaTranscription::process] Could not add VTT transcription file to %1::%2: %3', $table, $id, join('; ', $t->getErrors())));
					$this->error->setError(551, _t("Could not add VTT transcription file to %1::%2: %3", $table, $id, join('; ', $t->getErrors())),"mediaTranscription->process()");	
				} else {
					@unlink($vtt_output);
					return true;
				}
			} else {
				$logger->logError(_t('[TaskQueue::mediaTranscription::process] Could not transcribe media %1. Return code was %2; message was %3', $media_input, $return, join('; ', $output)));
				$this->error->setError(551, _t("Could not transcribe media %1. Return code was %2; message was %3", $media_input, $return, join('; ', $output)),"mediaTranscription->process()");	
			}
			@unlink($vtt_output);
		} else {
			// Bad table/id
			$logger->logError(_t("[TaskQueue::mediaTranscription::process] Invalid table or id. Table was '%1'; id was '%2'", $table, $id)); 
			$this->error->setError(551, _t("Invalid table or id. Table was '%1'; id was '%2'", $table, $id),"mediaTranscription->process()");	
		}
		return false;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Cancel function - cancels queued task, doing cleanup and deleting task queue record
	 * all task queue handlers must implement this
	 *
	 * Returns true on success, false on error
	 */
	public function cancel($pn_task_id, $parameters) {
		# delete tmp file
		@unlink($parameters["FILENAME"]);
		
		return true;
	}
	# --------------------------------------------------------------------------------
}
