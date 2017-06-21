#!/usr/bin/env php
<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/CLIUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
// This script maintains compatibility with older installations that invoke processing of the
// task queue by calling the now-deprecated processTaskQueue.php utility script.
// That script has been subsumed into the caUtils command-line application.
//
// This script merely calls caUtils to run its process-task-queue command in "quiet" mode


// Run process-task-queue utility
	$vs_hostname = isset($argv[1]) ? $argv[1] : null;
	$argv = array('caUtils', 'process-task-queue', '--quiet');
	if ($vs_hostname) {
		$argv[] = "--hostname={$vs_hostname}";
	}
	$argc = sizeof($argv);
	$_SERVER['argv'] = $argv;
	$_SERVER['argc'] = $argc;
	
	ob_start();
	$va_cwd = explode("/", $_SERVER['SCRIPT_FILENAME']);
	array_pop($va_cwd);
	array_pop($va_cwd);
	chdir(join("/", $va_cwd));
	require(join("/", $va_cwd)."/bin/caUtils");
	ob_clean();
?>
