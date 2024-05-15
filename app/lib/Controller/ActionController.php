<?php
/** ---------------------------------------------------------------------
 * app/lib/Controller/ActionController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2016 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/ApplicationVars.php');
require_once(__CA_LIB_DIR__.'/BaseObject.php');
require_once(__CA_LIB_DIR__.'/View.php');
require_once(__CA_LIB_DIR__.'/Controller/Request/NotificationManager.php');

class ActionController extends BaseObject {
	# -------------------------------------------------------
	/**
	 * @var RequestHTTP
	 */
	protected $opo_request;
	/**
	 * @var ResponseHTTP
	 */
	protected $opo_response;
	/**
	 * @var View
	 */
	protected $opo_view;
	protected $opa_view_paths;
	/**
	 * @var NotificationManager
	 */
	protected $opo_notification_manager;
	# -------------------------------------------------------
	/**
	 * @param RequestHTTP $po_request
	 * @param ResponseHTTP $po_response
	 * @param null|array $pa_view_paths
	 */
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		$this->opo_request =& $po_request;
		$this->opo_response =& $po_response;
		
		if($this->opo_request->isApplicationPlugin()) {
			if (!is_array($pa_view_paths)) { $pa_view_paths = array(); }
			$va_tmp = explode('/', $this->opo_request->getModulePath());
			$pa_view_paths[] = $this->opo_request->config->get('application_plugins').'/'.$va_tmp[0].'/views';
		}
		
		$this->opa_view_paths = is_array($pa_view_paths) ? $pa_view_paths : ($pa_view_paths ? array($pa_view_paths) : array());
		$this->opo_notification_manager = new NotificationManager($this->opo_request);
	}
	# -------------------------------------------------------
	public function setViewPath($pa_view_paths) {
		$this->opa_view_paths = $pa_view_paths;
		if($this->opo_view) {
			$this->opo_view->setViewPath($pa_view_paths);
		}
	}
	# -------------------------------------------------------
	public function getViewPaths() {
		return $this->opa_view_paths;
	}
	# -------------------------------------------------------
	public function __get($ps_key) {
		switch($ps_key) {
			case 'view':
				return $this->opo_view ? $this->opo_view : $this->initView();
				break;
			case 'request':
				return $this->opo_request;
				break;
			case 'response':
				return $this->opo_response;
				break;
			case 'notification':
				return $this->opo_notification_manager;
				break;
			default:
				return isset($this->{$ps_key}) ? $this->{$ps_key} : null;
				break;
		}
	}
	# -------------------------------------------------------
	/**
	 * Init new view
	 * @return View
	 */
	public function initView() {
		$this->opo_view = new View($this->opo_request, $this->opa_view_paths);
		$this->opo_view->setVar('request', $this->getRequest());
		$this->opo_view->setVar('controller', $this);
		
		// Set globals
		if (is_array($va_globals = $this->opo_request->config->getAssoc('global_template_values'))) {
			$o_appvars = new ApplicationVars();
			foreach($va_globals as $vs_name => $va_info) {
				$this->opo_view->setVar($vs_name, $o_appvars->getVar("pawtucket_global_{$vs_name}"));
			}
		}
		
		return $this->opo_view;
	}
	# -------------------------------------------------------
	/**
	 * Get view object by reference
	 * @return View
	 */
	public function &getView() {
		if (!$this->opo_view) { $this->initView(); }
		return $this->opo_view;
	}
	# -------------------------------------------------------
	public function viewExists($ps_path) {
		if (!$this->opo_view) { $this->initView(); }
		return $this->opo_view->viewExists($ps_path);
	}
	# -------------------------------------------------------
	public function getTagListForView($ps_path) {
		if (!$this->opo_view) { $this->initView(); }
		return $this->opo_view->getTagList($ps_path);
	}
	# -------------------------------------------------------
	/**
	 * Render view file
	 * 
	 * @param string $ps_view path to view file
	 * @param bool $pb_dont_add_content_to_response
	 * @param bool $pb_dont_replace_tags
	 * @return mixed|null|string
	 */
	public function &render($ps_view, $pb_dont_add_content_to_response=false, $pb_dont_replace_tags=false) {
		if (!$this->opo_view) { $this->initView(); }
		
		$vs_content = $this->opo_view->render($ps_view, $pb_dont_replace_tags);
		
		if ($this->opo_view->numErrors() > 0) {
			$this->errors = $this->opo_view->errors;
		}
		if (!$pb_dont_add_content_to_response) {
			$this->opo_response->addContent($vs_content, 'view');
		}
		return $vs_content;
	}
	# -------------------------------------------------------
	/**
	 * Get request object (by reference)
	 *
	 * @return RequestHTTP
	 */
	public function &getRequest() {
		return $this->opo_request;
	}
	# -------------------------------------------------------
	/*
	 * Get response object (by reference)
	 * @return ResponseHTTP
	 */
	public function &getResponse() {
		return $this->opo_response;
	}
	# -------------------------------------------------------
	public function forward($ps_path) {
		$this->opo_request->setPath($ps_path);
		$this->opo_request->setIsDispatched(false);
	}
	# -------------------------------------------------------
	public function redirect($ps_url, $pn_code=302) {
		$this->opo_response->setRedirect($ps_url, $pn_code);
	}
	# -------------------------------------------------------
	public function __call($ps_methodname, $pa_params) {
		$this->clearErrors();
		
		if (file_exists(__CA_APP_DIR__."/controllers/DefaultController.php")) {
			require_once(__CA_APP_DIR__."/controllers/DefaultController.php");
			$o_default_controller = new DefaultController($this->opo_request, $this->opo_response, $this->opa_view_paths);
			
			// Take rest of path and pass as params to DefaultController __call()
			$va_params = array($this->opo_request->getAction());
			if ($vs_action_extra = $this->opo_request->getActionExtra()) { $va_params[] = $vs_action_extra; } 
			$va_path_params = $this->opo_request->getParameters(array('PATH'));
			foreach($va_path_params as $vs_param => $vs_value) {
				if (!$vs_param) { $va_params[] = $vs_param; }
				if (!$vs_value) { $va_params[] = $vs_value; }
			}
			if (method_exists($o_default_controller, $this->opo_request->getController())) { 
			    return $o_default_controller->{$this->opo_request->getController()}($va_params);
			}
		}
		$this->postError(2310, _t("Action '%1' in class '%2' is invalid", $ps_methodname, get_class($this)), "ActionController->__call()");
		return false;
	}
	# -------------------------------------------------------
}
