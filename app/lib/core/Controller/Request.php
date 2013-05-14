<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/Request.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2013 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
require_once(__CA_LIB_DIR__."/core/Error.php");
require_once(__CA_LIB_DIR__."/core/Controller/Request/Session.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
require_once(__CA_MODELS_DIR__."/ca_users.php");

# ----------------------------------------------------------------------
# Define parameter type constants for getParameter()
# ----------------------------------------------------------------------
/**
 * 
 * Returns number of seconds since session object was instantiated. This is very, very close to the total time the request has been executing. You can use this number as a measure of performance.
 *
 * @access public 
 * @return float Seconds elapsed since request started.
 */	
if(!defined("pInteger")) { define("pInteger", 1); }
if(!defined("pFloat")) { define("pFloat", 2); }
if(!defined("pString")) { define("pString", 3); }
if(!defined("pArray")) { define("pArray", 4); }

/**
 * Global containing user_id of currently logged in user (if any).
 * Used by Table::logChange() to set user_id in when logging changes for tables supporting change logging
 *	
 * @global Array $AUTH_CURRENT_USER_ID
 */
$AUTH_CURRENT_USER_ID = ""; 

/**
 * Class representing application users. Stores basic information, including login.
 * 
 *
 *
	Auth user object interface
	--------------------------
	Constuctor({mixed user_id})
		(if user_id is specified, attempt is made to load associated user information)
	
	close()			
		(saves any changes made to the user information)
		
	getUserID()		
		(returns a unique identifier for the user)
		
	isActive()
		(returns true if the user is marked as active; this doesn't mean it has access, only that it is not disabled)
		
	hasRole(string role)
		(returns true if user has specified role)
	
	getName()
		(returns name of loaded via constructor or authenticated user)
	
	authenticate(string username, string password, {array options})
		(returns 1 if username/password combination is valid; 2 if IP authentication was successful, false is auth failed. 
		 If valid, also loads user information for access via get*() methods)
	
	getLastPing()
		(returns time of last contact with user as Unix timestamp)
		
	setLastPing(int timestamp)
		(sets time of last contact with user; time is in Unix timestamp format)
		
	setLastLogout(int timestamp)
		(sets time of last logout by user; time is in Unix timestamp format)
		
	getLastLogout()
		(returns time of last logout by user; time is in Unix timestamp format)
	
	getClassName(string classname)
		(returns name of class)

 * @todo 
 */
class Request {
	# ----------------------------------------
	# --- Properties
	# ----------------------------------------
	
/**
 * Configuration object containing main application configuration file.
 * You may use the object referenced by this property to access the main configuration 
 * file, saving yourself an instantiation.
 *
 * @access public
 */	
	public $config;
	
/**
 * Datamodel object containing application data schema.
 * You may use the object referenced by this property as a convenient way to access the datamodel 
 *
 * @access public
 */	
	public $datamodel;
	
	
	private $opa_params;
	
	private $opa_action_errors;
/**
 * 
 * Creates a new Auth object. The Auth object marshals the various application session and user authentication objects into once easy-to-handle package.
 * 
 * By default, a new Auth object looks for login credentials (existing or incoming). If there aren't any, it checks the requests client IP address against
 * the ip-authentication database (the Ip object and underlying weblib_ips table). If there is no match, the Auth object then directs the
 * user to the application login page, as configured in the main application configuration file. If there is a match, the user is redirected
 * to the application "welcome" page, as configured.
 *
 * You may override the default behavior by passing parameters to the constructor. You may do so in two ways:
 * (1) Pass individual function parameters or (2) Pass an associative array as the first (any only) parameter. The array may define
 * any of the following keys:
 *
 * 	- user_name					[string]
 *		(the user name of the user attempting to login; if this is set password should be set as well)
 *
 *	- password					[string]
 *		(the password of the user attempting to login)
 *
 *  - dont_redirect_to_login	[boolean]
 *		(if set, user will not be redirected to login page if authentication fails)
 *
 *  - dont_redirect_to_welcome	[boolean]
 *		(if set, no redirect to the "welcome" url will be performed upon successful login)
 *
 *  - dont_redirect 			[boolean]
 *		(if set, equivalent to dont_redirect_to_login and dont_redirect_to_welcome being set)
 *
 *  - dont_check_ips			[boolean]
 *		(if set, no ip-based authentication is performed)
 *
 *  - dont_create_new_session	[boolean]
 *		(if set, no new session will be created, even if it is required)
 *
 *  - no_headers				[boolean]
 *		(if set, no HTTP headers will be emitted under any circumstances. This is equivalent to setting "dont_redirect_to_login",
 *		 "dont_create_new_session" and "dont_redirect_to_welcome")
 *
 *  - no_authentication			[boolean]
 *		(if set, authentication is not performed by the constructor and must be triggered manually by calling doAuthentication())
 *
 *  - dont_add_default_handlers	[boolean]
 *		(if set, the default user auth handlers are added to the new Auth object; the default user auth handler uses the
 *			weblib_user table and PHPWeblib User class)
 *
 *	- options					[indexed array of strings and/or numbers]
 *		(optional associative array of authentication option information to be passed to auth handlers. Options can be used by 
 * 		the handler in its authentication decision. Note that the default handlers do not support any options.)
 *
 * The keys are analogous to their like-named counterpart function parameters. The array syntax is preferable for readability and is
 * required for those keys without a corresponding parameter.
 *
 * Because under normal conditions Auth may emit redirect headers and/or set a cookie, you must instantiate your Auth object *before* 
 * outputting anything to the browser. If anything at all is output to the browser, redirects will fail and error messages will be generated. 
 * If for some reason you need to instantiate Auth after output has started, you must pass "no_headers" as true to disable all redirects and cookie manipulation.
 * Remember, if you use "no_headers", users will not be redirected to the login screen (if they are not authenticated) or the welcome screen (if they are
 * authenticated), nor will new sessions be created for users who lack one. In other words, only use "no_headers" if you know what you're doing.
 *
 * When a user is currently logged in, Auth defines a global variable, $AUTH_CURRENT_USER_ID, that contains - you guessed it - the
 * user_id of the currently logged in user. You can use this variable to get at the user_id in those odd situations where
 * there is no other clean and easy way to access the Auth object.
 *
 * @access public 
 *
 * @param string User name of user to log in. Default is empty (no incoming login)
 * @param string Password of user to log in. Default is empty (no incoming login)
 * @param bool If true, never redirect user to login screen, even if no user is logged in, or login attempt fails.  Default is false.
 * @param bool If true, don't bother authenticating again IP address. Default is false.
 * @param bool If true, new sessions are not created, even if the requestor lacks one. Default is false.
 * @param bool If true, user is not redirected to welcome page upon successful login. Default is false.
 * @return float Seconds elapsed since request started.
 */	
	public function __construct () {
		$this->config = Configuration::load();
		$this->datamodel = Datamodel::load();
		
		$this->opa_params = array();
		$this->opa_action_errors = array();
	}

/**
 * Returns application configuration object
 */
	public function getAppConfig() {
		return $this->config;
	}
	
/**
 * Returns application datamodel object
 */
	public function getAppDatamodel() {
		return $this->datamodel;
	}
	
/**
 * Reloads application configuration object
 */
	public function reloadAppConfig() {
		$this->config = Configuration::load('', false, true);
	}
	# ----------------------------------------
	# --- Parameter handling
	# ----------------------------------------
/**
 * 
 * Safely fetches GET or POST request parameter from $_REQUEST. 
 *
 * When using getParameter() you pass the name of the parameter along with a type assertion. 
 * getParameter() will throw an error if the assertion fails. For example, if you expect a
 * parameter to be an integer, and it is instead non-numeric, getParameter will throw a 
 * fatal error. If it is numeric, it will use the PHP intval() function to ensure it is a legal
 * integer.
 *
 * Type assertions are made using type constants defined for you by Auth. These are:
 *
 *  - pInteger	= 	Integer parameter. If parameter input is numeric, getParameter() will force it to a legal integer value using the PHP intval() function. Non-numerics will cause a fatal error.
 *	- pFloat	=	Floating point parameter. If parameter input is numeric, it is passed unchanged. Non-numerics will cause a fatal error.
 *	- pString	=	Virtually any input 
 *
 * @access public 
 * @return float Seconds elapsed since request started.
 */	
	function getParameter($ps_name, $pn_type) {
		$vs_val = $this->opa_params[$ps_name];
		if ($vs_val == "") { return ""; }
		
		switch($pn_type) {
			# -----------------------------------------
			case pInteger:
				if (is_numeric($vs_val)) {
					if ($vs_val == intval($vs_val)) {
						return $vs_val;
					}
				}
				break;
			# -----------------------------------------
			case pFloat:
				if (is_numeric($vs_val)) {
					return $vs_val;
				}
				break;
			# -----------------------------------------
			case pString:
				if (is_string($vs_val)) {
					if (get_magic_quotes_gpc()) {
						$vs_val = stripSlashes($vs_val);
					}
					$vs_val = rawurldecode($vs_val);
					if ($pb_prep_for_sql) {
						$vs_val = addslashes($vs_val);
					} 
					return $vs_val;
				}
				break;
			# -----------------------------------------
			case pArray:
				if (is_array($vs_val)) {
					return $vs_val;
				}
				break;
			# -----------------------------------------
		}
		
		die("Invalid parameter type for $ps_name\n");
	}
	# ----------------------------------------
	function setParameter($ps_name, $ps_value) {
		$this->opa_params[$ps_name] = $ps_value;
	}
	# ----------------------------------------
	# Character set
	# ----------------------------------------
	function getCharacterSet() {
		if (!($vs_charset = $this->config->get("character_set"))) {
			$vs_charset = "utf-8";
		}
		return $vs_charset;
	}
	# ----------------------------------------
	function characterSetHeader() {
		header('Content-type: text/html; charset='.$this->getCharacterSet());
	}
	# ----------------------------------------
	# --- Benchmarking
	# ----------------------------------------
/**
 * 
 * Returns number of seconds since session object was instantiated. This is very, very close to the total time the request has been executing. You can use this number as a measure of performance.
 *
 * @access public 
 * @return float Seconds elapsed since request started.
 */	
	function elapsedTime($pn_decimal_places=4) {
		return $this->session->elapsedTime($pn_decimal_places);
	}
	# ----------------------------------------
	# --- Action errors
	# ----------------------------------------
	public function getActionErrors($ps_source=null, $ps_subsource=null) {
		if (is_null($ps_source)) {
			return $this->_unrollActionErrors($this->opa_action_errors);
		} else {
			if($ps_subsource) {
				return isset($this->opa_action_errors[$ps_source][$ps_subsource]) ? $this->opa_action_errors[$ps_source][$ps_subsource] : null;
			}
			return $this->_unrollActionErrors(isset($this->opa_action_errors[$ps_source]) ? $this->opa_action_errors[$ps_source] : null);
		}
	}
	# ----------------------------------------
	private function _unrollActionErrors($pa_errors) {
		$va_unrolled_errors = array();
		if (is_array($pa_errors)) {
			foreach($pa_errors as $vs_key => $vm_err) {
				if (is_object($vm_err)) {
					array_push($va_unrolled_errors, $vm_err);
				} else {
					if (is_array($vm_err)) {
						$va_unrolled_errors = array_merge($va_unrolled_errors, $this->_unrollActionErrors($vm_err));
					}
				}
			}
		}
		return $va_unrolled_errors;
	}
	# ----------------------------------------
	public function clearActionErrors() {
		$this->opa_action_errors = array();
	}
	# ----------------------------------------
	public function getActionErrorSources() {
		return array_keys($this->opa_action_errors);
	}
	# ----------------------------------------
	public function getActionErrorSubSources($ps_source) {
		if (!isset($this->opa_action_errors[$ps_source]) || !is_array($this->opa_action_errors[$ps_source])) { return array(); }
		return array_keys($this->opa_action_errors[$ps_source]);
	}
	# ----------------------------------------
	public function addActionError($o_error, $ps_source, $ps_subsource=null) {
		if(!is_array($this->opa_action_errors[$ps_source])) { $this->opa_action_errors[$ps_source] = array(); }
		
		if ($ps_subsource) {
			if(!is_array($this->opa_action_errors[$ps_source][$ps_subsource])) { $this->opa_action_errors[$ps_source][$ps_subsource] = array(); }
			array_push($this->opa_action_errors[$ps_source][$ps_subsource], $o_error);
		} else {
			array_push($this->opa_action_errors[$ps_source], $o_error);
		}
	}
	# ----------------------------------------
	public function addActionErrors($pa_errors, $ps_source=null, $ps_subsource=null) {
		$vs_source = $ps_source;
		foreach($pa_errors as $o_e) {
			if (is_null($ps_source)) {
				$vs_tmp = $o_e->getErrorSource();
				list($vs_source, $ps_subsource) = explode('/', $vs_tmp);
			}	
			$this->addActionError($o_e, $vs_source, $ps_subsource);
		}
	}
	# ----------------------------------------
	public function numActionErrors($ps_source=null, $ps_subsource=null) {
		if (is_null($ps_source)) {
			return sizeof($this->opa_action_errors);
		} else {
			if ($ps_subsource) {
				return isset($this->opa_action_errors[$ps_source][$ps_subsource]) ? sizeof($this->opa_action_errors[$ps_source][$ps_subsource]) : 0;
			}
			return isset($this->opa_action_errors[$ps_source]) ? sizeof($this->opa_action_errors[$ps_source]) : 0;
		}
	}
	# ----------------------------------------
}
?>