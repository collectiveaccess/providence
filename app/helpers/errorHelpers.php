<?php
/** ---------------------------------------------------------------------
 * app/helpers/errorHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2021 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */	
 
# ----------------------------------------
/**
 * PHP error handler for warnings, deprecations and notices
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @param array $errcontext
 *
 * return bool
 */
function caErrorHandler(int $errno, string $errstr, ?string $errfile=null, ?int $errline=null) {
	global $g_warnings, $g_deprecation_warnings, $g_notices;
	global $g_log_warnings, $g_display_warnings, 
		$g_log_deprecation_warnings, $g_display_deprecation_warnings, 
		$g_log_notices, $g_display_notices;
	global $g_log_ignore;
	
    switch ($errno) {
		case E_WARNING:
			if($g_log_warnings || $g_display_warnings) {
				// Ignore?
				if(sizeof(array_filter($g_log_ignore, function($v) use ($errstr) {
					return strpos($errstr, $v) !== false;
				})) > 0) {
					return true;
				}
				$g_warnings[md5($errstr.$errfile.$errline)] = [
					'message' => htmlspecialchars($errstr),
					'file' => $errfile,
					'line' => $errline
				];
			}
			return true;
			break;
		case E_DEPRECATED:
			if($g_log_deprecation_warnings || $g_display_deprecation_warnings) {
				// Ignore?
				if(sizeof(array_filter($g_log_ignore, function($v) use ($errstr) {
					return strpos($errstr, $v) !== false;
				})) > 0) {
					return true;
				}
				$g_deprecation_warnings[md5($errstr.$errfile.$errline)] = [
					'message' => htmlspecialchars($errstr),
					'file' => $errfile,
					'line' => $errline
				];
			}
			return true;
			break;
		case E_NOTICE:
			if($g_log_notices || $g_display_notices) {
				// Ignore?
				if(sizeof(array_filter($g_log_ignore, function($v) use ($errstr) {
					return strpos($errstr, $v) !== false;
				})) > 0) {
					return true;
				}
				$g_notices[md5($errstr.$errfile.$errline)] = [
					'message' => htmlspecialchars($errstr),
					'file' => $errfile,
					'line' => $errline
				];
			}
			return true;
			break;
    }

    return false;
}
# ----------------------------------------
/**
 * Set up globals and handler for display and logging of warnings, deprecations and notices
 */
function caInitErrorHandler() : void { 
	global $g_warnings, $g_deprecation_warnings, $g_notices, 
		$g_log_warnings, $g_display_warnings, 
		$g_log_deprecation_warnings, $g_display_deprecation_warnings, 
		$g_log_notices, $g_display_notices,
		$g_log_ignore;
	
	$g_warnings = $g_deprecation_warnings = $g_notices = [];
	
	$config = Configuration::load();

	$g_log_warnings = $config->get('log_warnings');
	$g_display_warnings = $config->get('display_warnings');
	$g_log_deprecation_warnings = $config->get('log_deprecation_warnings');
	$g_display_deprecation_warnings = $config->get('display_deprecation_warnings');
	$g_log_notices = $config->get('log_notices');
	$g_display_notices = $config->get('display_notices');

	$g_log_ignore = $config->getList('log_ignore') ?? [];
	
	set_error_handler('caErrorHandler', E_WARNING | E_DEPRECATED | E_NOTICE);
}
# ----------------------------------------
/**
 * Display exception error screen
 * @param Exception $e
 */
