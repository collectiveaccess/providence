<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/Transcription/Whisper.php :
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
 * @subpackage Transcription
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Transcription\Plugins;

require_once(__CA_LIB_DIR__.'/Plugins/Transcription/BaseTranscriptionPlugin.php');
require_once(__CA_APP_DIR__.'/helpers/avHelpers.php');

/**
 * Transcription using locally installed OpenAI Whisper
 * See https://github.com/openai/whisper
 */
class Whisper extends BaseTranscriptionPlugin {
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->description = _t('Transcribe audio and video using locally installed OpenAI Whisper');
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function register() {
		if(!caWhisperInstalled()) { return false; }
		return parent::register();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function getServiceName() : string {
		return 'Whisper';
	}
	# ------------------------------------------------
	/**
	 * Transcribe media file. Whisper runs synchronously, so the result will never be pending.
	 *
	 * @see BaseTranscriptionPlugin::transcribe()
	 */
	public function transcribe(string $filepath, ?array $options=null) : array {
		if(!($app_path = caWhisperInstalled())) {
			return $this->failure(_t("Whisper is not installed (see %1)", 'https://github.com/openai/whisper'));
		}
		if(!($model = caGetOption('model', $options, null)) && !($model = $this->config->get('whisper_model'))) { $model = 'base'; }
		$tmp = explode('.', $model);
		$end = array_pop($tmp);

		// Don't try to detect language if model is language-specific
		$language = caGetOption('language', $options, null);
		$dont_detect = $language || ((sizeof($tmp) > 0) && (strlen($end) >= 2) && (strlen($end) <= 3));

		if(!$dont_detect && ($detect_path = caWhisperInstalled(['returnPathToDetect' => true]))) {
			caExec("{$detect_path} --model=".caEscapeShellArg($model)." --input=".caEscapeShellArg($filepath)." --tmpdir=".__CA_TEMP_DIR__, $output, $return);
			if(($return == 0) && strlen($detected = trim(join('', $output ?? [])))) {
				$language = $detected;
			}
		}

		$whisper_output = caGetTempFileName('transcription', 'json', ['useAppTmpDir' => true]);
		$output = null;
		caExec("{$app_path} --model=".caEscapeShellArg($model)." --format=json --words=1 --input=".caEscapeShellArg($filepath)." --output=".caEscapeShellArg($whisper_output)." --tmpdir=".__CA_TEMP_DIR__, $output, $return);

		if((int)$return !== 0) {
			@unlink($whisper_output);
			return $this->failure(_t("Could not transcribe media %1. Return code was %2; message was %3", $filepath, $return, join('; ', $output ?? [])));
		}

		// Convert JSON file to VTT
		$vtt_output = caGetTempFileName('transcription', 'vtt', ['useAppTmpDir' => true]);
		if(!is_array($content = caWhisperTranscriptionToVTT($whisper_output, $vtt_output, ['returnContent' => true]))) {
			@unlink($whisper_output);
			@unlink($vtt_output);
			return $this->failure(_t("Could not convert Whisper output for %1 to VTT", $filepath));
		}

		return $this->result(self::STATUS_COMPLETED, [
			'vtt' => $vtt_output,
			'raw' => $whisper_output,
			'words' => caWhisperTranscriptionSegmentsToWords($content) ?? [],
			'language' => $language ?: ($content['language'] ?? null)
		]);
	}
	# ------------------------------------------------
}
