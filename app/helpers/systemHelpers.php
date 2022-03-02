<?php

/** ---------------------------------------------------------------------
 * app/helpers/systemHelpers.php : miscellaneous system functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2020 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage utils
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
   *
   */

	require_once(__CA_APP_DIR__.'/helpers/logHelpers.php');

	$system_helpers_debug_logger = null;

	function getLogger(){
		global $system_helpers_debug_logger;

		if (!isset($system_helpers_debug_logger) || $system_helpers_debug_logger === null){
			$system_helpers_debug_logger = caGetLogger( array( 'logDirectory' => __CA_APP_DIR__ . "/log", 'logLevel' => 'DEBUG' ) );
		}
		return $system_helpers_debug_logger;
	}
	# ---------------------------------------
	/**
	 * A wrapper on top of @exec to take care of output validation.
	 * It validates the command result status towards an expected value.
	 *
	 * @param      $command
	 * @param      $output
	 * @param int  $expectedStatus
	 *
	 * @return bool true if the command return the same status code as the one on the params. false otherwise.
	 */
	function caExecExpected( $command, &$output = null, $expectedStatus = 0 ) {
		$logger = getLogger();

		caExec( $command, $output, $return_status );
		$vb_result = $return_status === $expectedStatus;
		if (!$vb_result){
			$logger->log( "Status: $return_status (expected " . $expectedStatus . ')', LOG_ERR);
		}

		return $vb_result;
	}

	# ---------------------------------------
	/**
	 * A wrapper on top of @exec.
	 *
	 * @param      $command
	 * @param      $output
	 *
	 * @param null $return_status
	 *
	 * @return mixed Status and command output
	 */
	function caExec( $command, &$output = null, &$return_status = null ) {
		$logger = getLogger();

		$logger->logDebug( "Executing command: '$command'" );
		return exec( $command, $output, $return_status );
	}
