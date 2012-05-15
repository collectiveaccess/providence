<?php
/** ---------------------------------------------------------------------
 * includes/AppControllerPlugin.php : base class for application (front) controller plugin
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007 Whirl-i-Gig
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
 
	class AppControllerPlugin {
		# -------------------------------------------------------
		private $opo_request;
		private $opo_response;
		# -------------------------------------------------------
		public function __construct(&$po_request=null, &$po_response=null) {
			if ($po_request) {
				$this->setRequest($po_request);
			}
			if ($po_response) {
				$this->setResponse($po_response);
			}
		}
		# -------------------------------------------------------
		public function initPlugin(&$po_request, &$po_response) {
			$this->setRequest($po_request);
			$this->setResponse($po_response);
		}
		# -------------------------------------------------------
		public function getRequest() {
			return $this->opo_request;
		}
		# -------------------------------------------------------
		public function setRequest(&$po_request) {
			$this->opo_request =& $po_request;
		}
		# -------------------------------------------------------
		public function getResponse() {
			return $this->opo_response;
		}
		# -------------------------------------------------------
		public function setResponse(&$po_response) {
			$this->opo_response =& $po_response;
		}
		# -------------------------------------------------------
		public function routeStartup() {
			//$this->getResponse()->addContent("<p>routeStartup() called</p>\n");
		}
		# -------------------------------------------------------
		public function routeShutdown() {
			//$this->getResponse()->addContent("<p>routeShutdown() called</p>\n");
		}
		# -------------------------------------------------------
		public function dispatchLoopStartup() {
			//$this->getResponse()->addContent("<p>dispatchLoopStartup() called</p>\n");
		}
		# -------------------------------------------------------
		public function preDispatch() {
			//$this->getResponse()->addContent("<p>preDispatch() called</p>\n");
		}
		# -------------------------------------------------------
		public function postDispatch() {
			//$this->getResponse()->addContent("<p>postDispatch() called</p>\n");
		}
		# -------------------------------------------------------
		public function dispatchLoopShutdown() {
			//$this->getResponse()->addContent("<p>dispatchLoopShutdown() called</p>\n");
		}
		# -------------------------------------------------------
	}
?>