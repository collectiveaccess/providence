<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/IWLPlugLanguageTranslation.php : interface for language translation plugins
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 * @subpackage Visualization
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

interface IWLPlugLanguageTranslation {
	# -------------------------------------------------------
	# Initialization and state
	# -------------------------------------------------------
	public function __construct();
	public function register();
	
	public function getDescription();
	public function checkStatus();
	
	public function translate(string $text, string $to_lang, ?array $options=null) : ?string;
	public function translateList(array $text, string $to_lang, ?array $options=null) : array;
	
	public function getSourceLanguages() : array;
	public function getTargetLanguages() : array;
}
