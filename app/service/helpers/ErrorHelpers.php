<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/ErrorHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
namespace GraphQLServices\Helpers\Error;

/**
 *
 */
function error(string $idno, string $code, string $message, ?string $bundle) : array {
	return [
		'idno' => $idno, 
		'code' => $code,
		'message' => $message,
		'bundle' => $bundle
	];
}

/**
 *
 */
function warning(string $idno, string $code, string $message, ?string $bundle) : array {
	return [
		'idno' => $idno, 
		'code' => $code,
		'message' => $message,
		'bundle' => $bundle
	];
}

/**
 *
 */
function info(string $idno, string $code, string $message, ?string $bundle) : array {
	return [
		'idno' => $idno, 
		'code' => $code,
		'message' => $message,
		'bundle' => $bundle
	];
}

/**
 * Convert internal error number to GraphQL error code
 */
function toGraphQLError(int $error_number) : string {
	if($error_number <= 0) { return ''; }
	return 'ERROR_'.$error_number;
}