function caDisplayException(Exception $e, ?array $options=null) : void {
    if (defined("__CA_LIB_DIR__")) { require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php'); }
	if(!is_a($e, "DatabaseException") && class_exists('AppController')) { AppController::getInstance()->removeAllPlugins(); }

	$errno = 0;
	$errstr = $e->getMessage();
	$errfile = $e->getFile();
	$errline = $e->getLine();
	$errcontext = $e->getTrace();
	$errcontext_args = caExtractStackTraceArguments($errcontext);
	$request_params = caExtractRequestParams();

	$config = Configuration::load();
	$log_dir = $config->get('batch_metadata_import_log_directory');
	if(defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) {
		$o_log = new KLogger($log_dir, KLogger::DEBUG);
	} else {
		$o_log = new KLogger($log_dir, KLogger::ERR);
	}

	$o_log->logError(get_class($e) . ': ' . $e->getMessage());
	
	if(defined("__CA_IS_SERVICE_REQUEST__")) {
		$show_debugging = ((defined('__CA_ENABLE_DEBUG_OUTPUT__') && __CA_ENABLE_DEBUG_OUTPUT__) || $config->get('graphql_services_debug'));
		header("Content-type: application/json");
		print json_encode([
			'ok' => false, 'errors' => ['message' => $e->getMessage(), 'extensions' => ['category' => caGetOption('category', $options, null)],"locations" => $show_debugging ? ['file' => $errfile, 'line' => $errline] : null]
		]);
	} else {
		require_once(fatalErrorHtmlView());
	}
	exit;
}

/**
 * Get view path for fatal error html
 * @return string
 */
function fatalErrorHtmlView() {
	$fatal_error_html = '/views/system/fatal_error_html.php';
	if (defined("__CA_THEME_DIR__") && file_exists(__CA_THEME_DIR__ . $fatal_error_html)) {
		return __CA_THEME_DIR__ . $fatal_error_html;
	}
	else {
		return __CA_THEMES_DIR__ . '/default' . $fatal_error_html;
	}
}
# ----------------------------------------
/**
 * Display fatal error screen
 * @param int $pn_errno
 * @param string $ps_errstr
 * @param string $ps_errfile
 * @param int $pn_errline
 * @param array $pa_symboltable
 */
function caDisplayFatalError($pn_errno, $ps_errstr, $ps_errfile, $pn_errline, $pa_symboltable) {

	$pa_errcontext = debug_backtrace();
	array_shift($pa_errcontext); // remove entry for error handler
	$pa_errcontext_args = caExtractStackTraceArguments($pa_errcontext);
	$pa_request_params = caExtractRequestParams();

	switch($pn_errno) {
		case E_WARNING:
		case E_NOTICE:
		case E_STRICT:
		case E_DEPRECATED:
			break;
		default:
			if(class_exists('AppController')) { $o_app = AppController::getInstance()->removeAllPlugins(); }
			require_once(fatalErrorHtmlView());
			exit;
	}
}
# ----------------------------------------
/**
 * extract stack trace arguments from error context
 * @param array $pa_errcontext
 * @return array
 */
function caExtractStackTraceArguments($pa_errcontext) {
	if(!is_array($pa_errcontext)) { return []; }
		
	$o_purifier = caGetHTMLPurifier();
	$pa_args = [];
	
	foreach($pa_errcontext as $vn_i => $va_trace) {
		if(!is_array($va_trace)) { return []; }
		if(!isset($va_trace['args']) || !is_array($va_trace['args'])) { return []; }
		$pa_args[$vn_i] = [];
		foreach($va_trace['args'] as $vn_j => $vm_arg) {
			if (is_object($vm_arg)) {
				$pa_args[$vn_i][] = 'Object '.get_class($vm_arg);
			} elseif(is_array($vm_arg)) {
				$pa_args[$vn_i][] = 'Array('.sizeof($vm_arg).')';
			} elseif(is_resource($vm_arg)) {
				$pa_args[$vn_i][] = 'Resource';
			} elseif(is_bool($vm_arg)) {
				$pa_args[$vn_i][] = $vm_arg ? "true" : "false";
			} elseif(is_string($vm_arg)) {
				$vm_arg = $o_purifier->purify((string)$vm_arg);
				$pa_args[$vn_i][] = "'{$vm_arg}'";
			} else {
				$vm_arg = $o_purifier->purify((string)$vm_arg);
				$pa_args[$vn_i][] = $vm_arg;
			}
		}
	}
	return $pa_args;
}
# ----------------------------------------
/**
 * extract request parameters
 * @return array
 */
function caExtractRequestParams() {
	if(!include_once(pathinfo(__FILE__, PATHINFO_DIRNAME).'/../../vendor/autoload.php')) { return []; }

	if(!is_array($_REQUEST)) { return []; }
	
	$o_purifier = caGetHTMLPurifier();
	$pa_params = [];
	foreach($_REQUEST as $vs_k => $vm_val) {
		if(is_array($vm_val)) { $vm_val = join(',', caFlattenArray($vm_val));}
		if($vs_k == 'password') { continue; } // don't dump plain text passwords on screen
		$pa_params[$o_purifier->purify($vs_k)] = $o_purifier->purify($vm_val);
	}

	return $pa_params;
}

# ----------------------------------------
/**
 * Return application error message for numeric code
 *
 * @param int $error_code
 * @param string $locale
 *
 * @return string
 */
function caGetErrorMessage(int $error_code, string $locale=null) {
	if (!$locale || !preg_match("!^[a-z]{2,3}_[A-Z]{2,3}$!", $locale)) { $locale = 'en_US'; } 
	
	$path = __CA_LIB_DIR__."/Error/errors.{$locale}";
	if(!file_exists($path)) {
		$path = __CA_LIB_DIR__."/Error/errors.en_US";
	}
	if (!($errors = Configuration::load($path))) { return null; }
	
	return $errors->get($error_code);
}
# ----------------------------------------
 /**
  * Return URL path to themes directory, guessing based upon PHP script name is constants aren't set
  *
  * @return string
  */
function caGetThemeUrlPath() : string {
	$tmp = explode("/", str_replace("\\", "/", $_SERVER['SCRIPT_NAME']));
	array_pop($tmp);
	return defined('__CA_THEME_URL__') ? __CA_THEME_URL__ : join("/", $tmp).'/themes/default';
}
# ----------------------------------------
 /**
  * Return default application logo as HTML tag
  *
  * @return string
  */
function caGetDefaultLogo() : string {
	if(function_exists('caGetLoginLogo')) { 
		return caGetLoginLogo();
	}
	$url = caGetThemeUrlPath()."/graphics/logos/logo.svg";
	$width = 327;
	$height = 45;
	return "<img src={$url} alt='CollectiveAccess logo' width='{$width}' height='{$height}'/>";
}
# ----------------------------------------
		
