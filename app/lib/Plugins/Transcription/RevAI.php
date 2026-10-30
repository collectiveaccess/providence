<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Transcription/RevAI.php :
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

require_once(__CA_LIB_DIR__.'/Plugins/Transcription/BaseRemoteTranscriptionPlugin.php');

/**
 * Transcription using the Rev (rev.com) asynchronous speech-to-text API
 * See https://docs.rev.ai/api/asynchronous/
 *
 * Requires __REV_AI_ACCESS_TOKEN__ to be defined in setup.php. Additional job options
 * (Eg. transcriber = human) may be set in app.conf transcription_service_options.revai and
 * are passed to the API as-is.
 */
class RevAI extends BaseRemoteTranscriptionPlugin {
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->description = _t('Transcribe audio and video using the Rev.ai API');
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getServiceName() : string {
		return 'Rev.ai';
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getBaseUrl() : string {
		return 'https://api.rev.ai/speechtotext/v1/';
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getApiKey() : ?string {
		return defined('__REV_AI_ACCESS_TOKEN__') ? __REV_AI_ACCESS_TOKEN__ : null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function submit(string $filepath, ?array $options=null) : ?string {
		$job_options = $this->getServiceOptions();
		if($language = caGetOption('language', $options, null)) {
			$job_options['language'] = $language;
		}

		$multipart = [
			['name' => 'media', 'contents' => fopen($filepath, 'r'), 'filename' => pathinfo($filepath, PATHINFO_BASENAME)]
		];
		if(sizeof($job_options)) {
			$multipart[] = ['name' => 'options', 'contents' => json_encode($job_options)];
		}
		$response = $this->getClient()->request('POST', 'jobs', ['multipart' => $multipart]);
		$data = json_decode((string)$response->getBody(), true);

		return $data['id'] ?? null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getJobStatus(string $job_id) : array {
		$data = $this->getJSON('jobs/'.rawurlencode($job_id));

		switch($data['status'] ?? null) {
			case 'transcribed':
				$status = self::STATUS_COMPLETED;
				break;
			case 'failed':
				$status = self::STATUS_FAILED;
				break;
			default:
				$status = self::STATUS_PENDING;
				break;
		}
		return [
			'status' => $status,
			'language' => $data['language'] ?? null,
			'message' => $data['failure_detail'] ?? $data['failure'] ?? null
		];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function fetchResults(string $job_id, array $status) : array {
		$id = rawurlencode($job_id);
		$raw = $this->download("jobs/{$id}/transcript", 'json', 'application/vnd.rev.transcript.v1.0+json');
		$vtt = $this->download("jobs/{$id}/captions", 'vtt', 'text/vtt');

		$words = [];
		$data = json_decode(file_get_contents($raw), true);
		foreach($data['monologues'] ?? [] as $monologue) {
			foreach($monologue['elements'] ?? [] as $e) {
				if(($e['type'] ?? null) !== 'text') { continue; }
				$words[] = [
					'word' => $e['value'],
					'start' => $e['ts'],
					'end' => $e['end_ts']
				];
			}
		}

		return $this->result(self::STATUS_COMPLETED, [
			'jobId' => $job_id,
			'vtt' => $vtt,
			'raw' => $raw,
			'words' => $words,
			'language' => $status['language']
		]);
	}
	# ------------------------------------------------
}
