<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/cataloguing/CataloguingController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/Service/deprecated/CataloguingService.php');
	require_once(__CA_LIB_DIR__.'/ca/Service/BaseServiceController.php');
	require_once(__CA_LIB_DIR__.'/core/Zend/Soap/Server.php');
	require_once(__CA_LIB_DIR__.'/core/Zend/Soap/AutoDiscover.php');
	require_once(__CA_LIB_DIR__.'/core/Zend/Rest/Server.php');

	class CataloguingController extends BaseServiceController {
		# -------------------------------------------------------
		public function __construct(&$po_request, &$po_response, $pa_view_paths) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
		# -------------------------------------------------------
		public function soap(){
			$vs_wsdl =
				$this->request->config->get("site_host").
				__CA_URL_ROOT__.
				"/service.php/cataloguing/Cataloguing/soapWSDL";
			$vo_soapserver = new Zend_Soap_Server($vs_wsdl,array("soap_version" => SOAP_1_2));
			$vo_soapserver->setClass('CataloguingService',$this->request);
			$this->view->setVar("soap_server",$vo_soapserver);
			$this->render("cataloguing_soap.php");
		}
		# -------------------------------------------------------
		public function soapWSDL(){
			$vs_service =
				$this->request->config->get("site_host").
				__CA_URL_ROOT__.
				"/service.php/cataloguing/Cataloguing/soap";
			$vo_autodiscover = new Zend_Soap_AutoDiscover(true,$vs_service);
			$vo_autodiscover->setClass('CataloguingService',$this->request);
			$this->view->setVar("autodiscover",$vo_autodiscover);
			$this->render("cataloguing_soap_wsdl.php");
		}
		# -------------------------------------------------------
		public function rest(){
			$vo_restserver = new Zend_Rest_Server();
			$vo_restserver->returnResponse(true);
			$vo_restserver->setClass('CataloguingService',null,array($this->request));
			$this->view->setVar("rest_server",$vo_restserver);
			$this->render("cataloguing_rest.php");
		}
		# -------------------------------------------------------
	}
?>
