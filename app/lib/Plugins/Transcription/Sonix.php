<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Transcription/Sonix.php :
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
 * Transcription using the Sonix (sonix.ai) API
 * See https://sonix.ai/docs/api
 *
 * Requires __SONIX_API_KEY__ to be defined in setup.php. Additional upload parameters
 * may be set in app.conf transcription_service_options.sonix and are passed to the API as-is.
 */
class Sonix extends BaseRemoteTranscriptionPlugin {
	# ------------------------------------------------
	/**
	 * Maximum size of file that may be uploaded directly to Sonix
	 */
	const MAX_UPLOAD_SIZE = 100 * 1024 * 1024;

	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->description = _t('Transcribe audio and video using the Sonix API');
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getServiceName() : string {
		return 'Sonix';
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getBaseUrl() : string {
		return 'https://api.sonix.ai/v1/';
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getApiKey() : ?string {
		return defined('__SONIX_API_KEY__') ? __SONIX_API_KEY__ : null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function submit(string $filepath, ?array $options=null) : ?string {
		if(filesize($filepath) > self::MAX_UPLOAD_SIZE) {
			$this->logger->logError(_t('[Transcription::%1] %2 exceeds the maximum upload size of %3', $this->getServiceName(), $filepath, caHumanFilesize(self::MAX_UPLOAD_SIZE)));
			return null;
		}
		$params = array_merge(['language' => 'en'], $this->getServiceOptions());
		if($language = caGetOption('language', $options, null)) {
			$params['language'] = $language;
		}

		$multipart = [
			['name' => 'file', 'contents' => fopen($filepath, 'r'), 'filename' => pathinfo($filepath, PATHINFO_BASENAME)]
		];
		foreach($params as $k => $v) {
			$multipart[] = ['name' => $k, 'contents' => is_array($v) ? join(',', $v) : (string)$v];
		}
		$response = $this->getClient()->request('POST', 'media', ['multipart' => $multipart]);
		$data = json_decode((string)$response->getBody(), true);

		return $data['id'] ?? null;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function getJobStatus(string $job_id) : array {
		$data = $this->getJSON('media/'.rawurlencode($job_id));

		switch($s = ($data['status'] ?? null)) {
			case 'completed':
				$status = self::STATUS_COMPLETED;
				break;
			case 'failed':
			case 'blocked':
			case 'duplicate':
				$status = self::STATUS_FAILED;
				break;
			default:
				$status = self::STATUS_PENDING;
				break;
		}
		return [
			'status' => $status,
			'language' => $data['language'] ?? null,
			'message' => ($status === self::STATUS_FAILED) ? _t('Media status is %1', $s) : null
		];
	}
	# ------------------------------------------------
	/**
	 *
	 */
	protected function fetchResults(string $job_id, array $status) : array {
		$id = rawurlencode($job_id);
		$raw = $this->download("media/{$id}/transcript.json", 'json');
		$vtt = $this->download("media/{$id}/transcript.vtt", 'vtt');

		$words = [];
		$data = json_decode(file_get_contents($raw), true);
		foreach($data['transcript'] ?? [] as $segment) {
			foreach($segment['words'] ?? [] as $w) {
				if(!strlen(trim($w['text'] ?? ''))) { continue; }
				$words[] = [
					'word' => $w['text'],
					'start' => $w['start_time'],
					'end' => $w['end_time']
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
