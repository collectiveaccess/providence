<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/mediaTranscription.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022-2026 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/TranscriptionManager.php");
include_once(__CA_LIB_DIR__."/Plugins/Transcription/BaseTranscriptionPlugin.php");

/**
 * TaskQueue handler plugin for transcription of uploaded AV media. Transcription is performed by
 * plugins in app/lib/Plugins/Transcription (Eg. OpenAI Whisper, Rev.ai, Sonix) selected using the
 * app.conf transcription_service directive.
 */
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
		
		$params['service'] = array(
			'label' => _t('Transcription service'),
			'value' => $parameters["TRANSCRIPTION_SERVICE"] ?? \CA\TranscriptionManager::getDefaultServiceName()
		);
		
		if($parameters["JOB_ID"] ?? null) {
			$params['job_id'] = array(
				'label' => _t('Transcription job'),
				'value' => $parameters["JOB_ID"].' ('._t('attempt %1', $parameters["ATTEMPT"] ?? 1).')'
			);
		}
		
		
		return $params;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Method invoked when the task queue needs to actually execute the task. Media is transcribed using 
	 * the service set in the TRANSCRIPTION_SERVICE parameter, or if not set the service configured in
	 * app.conf transcription_service.
	 *
	 * Asynchronous services that have not completed within transcription_max_wait seconds are re-queued 
	 * as a new task with the service's job id, so that the next attempt resumes polling rather than resubmitting
	 * the media.
	 *
	 * Return false on failure/error and sets the error property with an error description. Returns an array
	 * with processing details on success.
	 *
	 * @param array $parameters An unserialized parameters array for the current task (eg. unserialized data from ca_task_queue.parameters)
	 * @return array Returns false on error, or an array with processing details on success
	 */
	public function process($parameters) {
		$table = 			$parameters["TABLE"] ?? null;				// name of table of record we're processing
		$field = 			$parameters["FIELD"] ?? null;				// name of field in record we're processing
		$pk = 				$parameters["PK"] ?? null;					// Field name of primary key of record we're processing
		$id = 				$parameters["PK_VAL"] ?? null;				// Value of primary key
		$is_service_call = 	$parameters['SERVICE'] ?? null;
		$model =			$parameters['MODEL'] ?? null;
		$output_dir =		$parameters['OUTPUT_DIR'] ?? null;
		$service_name =		$parameters['TRANSCRIPTION_SERVICE'] ?? null;
		$job_id =			$parameters['JOB_ID'] ?? null;				// Job id for in-progress transcription on asynchronous service
		$attempt =			(int)($parameters['ATTEMPT'] ?? 1);
		
		$logger = caGetLogger(['logLevel' => 'INFO']);
		$config = Configuration::load();
		
		if(!$service_name) { $service_name = \CA\TranscriptionManager::getDefaultServiceName(); }
		if(!($service = \CA\TranscriptionManager::getService($service_name))) { 
			$logger->logError(_t("[TaskQueue::mediaTranscription::process] Transcription service %1 is not available", $service_name));
			$this->error->setError(551, _t("Transcription service %1 is not available", $service_name),"mediaTranscription->process()");	
			return false;
		}
		
		$report = ['errors' => [], 'notes' => []];
		
		$t = null;
		if($is_service_call) {
			$media_input = $parameters['FILE'] ?? null;
		} elseif(($t = Datamodel::getInstance($table)) && $t->load($id)) {
			$media_input = $t->getMediaPath($field, 'original');
		} else {
			// Bad table/id
			$logger->logError(_t("[TaskQueue::mediaTranscription::process] Invalid table or id. Table was '%1'; id was '%2'", $table, $id)); 
			$this->error->setError(551, _t("Invalid table or id. Table was '%1'; id was '%2'", $table, $id),"mediaTranscription->process()");	
			return false;
		}
		
		$result = $service->transcribe($media_input, [
			'model' => $model,
			'jobId' => $job_id,
			'language' => $config->get('transcription_language'),
			'maxWait' => $config->get('transcription_max_wait') ?: 300,
			'pollInterval' => $config->get('transcription_poll_interval') ?: 15
		]);
		
		switch($result['status']) {
			case \CA\Transcription\Plugins\BaseTranscriptionPlugin::STATUS_PENDING:
				if(($max_attempts = (int)($config->get('transcription_max_attempts') ?: 288)) && ($attempt >= $max_attempts)) {
					$logger->logError(_t('[TaskQueue::mediaTranscription::process] Gave up waiting for %1 to transcribe media %2 (job %3) after %4 attempts', $service->getServiceName(), $media_input, $result['jobId'], $attempt));
					$this->error->setError(551, _t("Gave up waiting for %1 to transcribe media %2 (job %3) after %4 attempts", $service->getServiceName(), $media_input, $result['jobId'], $attempt),"mediaTranscription->process()");	
					return false;
				}
				
				// Re-queue as new task to resume polling later
				$tq = new TaskQueue();
				$task_id = $tq->addTask('mediaTranscription', array_merge($parameters, [
						'TRANSCRIPTION_SERVICE' => $service_name,
						'JOB_ID' => $result['jobId'],
						'ATTEMPT' => $attempt + 1
					]), 
					[
						'priority' => 200, 
						'entity_key' => $parameters['ENTITY_KEY'] ?? ($t ? md5(join("/", [$table, $field, $id])) : $media_input), 
						'row_key' => $t ? join("/", [$table, $id]) : '', 
						'user_id' => $parameters['USER_ID'] ?? null
					]
				);
				$logger->logInfo(_t('[TaskQueue::mediaTranscription::process] Transcription of %1 by %2 (job %3) is in progress; re-queued as task %4', $media_input, $service->getServiceName(), $result['jobId'], $task_id));
				$report['notes'][] = _t('Transcription by %1 (job %2) is in progress; re-queued as task %3', $service->getServiceName(), $result['jobId'], $task_id);
				$report['requeued_task_id'] = $task_id;
				return $report;
			case \CA\Transcription\Plugins\BaseTranscriptionPlugin::STATUS_FAILED:
				$logger->logError(_t('[TaskQueue::mediaTranscription::process] Could not transcribe media %1 using %2: %3', $media_input, $service->getServiceName(), $result['message']));
				$this->error->setError(551, _t("Could not transcribe media %1 using %2: %3", $media_input, $service->getServiceName(), $result['message']),"mediaTranscription->process()");	
				return false;
		}
		
		$vtt_output = $result['vtt'];
		$raw_output = $result['raw'];
		
		$locale = __CA_DEFAULT_LOCALE__;
		if($lang = $result['language']) {
			$lang = preg_quote($lang, '/');
			if(!preg_match("/^{$lang}_/", $locale) && ($locales = ca_locales::localesForLanguage($lang, ['codesOnly' => true])) && is_array($locales) && sizeof($locales)) {
				$locale = array_shift($locales);
			}
		} else {
			$logger->logNotice(_t('[TaskQueue::mediaTranscription::process] Could not detect language of media. Using default locale %1.', $locale));
		}
		
		if($is_service_call) {
			$stub = pathinfo($media_input, PATHINFO_FILENAME);
			copy($vtt_output, $vtt_file = "{$output_dir}/{$stub}.vtt");
			$report['files']['vtt'] = $vtt_file;
			if($raw_output) {
				copy($raw_output, $raw_file = "{$output_dir}/{$stub}.{$result['rawExtension']}");
				$report['files']['transcription'] = $raw_file;
			}
		} elseif($t) {
			if(!$t->addCaptionFile($vtt_output, $locale, ['content' => $result['words']])) {
				$logger->logError(_t('[TaskQueue::mediaTranscription::process] Could not add VTT transcription file to %1::%2: %3', $table, $id, join('; ', $t->getErrors())));
				$this->error->setError(551, _t("Could not add VTT transcription file to %1::%2: %3", $table, $id, join('; ', $t->getErrors())),"mediaTranscription->process()");	
			}
			
			// Attach raw service output as sidecar
			if($raw_output && method_exists($t, 'addSidecarFile')) {
				$t->addSidecarFile($raw_output, _t('Generated by %1 at %2', $service->getServiceName(), date('c')), ['originalFilename' => pathinfo($media_input, PATHINFO_FILENAME).'_transcription.'.$result['rawExtension']]);
			}
		}
		$report['notes'][] = _t('Transcribed by %1', $service->getServiceName());
		
		@unlink($raw_output);
		@unlink($vtt_output);
		return $report;
	}
	# --------------------------------------------------------------------------------
	/**
	 * Cancel function - cancels queued task, doing cleanup and deleting task queue record
	 * all task queue handlers must implement this
	 *
	 * Returns true on success, false on error
	 */
	public function cancel($task_id, $parameters) {
		# delete tmp file
		@unlink($parameters["FILENAME"]);
		
		return true;
	}
	# --------------------------------------------------------------------------------
}
