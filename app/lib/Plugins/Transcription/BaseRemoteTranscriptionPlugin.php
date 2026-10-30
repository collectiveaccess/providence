<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Transcription/BaseRemoteTranscriptionPlugin.php :
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

require_once(__CA_LIB_DIR__.'/Plugins/Transcription/BaseTranscriptionPlugin.php');

/**
 * Base class for third-party transcription services with asynchronous job-based HTTP APIs.
 * Sub-classes implement submit(), getJobStatus() and fetchResults(); this class handles
 * polling and resumption of in-progress jobs.
 */
abstract class BaseRemoteTranscriptionPlugin extends BaseTranscriptionPlugin {
	# ------------------------------------------------
	/**
	 *
	 */
	protected $client = null;

	# ------------------------------------------------
	/**
	 * Submit media file to service
	 *
	 * @param string $filepath
	 * @param array $options Options passed to transcribe()
	 *
	 * @return string Job identifier, or null on failure
	 */
	abstract protected function submit(string $filepath, ?array $options=null) : ?string;
	# ------------------------------------------------
	/**
	 * Check status of submitted job
	 *
	 * @param string $job_id
	 *
	 * @return array Array with keys status (one of the STATUS_* constants), language and message
	 */
	abstract protected function getJobStatus(string $job_id) : array;
	# ------------------------------------------------
	/**
	 * Download transcription output for completed job
	 *
	 * @param string $job_id
	 * @param array $status Status array returned by getJobStatus()
	 *
	 * @return array Result array
	 */
	abstract protected function fetchResults(string $job_id, array $status) : array;
	# ------------------------------------------------
	/**
	 * Base URL for service API, with trailing slash
	 */
	abstract protected function getBaseUrl() : string;
	# ------------------------------------------------
	/**
	 * API key or access token for service
	 */
	abstract protected function getApiKey() : ?string;
	# ------------------------------------------------
	/**
	 *
	 */
	public function register() {
		if(!$this->getApiKey()) { return false; }
		return parent::register();
	}
	# ------------------------------------------------
	/**
	 * Transcribe media file, polling service until job is complete or maxWait seconds have elapsed.
	 *
	 * @see BaseTranscriptionPlugin::transcribe()
	 */
	public function transcribe(string $filepath, ?array $options=null) : array {
		$max_wait = (int)caGetOption('maxWait', $options, 300);
		$poll_interval = max(1, (int)caGetOption('pollInterval', $options, 15));

		try {
			if(!($job_id = caGetOption('jobId', $options, null))) {
				if(!file_exists($filepath)) {
					return $this->failure(_t('Media file %1 does not exist', $filepath));
				}
				if(!($job_id = $this->submit($filepath, $options))) {
					return $this->failure(_t('Could not submit %1 for transcription', $filepath));
				}
				$this->logger->logInfo(_t('[Transcription::%1] Submitted %2 as job %3', $this->getServiceName(), $filepath, $job_id));
			}

			$deadline = time() + $max_wait;
			while(true) {
				$status = $this->getJobStatus($job_id);
				switch($status['status']) {
					case self::STATUS_COMPLETED:
						return $this->fetchResults($job_id, $status);
					case self::STATUS_FAILED:
						return $this->failure(_t('Job %1 failed: %2', $job_id, $status['message'] ?? _t('unknown error')), ['jobId' => $job_id]);
				}
				if(time() + $poll_interval > $deadline) {
					return $this->result(self::STATUS_PENDING, ['jobId' => $job_id]);
				}
				sleep($poll_interval);
			}
		} catch(\GuzzleHttp\Exception\GuzzleException $e) {
			return $this->failure(_t('Request to service failed: %1', $e->getMessage()), ['jobId' => $job_id ?? null]);
		}
	}
	# ------------------------------------------------
	/**
	 * Get HTTP client configured with service base URL and authentication
	 */
	protected function getClient() : \GuzzleHttp\Client {
		if(!$this->client) {
			$this->client = new \GuzzleHttp\Client([
				'base_uri' => $this->getBaseUrl(),
				'headers' => ['Authorization' => 'Bearer '.$this->getApiKey()],
				'connect_timeout' => 30
			]);
		}
		return $this->client;
	}
	# ------------------------------------------------
	/**
	 * Perform GET request and decode JSON response
	 */
	protected function getJSON(string $path) : ?array {
		$response = $this->getClient()->request('GET', $path, ['headers' => ['Accept' => 'application/json']]);
		return json_decode((string)$response->getBody(), true);
	}
	# ------------------------------------------------
	/**
	 * Perform GET request and write response body to temporary file
	 *
	 * @param string $path
	 * @param string $extension Extension for temporary file
	 * @param string $accept Value for Accept header. [Default is null]
	 *
	 * @return string Path to temporary file
	 */
	protected function download(string $path, string $extension, ?string $accept=null) : string {
		$tmp_path = caGetTempFileName('transcription', $extension, ['useAppTmpDir' => true]);
		$this->getClient()->request('GET', $path, [
			'headers' => $accept ? ['Accept' => $accept] : [],
			'sink' => $tmp_path
		]);
		return $tmp_path;
	}
	# ------------------------------------------------
}
