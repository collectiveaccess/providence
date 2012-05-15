<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Controller/AppController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2009 Whirl-i-Gig
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
 
include_once(__CA_LIB_DIR__."/core/Controller/Request/RequestHTTP.php");
include_once(__CA_LIB_DIR__."/core/Controller/RequestDispatcher.php");

global $g_app_controller;
$g_app_controller = null;

class AppController {
	# -------------------------------------------------------
	private $opo_request;
	private $opo_response;
	private $opa_plugins;
	private $opo_dispatcher;
	
	# -------------------------------------------------------
	public static function getInstance(&$po_request=null, &$po_response=null) {
		global $g_app_controller;
		if (!$g_app_controller) {
			return $g_app_controller = new AppController($po_request, $po_response);
		}
		return $g_app_controller;
	}
	# -------------------------------------------------------
	public static function instanceExists(){
		global $g_app_controller;
		if (!$g_app_controller) {
			return false;
		} else {
			return true;
		}
	}
	# -------------------------------------------------------
	public function __construct(&$po_request=null, &$po_response=null) {
		if (!isset($_GLOBALS) || !$_GLOBALS['AppController']) {
			if ($po_response) { 
				$this->opo_response = $po_response;
			} else {
				$this->opo_response = new ResponseHTTP();
			}
			if ($po_request) { 
				$this->opo_request = $po_request;
			} else {
				$this->opo_request = new RequestHTTP($this->opo_response, array('dont_redirect' => true, 'no_authentication' => true));
			}
			$this->opa_plugins = array();
			
			$this->opo_dispatcher = new RequestDispatcher($this->opo_request, $this->opo_response);
		} else {
			die("Can't instantiate AppController twice");
		}
	}
	# -------------------------------------------------------
	public function &getRequest() {
		return $this->opo_request;
	}
	
	# -------------------------------------------------------
	public function &getResponse() {
		return $this->opo_response;
	}
	# -------------------------------------------------------
	public function dispatch($pb_dont_send_response=false) {
	
		foreach($this->getPlugins() as $vo_plugin) {
			$vo_plugin->routeStartup();
		}
		//
		// TODO: do routing here
		//
		foreach($this->getPlugins() as $vo_plugin) {
			$vo_plugin->routeShutdown();
		}
	
		foreach($this->getPlugins() as $vo_plugin) {
			$vo_plugin->dispatchLoopStartup();
		}
		if (!$this->opo_dispatcher->dispatch($this->getPlugins())) {
			// error dispatching
			$va_errors = array();
			foreach($this->opo_dispatcher->errors as $o_error) {
				$va_errors[] = $o_error->getErrorNumber();
			}
			$this->opo_response->setRedirect($this->opo_request->config->get('error_display_url').'/n/'.join(';', $va_errors).'?r='.urlencode($this->opo_request->getFullUrlPath()));
		}
		foreach($this->getPlugins() as $vo_plugin) {
			$vo_plugin->dispatchLoopShutdown();
		}
		
		
		if(!$pb_dont_send_response) {
			$this->opo_response->sendResponse();
		}
	}
	# -------------------------------------------------------
	public function getDispatcher() {
		return $this->opo_dispatcher;
	}
	# -------------------------------------------------------
	# Plugins
	# -------------------------------------------------------
	public function registerPlugin($po_plugin) {
		$po_plugin->initPlugin($this->getRequest(), $this->getResponse());
		array_push($this->opa_plugins, $po_plugin);
		
		// update dispatcher's plugin list
		if($this->opo_dispatcher) {
			$this->opo_dispatcher->setPlugins($this->opa_plugins);
		}
	}
	# -------------------------------------------------------
	public function removeAllPlugins() {
		$this->opa_plugins = array();
		if($this->opo_dispatcher) {
			$this->opo_dispatcher->setPlugins(array());
		}
	}
	# -------------------------------------------------------
	public function getPlugins() {
		return $this->opa_plugins;
	}
	# -------------------------------------------------------
}
?>