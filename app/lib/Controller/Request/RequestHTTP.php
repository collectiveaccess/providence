<?php
/** ---------------------------------------------------------------------
 * app/lib/Controller/Request/RequestHTTP.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2018 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Controller/Request.php");

# ----------------------------------------
# Authorization constants
# ----------------------------------------
define("__AUTH_EDIT_ACCESS__", 0);
define("__AUTH_VIEW_ACCESS__", 1);
define("__AUTH_NO_ACCESS__", 2);

class RequestHTTP extends Request {
	# -------------------------------------------------------
	/**
	 *
	 */
	private $opb_is_dispatched;
 		
	/**
	 * Current session_id
	 *
	 * @var session
	 * @access public
	 */
	public $session_id;
	
	/**
	 * User object for currently logged in user. Will be undefined if no user is logged in.
	 * You may check to see if a user is logged in using the isLoggedIn() method.
	 *
	 * @var ca_users
	 * @access public
	 */
	public $user;
	
	private $opo_response;
	
	private $ops_script_name;
	private $ops_base_path;
	private $ops_path_info;
	private $ops_request_method;
	private $ops_raw_post_data = "";

	private $opa_params;
    
    /**
     * Parsed request info: controller path, controller and action
     */
 	private $ops_parsed_module_path;
 	private $ops_parsed_controller;
 	private $ops_parsed_action;
 	private $ops_parsed_action_extra;
 	private $ops_parsed_controller_url;
 	private $ops_parsed_is_app_plugin = false;
 	
 	
 	/**
 	 * 
 	 */
 	static $html_purifier;
 		
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($po_response, $pa_options=null) {
		$this->opo_response = $po_response;
		parent::__construct();
		
		global $AUTH_CURRENT_USER_ID;
		$AUTH_CURRENT_USER_ID = "";

		if (is_array($pa_options)) {
			if (isset($pa_options["no_headers"]) && $pa_options["no_headers"]) {
				$pa_options["dont_redirect_to_login"] = true;
				$pa_options["dont_create_new_session"] = true;
				$pa_options["dont_redirect_to_welcome"] = true;
			}
			
			if (isset($pa_options["dont_redirect"]) && $pa_options["dont_redirect"]) {
				$pa_options["dont_redirect_to_login"] = true;
				$pa_options["dont_redirect_to_welcome"] = true;
			}
			
			$va_sim_params = null;
			if (isset($pa_options["simulateWith"]) && is_array($pa_options["simulateWith"])) {
				$va_sim_params = $pa_options["simulateWith"];
				if (isset($va_sim_params['GET'])) { $_GET = $va_sim_params['GET']; }
				if (isset($va_sim_params['POST'])) {$_POST = $va_sim_params['POST']; }
				if (isset($va_sim_params['COOKIE'])) {$_COOKIE = $va_sim_params['COOKIE']; }
				$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
				
				foreach(array(
					'SCRIPT_NAME', 'REQUEST_METHOD', 'PHP_AUTH_USER', 'PHP_AUTH_PW',
					'REQUEST_URI', 'PATH_INFO', 'REMOTE_ADDR', 'HTTP_USER_AGENT'
				) as $vs_k) {
					if (isset($va_sim_params[$vs_k])) { $_SERVER[$vs_k] = $va_sim_params[$vs_k]; }
				}
				
				$pa_options["no_authentication"] = true;
			}
		}

		# figure out script name
		$va_tmp = (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) ? explode('/', $_SERVER['SCRIPT_NAME']) : array();
		$this->ops_script_name = '';
		while((!$this->ops_script_name) && (sizeof($va_tmp) > 0)) {
			$this->ops_script_name = array_pop($va_tmp);
		}

		# create session
		$vs_app_name = $this->config->get("app_name");

		// restore session from token for service requests
		if(($this->ops_script_name=="service.php") && isset($_GET['authToken']) && (strlen($_GET['authToken']) > 0)) {
			$vs_token = preg_replace("/[^a-f0-9]/", "", $_GET['authToken']); // sanitize

			$this->session_id = Session::restoreFromServiceAuthToken($vs_token, $vs_app_name);
		}

		// plain old session
		if(!$this->session_id) {
			$this->session_id = Session::init($vs_app_name, isset($pa_options["dont_create_new_session"]) ? $pa_options["dont_create_new_session"] : false);
		}
		
		if (!isset($pa_options["no_authentication"]) || !$pa_options["no_authentication"]) {
			$this->doAuthentication($pa_options);
		} else {
			if (isset($va_sim_params['user_id']) && $va_sim_params['user_id']) {
				$this->user = new ca_users($va_sim_params['user_id']);
			} else {
				$this->user = new ca_users();
			}
		}
		
		$this->opb_is_dispatched = false;
		
		$this->opa_params['GET'] =& $_GET;
		$this->opa_params['POST'] =& $_POST;
		$this->opa_params['COOKIE'] =& $_COOKIE;
		$this->opa_params['PATH'] = array();
		
		$this->ops_request_method = (isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : null);
		
		/* allow authentication via URL for web service API like so: http://user:pw@example.com/ */
		if($this->ops_script_name=="service.php") {
			$this->ops_raw_post_data = file_get_contents("php://input");

			if($_SERVER["PHP_AUTH_USER"] && $_SERVER["PHP_AUTH_PW"]){
				$this->doAuthentication(array(
					'noPublicUsers' => true,
					"no_headers" => true,
					"dont_redirect" => true,
					"options" => array(),
					"user_name" => $_SERVER["PHP_AUTH_USER"],
					"password" => $_SERVER["PHP_AUTH_PW"],
				));
			}
		}
		
		$this->ops_base_path = join('/', $va_tmp);
		$this->ops_full_path = $_SERVER['REQUEST_URI'];
		if (!caUseCleanUrls() && !preg_match("!/index.php!", $this->ops_full_path) && !preg_match("!/service.php!", $this->ops_full_path)) { $this->ops_full_path = rtrim($this->ops_full_path, "/")."/index.php"; }
		$vs_path_info = str_replace($_SERVER['SCRIPT_NAME'], "", str_replace("?".$_SERVER['QUERY_STRING'], "", $this->ops_full_path));
		
		$this->ops_path_info = $vs_path_info ? $vs_path_info : (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
		if (__CA_URL_ROOT__) { $this->ops_path_info = preg_replace("!^".__CA_URL_ROOT__."/!", "", $this->ops_path_info); }
	}
	# -------------------------------------------------------
	/** 
		Returns a list of locale_ids to use for UI presentation in priority order
		This will include the user's selected locale if one in logged in, as well as
		the default locale_id(s) as configured in app.conf or global.conf
		using the 'locale_defaults' directive. If no locales are set then a full list
		of locale_ids is returned.
	 */
	public function getUILocales() {
		$va_locale_codes = array();
		$va_locale_ids = array();
		if ($this->isLoggedIn()) {
			$va_locale_codes[] = $this->user->getPreference('ui_locale');
		}
		
		if ($va_tmp = $this->config->getList('locale_defaults')) {
			$va_locale_codes = array_merge($va_locale_codes, $va_tmp);
		}
		
		
		$t_locale = new ca_locales();
		if (sizeof($va_locale_codes) == 0) {
			foreach(ca_locales::getLocaleList() as $vn_locale_id => $va_locale_info) {
				$va_locale_ids[] = $vn_locale_id;
			}
		} else {
			foreach($va_locale_codes as $vs_locale_code) {
				if ($vn_locale_id = $t_locale->loadLocaleByCode($vs_locale_code)) {
					$va_locale_ids[] = $vn_locale_id;
				}
			}
		}
		
		if (!sizeof($va_locale_ids)) {
			die("No locales configured?");
		}
		
		return $va_locale_ids;
	}
	# -------------------------------------------------------
	function isAjax() {
		return ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest") || (isset($_REQUEST['_isFlex']) && $_REQUEST['_isFlex']));
	}
	# -------------------------------------------------------
	function isDownload($pb_set_download=null) {
		if (!is_null($pb_set_download)) {
			foreach(array('GET', 'POST', 'COOKIE', 'PATH', 'REQUEST') as $vs_method) {
				$this->opa_params[$vs_method]['download'] = ($pb_set_download) ? true : false;
			}
			return $this->opa_params['GET']['download'];
		} else {
			return $this->getParameter('download', pInteger) ? true : false;
		}
	}
	# -------------------------------------------------------
	public function getPathInfo() {
		return $this->ops_path_info;
	}
	# -------------------------------------------------------
	public function getFullUrlPath() {
		return $this->ops_full_path;
	}
	# -------------------------------------------------------
	public function getBaseUrlPath() {
		return $this->ops_base_path;
	}
	# -------------------------------------------------------
	public function getScriptName() {
		return $this->ops_script_name;
	}
	# -------------------------------------------------------
	public function getRequestMethod() {
		return $this->ops_request_method;
	}
	# -------------------------------------------------------
	public function getRawPostData() {
		return $this->ops_raw_post_data;
	}
	# -------------------------------------------------------
	public function setRawPostData($ps_post_data) {
		$this->ops_raw_post_data = $ps_post_data;
	}
	# -------------------------------------------------------
	public function getSessionID() {
		return $this->session_id;
	}
	# -------------------------------------------------------
	public function getUser() {
		return $this->user;
	}
	# -------------------------------------------------------
	public function getThemeUrlPath($pb_use_default=false) {
		if ($this->config->get('always_use_default_theme')) { $pb_use_default = true; }
		if (!$pb_use_default && $this->isLoggedIn()) {
			$vs_theme = $this->user->getPreference('ui_theme');
		} else {
			$vs_theme = $this->config->get('theme');		// default theme
		}
		if (!$vs_theme) { $vs_theme = $this->config->get('theme'); }
		return $this->config->get('themes_url').'/'.$vs_theme;
	}
	# -------------------------------------------------------
	public function getThemeDirectoryPath($pb_use_default=false) {
		if ($this->config->get('always_use_default_theme')) { $pb_use_default = true; }
		if (!$pb_use_default && $this->isLoggedIn()) {
			$vs_theme = $this->user->getPreference('ui_theme');
		} else {
			$vs_theme = $this->config->get('theme');		// default theme
		}
		if (!$vs_theme) { $vs_theme = $this->config->get('theme'); }
		return $this->config->get('themes_directory').'/'.$vs_theme;
	}
	# -------------------------------------------------------
	/**
	 * Returns url path to "default" theme. This is not (necessarily) the same as the configured theme for the installation
	 * that is returned when calling getThemeUrlPath() with the $pb_use_default parameter set. The path returned
	 * by this method is the url path to the base theme named "default"
	 */
	public function getDefaultThemeUrlPath() {
		return $this->config->get('themes_url').'/default';
	}
	# -------------------------------------------------------
	/**
	 * Returns path to "default" theme. This is not (necessarily) the same as the configured theme for the installation
	 * that is returned when calling getThemeDirectoryPath() with the $pb_use_default parameter set. The path returned
	 * by this method is the path to the base theme named "default"
	 */
	public function getDefaultThemeDirectoryPath() {
		return $this->config->get('themes_directory').'/default';
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function getServiceViewPath(){
		return $this->config->get('service_view_path');
	}
	# -------------------------------------------------------
	/**
	 * Return file path on server to views path in current theme.
	 *
	 * @param bool $pb_use_default Return path in default theme no matter what theme user has selected. [Default is false]
	 * @return string Path to file on server
	 */
	public function getViewsDirectoryPath($pb_use_default=false) {
		if ($this->config->get('always_use_default_theme')) { $pb_use_default = true; }
		switch($this->getScriptName()){
			case "service.php":
				return $this->getServiceViewPath();
				break;
			case "index.php":
			default:
				return $this->getThemeDirectoryPath($pb_use_default).'/views';
				break;
		}
	}
	# -------------------------------------------------------
	/**
	 * Search for and return file path on server for a theme file. The selected theme will be searched followed
	 * by the default theme, ceasing when the file is found. 
	 *
	 * @param string $ps_relative_file_path Path to theme file relative to the theme root directory. To find the file path of the base.css file "css/base.css" would be passed.
	 * @return Path to file on server
	 */
	public function getDirectoryPathForThemeFile($ps_relative_file_path) {
		if(
			file_exists($vs_path = $this->getThemeDirectoryPath()."/{$ps_relative_file_path}")
			||
			file_exists($vs_path = $this->getDefaultThemeDirectoryPath()."/{$ps_relative_file_path}")
		) {
			return $vs_path;
		} 
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Search for and return URL path for a theme file. The selected theme will be searched followed
	 * by the default theme, ceasing when the file is found. 
	 *
	 * @param string $ps_relative_file_path Path to theme file relative to the theme root directory. To find the URL path of the base.css file "css/base.css" would be passed.
	 * @return URL to file
	 */
	public function getUrlPathForThemeFile($ps_relative_file_path) {
		if(
			file_exists($this->getThemeDirectoryPath()."/{$ps_relative_file_path}")
		) {
			return $this->getThemeUrlPath()."/{$ps_relative_file_path}";
		} elseif(file_exists($this->getDefaultThemeDirectoryPath()."/{$ps_relative_file_path}")) {
			return $this->getDefaultThemeUrlPath()."/{$ps_relative_file_path}";
		} 
		return null;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function getAssetsUrlPath() {
		return $this->config->get('ca_url_root')."/assets";
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function getAssetsDirectoryPath() {
		return $this->config->get('ca_base_dir').$this->config->get('ca_url_root')."/assets";
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function isDispatched() {
		return $this->opb_is_dispatched;
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function setIsDispatched($ps_is_dispatched=true) {
		$this->opb_is_dispatched = $ps_is_dispatched;
	}
	# -------------------------------------------------------
	public function isApplicationPlugin() {
		return $this->ops_parsed_is_app_plugin;
	}
	# -------------------------------------------------------
	public function setIsApplicationPlugin($pb_is_app_plugin) {
		$this->ops_parsed_is_app_plugin = $pb_is_app_plugin;
	}
	# -------------------------------------------------------
	public function setModulePath($ps_module_path) {
		$this->ops_parsed_module_path = $ps_module_path;
	}
	# -------------------------------------------------------
	public function getModulePath() {
		if ($this->isApplicationPlugin()) {
			// clean up module path for plugins (has "/controllers" tagged on the end
			return preg_replace('!/controllers$!', '', $this->ops_parsed_module_path);
		}
		return $this->ops_parsed_module_path;
	}
	# -------------------------------------------------------
	public function setController($ps_controller) {
		$this->ops_parsed_controller = $ps_controller;
	}
	# -------------------------------------------------------
	public function getController() {
		return $this->ops_parsed_controller;
	}
	# -------------------------------------------------------
	public function setAction($ps_action) {
		$this->ops_parsed_action = $ps_action;
	}
	# -------------------------------------------------------
	public function getAction() {
		return $this->ops_parsed_action;
	}
	# -------------------------------------------------------
	public function setActionExtra($ps_action_extra) {
		$this->ops_parsed_action_extra = $ps_action_extra;
	}
	# -------------------------------------------------------
	public function getActionExtra() {
		return $this->ops_parsed_action_extra;
	}
	# -------------------------------------------------------
	public function setControllerUrl($ps_url) {
		$this->ops_parsed_controller_url = $ps_url;
	}
	# -------------------------------------------------------
	public function getControllerUrl() {
		return $this->ops_parsed_controller_url;
	}
	# -------------------------------------------------------
	public function getRequestUrl($pb_absolute=false) {
		$va_url = array();
		if ($vs_tmp = $this->getBaseUrlPath()) {
			$va_url[] = trim($vs_tmp, '/');
		}
		if ($vs_tmp = $this->getScriptName()) {
			$va_url[] = trim($vs_tmp, '/');
		}
		if ($vs_tmp = $this->getModulePath()) {
			$va_url[] = trim($vs_tmp, '/');
		}
		if ($vs_tmp = $this->getController()) {
			$va_url[] = trim($vs_tmp, '/');
		}
		if ($vs_tmp = $this->getAction()) {
			$va_url[] = trim($vs_tmp, '/');
		}
		if ($vs_tmp = $this->getActionExtra()) {
			$va_url[] = trim($vs_tmp, '/');
		}
		
		//foreach($this->opa_params['PATH'] as $vs_param => $vs_value) {
		//	$va_url[] = urlencode($vs_param).'/'.urlencode($vs_value);
		//}
		if (is_array($this->opa_params['GET'])) {
			foreach($this->opa_params['GET'] as $vs_param => $vs_value) {
				$va_url[] = urlencode($vs_param).'/'.urlencode($vs_value);
			}
		}
		if (is_array($this->opa_params['PATH'])) {
			foreach($this->opa_params['PATH'] as $vs_param => $vs_value) {
				$va_url[] = urlencode($vs_param).'/'.urlencode($vs_value);
			}
		}
		
		if ($pb_absolute) {
			// make returned URL absolute
			array_unshift($va_url, $this->config->get('site_host'));
		}
		
		return join('/', $va_url);
	}
	# --------------------------------------------------------------------------------
	/**
	 * Return HTMLPurifier instance
	 *
	 * @return HTMLPurifier Returns instance
	 */
	static public function getPurifier() {
		if (!RequestHTTP::$html_purifier) { RequestHTTP::$html_purifier = new HTMLPurifier(); }
		return RequestHTTP::$html_purifier;
	}
	# -------------------------------------------------------
	public function getParameter($ps_name, $pn_type, $ps_http_method=null, $pa_options=array()) {
		if (in_array($ps_http_method, array('GET', 'POST', 'COOKIE', 'PATH', 'REQUEST'))) {
			$vm_val = $this->opa_params[$ps_http_method][$ps_name];
		} else {
			foreach(array('GET', 'POST', 'PATH', 'COOKIE', 'REQUEST') as $vs_http_method) {
				$vm_val = (isset($this->opa_params[$vs_http_method]) && isset($this->opa_params[$vs_http_method][$ps_name])) ? $this->opa_params[$vs_http_method][$ps_name] : null;
				if (isset($vm_val)) {
					break;
				}
			}
		}
		if (!isset($vm_val)) { return ""; }
		
		$vm_val = str_replace("\0", '', $vm_val);
		if((caGetOption('purify', $pa_options, true) && $this->config->get('purify_all_text_input')) || caGetOption('forcePurify', $pa_options, false)) {
		    if(is_array($vm_val)) {
		        $vm_val = array_map(function($v) { return is_array($v) ? $v : str_replace("&amp;", "&", RequestHTTP::getPurifier()->purify(rawurldecode($v))); }, $vm_val);
		    } else {
		        $vm_val = str_replace("&amp;", "&", RequestHTTP::getPurifier()->purify(rawurldecode($vm_val)));
		    }
		}
		
		if ($vm_val == "") { return ""; }
		
		switch($pn_type) {
			# -----------------------------------------
			case pInteger:
				if (is_numeric($vm_val)) {
					if ($vm_val == intval($vm_val)) {
						return (int)$vm_val;
					}
				}
				break;
			# -----------------------------------------
			case pFloat:
				if (is_numeric($vm_val)) {
					return (float)$vm_val;
				}
				break;
			# -----------------------------------------
			case pString:
				if (is_string($vm_val)) {
					if(caGetOption('retainBackslashes', $pa_options, true)) {
						$vm_val = str_replace("\\", "\\\\", $vm_val);	// retain backslashes for some strange people desire them as valid input
					}
					$vm_val = rawurldecode($vm_val);
					return $vm_val;
				}
				break;
			# -----------------------------------------
			case pArray:
				if (is_array($vm_val)) {
					return $vm_val;
				}
				break;
			# -----------------------------------------
		}
		
		die("Invalid parameter type for $ps_name\n");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getParameters($pa_http_methods=null) {
		if (!$pa_http_methods) { $pa_http_methods = array('GET', 'POST', 'COOKIE', 'PATH', 'REQUEST'); }
		if($pa_http_methods && !is_array($pa_http_methods)) { $pa_http_methods = array($pa_http_methods); }
		$va_params = array();
		if (!is_array($pa_http_methods)) { $pa_http_methods = array('GET', 'POST', 'COOKIE', 'PATH', 'REQUEST'); }
		foreach($pa_http_methods as $vs_http_method) {
			if (isset($this->opa_params[$vs_http_method]) && is_array($this->opa_params[$vs_http_method])) {
				$va_params = array_merge($va_params, $this->opa_params[$vs_http_method]);
			}
		}
		return $va_params;
	}
	# -------------------------------------------------------
	function setParameter($ps_name, $pm_value, $ps_http_method='GET') {
		if (in_array($ps_http_method, array('GET', 'POST', 'COOKIE', 'PATH', 'REQUEST'))) {
			$this->opa_params[$ps_http_method][$ps_name] = $pm_value;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
 	/**
	 *
	 * Saves changes to user object and sends asynchronous request for search indexing
	 * You should call this at the end of every request to ensure that user and session variables are saved.
	 *
	 * @access public
	 */
	function close() {
		if (is_object($this->user)) {
			$this->user->close();
		}

		if(defined('__CA_SITE_HOSTNAME__') && strlen(__CA_SITE_HOSTNAME__) > 0) {
		    
			if (
			    !($vn_port = (int)$this->getAppConfig()->get('out_of_process_search_indexing_port'))
			    && 
			    !($vn_port = (int)getenv('CA_OUT_OF_PROCESS_SEARCH_INDEXING_PORT'))
			) {
                if(__CA_SITE_PROTOCOL__ == 'https') { 
                    $vn_port = 443;	
                } elseif(isset($_SERVER['SERVER_PORT']) &&  $_SERVER['SERVER_PORT']) {
                    $vn_port = $_SERVER['SERVER_PORT'];
                } else {
                    $vn_port = 80;
                }
            }
			
			if (
			    !($vs_proto = trim($this->getAppConfig()->get('out_of_process_search_indexing_protocol')))
			    && 
			    !($vs_proto = getenv('CA_OUT_OF_PROCESS_SEARCH_INDEXING_PROTOCOL'))
			) {
			    $vs_proto = (($vn_port == 443) || (__CA_SITE_PROTOCOL__ == 'https')) ? 'tls' : 'tcp';
			}
			
			if (
			    !($vs_indexing_hostname = trim($this->getAppConfig()->get('out_of_process_search_indexing_hostname')))
			    && 
			    !($vs_indexing_hostname = getenv('CA_OUT_OF_PROCESS_SEARCH_INDEXING_HOSTNAME'))
			) {
			    $vs_indexing_hostname = __CA_SITE_HOSTNAME__;
			}
			// trigger async search indexing
			if((__CA_APP_TYPE__ === 'PROVIDENCE') && !$this->getAppConfig()->get('disable_out_of_process_search_indexing')) {
                require_once(__CA_MODELS_DIR__."/ca_search_indexing_queue.php");
                if (!ca_search_indexing_queue::lockExists()) {
                    $r_socket = fsockopen($vs_proto . '://'. $vs_indexing_hostname, $vn_port, $errno, $err, 3);
                    if ($r_socket) {
                        $vs_http  = "GET ".$this->getBaseUrlPath()."/index.php?processIndexingQueue=1 HTTP/1.1\r\n";
                        $vs_http .= "Host: ".__CA_SITE_HOSTNAME__."\r\n";
                        $vs_http .= "Connection: Close\r\n\r\n";
                        fwrite($r_socket, $vs_http);
                        fclose($r_socket);
                    }
                }
			}
		}
	}
	# ----------------------------------------
    /**
     * 
     * Determines if a user is currently logged in. If a user is logged in you
     * can safely access the user object via the user property. If this returns
     * false, the user property will be unset and any method calls on it will (of course)
     * result in an error.
     *
     * @access public 
     * @return bool True if a user is logged in, false if not.
     */	
	public function isLoggedIn() {
		if (is_object($this->user) && ($this->user->getUserID())) {
			return true;
		} else {
			return false; 
		}
	}
	# ----------------------------------------
    /**
     * 
     * Returns true if the currently logged in user has the specified role. You may specify the
     * role as either an integer role_id, the role name or the role short name.
     *
     * @access public 
     * @return bool True if user has role, false if not.
     */	
	public function hasRole($pm_role) {
		if ($this->isLoggedIn()) {
			return $this->user->hasRole($pm_role);
		}
		return false; 
	}
	# ----------------------------------------
    /**
     * 
     * Returns the user_id of the currently logged in user. This is the integer user_id,
     * *NOT* the user name.
     *
     * @access public 
     * @return integer User_id of currently logged in user or null if user is not logged in.
     */	
	public function getUserID() {
		if ($this->isLoggedIn()) {
			return $this->user->getUserID();
		}
		return null;
	}
	# ----------------------------------------
	# Authentication
	# ----------------------------------------
    /**
     * 
     * Implements standard username/password and IP-address based user authentication. Applications
     * requiring completely custom authentication methods should override this method. However, most of
     * the time if you need custom authentication you can just create a custom user auth handler class ("username/password" authentication).
     *
     * One clean way to extend Auth is create a sub-class whose constructor calls addUserHandler() and delegates
     * everything else to Auth.
     *
     * @access private 
     * @param array of login options (same as the associative option array in the class constructor)
     */	
	public function doAuthentication($pa_options) {	
		global $AUTH_CURRENT_USER_ID;

		$o_event_log = new Eventlog();
		$vs_app_name = $this->config->get("app_name");
		
		foreach(array(
			'no_headers', 'dont_redirect_to_login', 'dont_create_new_session', 'dont_redirect_to_welcome',
			'user_name', 'password', 'options', 'noPublicUsers', 'dont_redirect', 'no_headers', 'redirect',
			'allow_external_auth'
		) as $vs_key) {
			if (!isset($pa_options[$vs_key])) { $pa_options[$vs_key] = null; }
		}
		if (!is_array($pa_options["options"])) { $pa_options["options"] = array(); }
		
		if ($pa_options["no_headers"]) {
			$pa_options["dont_redirect_to_login"] = true;
			$pa_options["dont_create_new_session"] = true;
			$pa_options["dont_redirect_to_welcome"] = true;
		}
		
		if ($pa_options["dont_redirect"]) {
			$pa_options["dont_redirect_to_login"] = true;
			$pa_options["dont_redirect_to_welcome"] = true;
		}
		
		$vb_login_successful = false;
		if (!$pa_options["user_name"]) {		// no incoming login
			//
			// is a user already logged in?
			//
			if ($vn_user_id = Session::getVar($vs_app_name."_user_id")) { 				// does session have a user attached to it?
				// user is already logged in

				$this->user = new ca_users($vn_user_id);		// add user object

				if ((!$this->user->isActive()) || ($this->user->numErrors()) || ($pa_options['noPublicUsers'] && $this->user->isPublicUser())) {			// error means user_id in session is invalid
					$vb_login_successful = false;
				} else {
					$vb_login_successful = true;
				}
				
				if ($vb_login_successful) {																	// Login was successful
					Session::setVar($vs_app_name."_lastping",time());					// set last time we heard from client in session
					$this->user->setLastPing(time());	
					$AUTH_CURRENT_USER_ID = $vn_user_id;
					//$this->user->close(); ** will be called externally **
					return $vb_login_successful;
				}
			}
			
			if (!$vb_login_successful) {
				$this->user = new ca_users();		// add user object

                if (!AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_USE_ADAPTER_LOGIN_FORM__) || $pa_options['allow_external_auth']) {
                    $vs_tmp1 = $vs_tmp2 = null;
                    if (($vn_auth_type = $this->user->authenticate($vs_tmp1, $vs_tmp2, $pa_options["options"]))) {	# error means user_id in session is invalid
                        if (($pa_options['noPublicUsers'] && $this->user->isPublicUser()) || !$this->user->isActive()) {
                            $o_event_log->log(array("CODE" => "LOGF", "SOURCE" => "Auth", "MESSAGE" => "Failed login for user id '".$vn_user_id."' (".$_SERVER['REQUEST_URI']."); IP=".$_SERVER["REMOTE_ADDR"]."; user agent='".$_SERVER["HTTP_USER_AGENT"]."'"));
                            $vb_login_successful = false;
                        } else {
                            $vb_login_successful = true;
                            $vn_user_id = $this->user->getUserID();
                        }
                    }

                    if (!$vb_login_successful) {																	// throw user to login screen
                        if (!$pa_options["dont_redirect_to_login"]) {
                            $o_event_log->log(array("CODE" => "LOGF", "SOURCE" => "Auth", "MESSAGE" => "Failed login with redirect for user id '".$vn_user_id."' (".$_SERVER['REQUEST_URI']."); IP=".$_SERVER["REMOTE_ADDR"]."; user agent='".$_SERVER["HTTP_USER_AGENT"]."'"));
                            $vs_redirect = $this->getRequestUrl(true);

                            if (strpos($vs_redirect, $this->config->get("auth_login_path") !== -1)) {
                                $vs_redirect = '';
                            } else {
                                $vs_redirect = '?redirect=' . urlencode($vs_redirect);
                            }
                            $this->opo_response->addHeader("Location", $this->getBaseUrlPath().'/'.$this->getScriptName().'/'.$this->config->get("auth_login_path") . $vs_redirect);
                        }
                        return false;
                    }
                } else {
                	// Redirect to external auth?
                	return $this->user->authenticate($vs_tmp1, $vs_tmp2, $pa_options["options"]);
                }
			}
		} 
		
		//
		// incoming login
		//
		if ($pa_options["user_name"]) {
			$vb_login_successful = false;
			$this->user = new ca_users();

			if (($vn_auth_type = $this->user->authenticate($pa_options["user_name"], $pa_options["password"], $pa_options["options"]))) {	# error means user_id in session is invalid
				if (($pa_options['noPublicUsers'] && $this->user->isPublicUser()) || !$this->user->isActive()) {
					$vb_login_successful = false;
				} else {
					$vb_login_successful = true;
					$vn_user_id = $this->user->getUserID();
					
					if (!defined('__CA_IS_SERVICE_REQUEST__')) { // service need to keep auth tokens around
		                Session::deleteSession();
		            }
				}
			}
		}
	
		if (!$vb_login_successful) {	
			$this->user = new ca_users();															// auth failed
																								// throw user to login screen
			if ($pa_options["user_name"]) {
				$o_event_log->log(array("CODE" => "LOGF", "SOURCE" => "Auth", "MESSAGE" => "Failed login for '".$pa_options["user_name"]."' (".$_SERVER['REQUEST_URI']."); IP=".$_SERVER["REMOTE_ADDR"]."; user agent='".$_SERVER["HTTP_USER_AGENT"]."'"));
			}
			if (!$pa_options["dont_redirect_to_login"]) {
				$vs_auth_login_url = $this->getBaseUrlPath().'/'.$this->getScriptName().'/'.$this->config->get("auth_login_path");
				$this->opo_response->addHeader("Location", $vs_auth_login_url);
			}
			return false;
		} else {		
			$o_event_log->log(array("CODE" => "LOGN", "SOURCE" => "Auth", "MESSAGE" => "Successful login for '".$pa_options["user_name"]."'; IP=".$_SERVER["REMOTE_ADDR"]."; user agent=".$_SERVER["HTTP_USER_AGENT"]));
		    
		    $this->session_id = Session::init($vs_app_name, isset($pa_options["dont_create_new_session"]) ? $pa_options["dont_create_new_session"] : false);
			
			Session::setVar($vs_app_name."_user_auth_type",$vn_auth_type);				// type of auth used: 1=username/password; 2=ip-base auth
			Session::setVar($vs_app_name."_user_id",$vn_user_id);						// auth succeeded; set user_id in session
			Session::setVar($vs_app_name."_logintime",time());							// also set login time (unix timestamp) in session
			Session::setVar($vs_app_name."_lastping",time());
			
			$this->user->setVar('last_login', time(), array('volatile' => true));
			$this->user->setLastLogout($this->user->getLastPing(), array('volatile' => true));
			
			$this->user->setMode(ACCESS_WRITE);
			$this->user->update();
			
			$AUTH_CURRENT_USER_ID = $vn_user_id;

			if ($pa_options['redirect']) {
				// redirect to specified URL
				$this->opo_response->setRedirect($pa_options['redirect']);
				$this->opo_response->sendResponse();
				exit;
			}

			if ((!$pa_options["dont_redirect_to_welcome"]) && !AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_USE_ADAPTER_LOGIN_FORM__)) {
				// redirect to "welcome" page
				$this->opo_response->setRedirect($this->getBaseUrlPath().'/'.$this->getScriptName().'/'.$this->config->get("auth_login_welcome_path"));
				$this->opo_response->sendResponse();
				exit;
			}
			
			return true;
		}
	}
	# ----------------------------------------
	/**
	 * Returns the IP address of the remote client
	 *
	 * @access public
	 * @return (string) - the IP address of the remote client
	 */
	public function getClientIP() {
		return $_SERVER["REMOTE_ADDR"];
	}
	# ----------------------------------------
	public function deauthenticate() {
		$vs_app_name = $this->config->get("app_name");
    
        AuthenticationManager::deauthenticate();    
		if ($this->isLoggedIn()) {
			Session::setVar("{$vs_app_name}_user_id",'');
			//Session::deleteSession();
			$this->user = null;
		}
	}
	# ----------------------------------------
	/**
	 * Returns true if this request is an attempt to authenticate via the web service API
	 * (in this case access control measures should be disabled)
	 *
	 * @return boolean
	 * @access public
	 */
	public function isServiceAuthRequest() {
		if($this->getParameter("method",pString)=="auth") {
			return true;
		}

		if($this->getParameter("method",pString)=="getUserID") {
			return true;
		}


		$va_action = explode("#",$_SERVER["HTTP_SOAPACTION"]); // I hope this is set no matter what Soap client you use :-)

		if(strlen($va_action[1])>0 && trim(str_replace('"',"",$va_action[1])) == "auth"){
			return true;
		}

		if(strlen($va_action[1])>0 && trim(str_replace('"',"",$va_action[1])) == "getUserID"){
			return true;
		}
		
		return false;
	}
	# ----------------------------------------
	/**
	 * Returns a unique key identifying this request for caching purposes
	 *
	 * @return string
	 */
	public function getHash() {
		return md5(
			serialize($this->getParameters(array('POST', 'GET', 'REQUEST'))) .
			$this->getRawPostData() .
			$this->getRequestMethod() .
			$this->getFullUrlPath() .
			$this->getScriptName() .
			($this->isLoggedIn() ? $this->getUserID() : '')
		);
	}
	# ----------------------------------------
}
