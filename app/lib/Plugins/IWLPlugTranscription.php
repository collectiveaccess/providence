<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/IWLPlugTranscription.php : interface for AV media transcription plugins
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

interface IWLPlugTranscription {
	# -------------------------------------------------------
	# Initialization and state
	# -------------------------------------------------------
	public function __construct();
	public function register();

	public function getDescription();
	public function checkStatus();

	# -------------------------------------------------------
	# Transcription
	# -------------------------------------------------------
	public function getServiceName() : string;
	public function transcribe(string $filepath, ?array $options=null) : array;
}
