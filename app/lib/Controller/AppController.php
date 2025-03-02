<?php
/** ---------------------------------------------------------------------
 * app/lib/Controller/AppController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2021 Whirl-i-Gig
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
include_once(__CA_LIB_DIR__."/Controller/Request/RequestHTTP.php");
include_once(__CA_LIB_DIR__."/Controller/RequestDispatcher.php");
include_once(__CA_LIB_DIR__."/Exceptions/ApplicationException.php");

global $g_app_controller;
$g_app_controller = null;

class AppController {
	# -------------------------------------------------------
	private $request;
	private $response;
	private $plugins;
	var $dispatcher;
	
	# -------------------------------------------------------
	public static function getInstance(&$po_request=null, &$po_response=null, $dont_create=false) {
		global $g_app_controller;
		if (!$g_app_controller) {
			return $dont_create ? null : $g_app_controller = new AppController($po_request, $po_response);
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
		if (!isset($GLOBALS) || !isset($GLOBALS['AppController'])) {
			if ($po_response) { 
				$this->response = $po_response;
			} else {
				$this->response = new ResponseHTTP();
			}
			if ($po_request) { 
				$this->request = $po_request;
			} else {
				$this->request = new RequestHTTP($this->response, array('dont_redirect' => true, 'no_authentication' => true));
			}
			$this->plugins = array();
			
			$this->dispatcher = new RequestDispatcher($this->request, $this->response);
		} else {
			die("Can't instantiate AppController twice");
		}
	}
	# -------------------------------------------------------
	public function getRequest() {
		return $this->request;
	}
	# -------------------------------------------------------
	public function getResponse() {
		return $this->response;
	}
	# -------------------------------------------------------
	public function dispatch($pb_dont_send_response=false) {
		global $g_errored;
	
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
		if (!$this->dispatcher->dispatch($this->getPlugins())) {
			// error dispatching
			$va_errors = array();
			foreach($this->dispatcher->errors as $o_error) {
				$va_errors[] = $o_error->getErrorMessage();
			}
			
			$g_errored = true;	// routing error occurred
			throw new ApplicationException(join(';', $va_errors));
		}
		foreach($this->getPlugins() as $vo_plugin) {
			$vo_plugin->dispatchLoopShutdown();
		}
		
		if(!$pb_dont_send_response) {
			$this->response->sendResponse();
		}
	}
	# -------------------------------------------------------
	public function getDispatcher() {
		return $this->dispatcher;
	}
	# -------------------------------------------------------
	# Plugins
	# -------------------------------------------------------
	public function registerPlugin($po_plugin) {
		$po_plugin->initPlugin($this->getRequest(), $this->getResponse());
		array_push($this->plugins, $po_plugin);
		
		// update dispatcher's plugin list
		if($this->dispatcher) {
			$this->dispatcher->setPlugins($this->plugins);
		}
	}
	# -------------------------------------------------------
	public function removeAllPlugins() {
		$this->plugins = array();
		if($this->dispatcher) {
			$this->dispatcher->setPlugins(array());
		}
	}
	# -------------------------------------------------------
	public function getPlugins() {
		return $this->plugins;
	}
	# -------------------------------------------------------
}
