<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Transcription/BaseTranscriptionPlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage Transcription
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Transcription\Plugins;

include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugTranscription.php");

/**
 * Base class for transcription plugins. Plugins take a path to an audio or video file and return
 * a result array, as returned by BaseTranscriptionPlugin::result(), with keys:
 *
 *		status = One of the STATUS_* constants
 *		jobId = Service-specific identifier for the transcription job. Used to resume polling of asynchronous services. [Null for synchronous services]
 *		vtt = Path to WebVTT file containing transcription
 *		raw = Path to service-specific transcription output (typically JSON), to be attached as a sidecar file
 *		rawExtension = File extension for raw output
 *		words = List of words with timing, each an array with keys word, start and end (in seconds)
 *		language = ISO 639-1 language code of transcribed media, if known
 *		message = Error or status message
 */
abstract class BaseTranscriptionPlugin extends \WLPlug implements \IWLPlugTranscription {
	# ------------------------------------------------
	/**
	 * Transcription is complete; output files are available
	 */
	const STATUS_COMPLETED = 'completed';

	/**
	 * Transcription is in progress on remote service; resubmit with jobId to resume
	 */
	const STATUS_PENDING = 'pending';

	/**
	 * Transcription failed
	 */
	const STATUS_FAILED = 'failed';

	# ------------------------------------------------
	/**
	 *
	 */
	protected $info = [];

	/**
	 *
	 */
	protected $config;

	/**
	 *
	 */
	protected $logger;

	# ------------------------------------------------
	public function __construct() {
		$this->config = \Configuration::load();
		$this->logger = caGetLogger();
	}
	# ------------------------------------------------
	/**
	 * Return plugin info if plugin is available for use, false if not.
	 */
	public function register() {
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function checkStatus() {
		$status = parent::checkStatus();
		$status['available'] = is_array($this->register());
		return $status;
	}
	# ------------------------------------------------
	/**
	 * Display name of transcription service, used in sidecar notes and logs
	 */
	abstract public function getServiceName() : string;
	# ------------------------------------------------
	/**
	 * Transcribe media file
	 *
	 * @param string $filepath Path to audio or video file
	 * @param array $options Options include:
	 *		jobId = Identifier of previously submitted job to resume. [Default is null]
	 *		language = ISO 639-1 code of spoken language in media. If omitted the service will detect the language or use its default. [Default is null]
	 *		model = Service-specific model. [Default is null]
	 *		maxWait = Maximum number of seconds to wait for an asynchronous service to complete before returning a pending status. [Default is 300]
	 *		pollInterval = Number of seconds between status checks for asynchronous services. [Default is 15]
	 *
	 * @return array Result array. See class description.
	 */
	abstract public function transcribe(string $filepath, ?array $options=null) : array;
	# ------------------------------------------------
	/**
	 * Settings for this plugin from the app.conf transcription_service_options directive
	 */
	protected function getServiceOptions() : array {
		$options = $this->config->getAssoc('transcription_service_options') ?? [];
		$key = strtolower((new \ReflectionClass($this))->getShortName());
		return is_array($options[$key] ?? null) ? $options[$key] : [];
	}
	# ------------------------------------------------
	/**
	 * Build result array
	 */
	protected function result(string $status, ?array $values=null) : array {
		return array_merge([
			'status' => $status,
			'jobId' => null,
			'vtt' => null,
			'raw' => null,
			'rawExtension' => 'json',
			'words' => [],
			'language' => null,
			'message' => null
		], $values ?? []);
	}
	# ------------------------------------------------
	/**
	 * Build failure result array and log message
	 */
	protected function failure(string $message, ?array $values=null) : array {
		$this->logger->logError(_t('[Transcription::%1] %2', $this->getServiceName(), $message));
		return $this->result(self::STATUS_FAILED, array_merge($values ?? [], ['message' => $message]));
	}
	# ------------------------------------------------
}
