<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/IIIFController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2023 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Service/BaseServiceController.php');
require_once(__CA_LIB_DIR__.'/Service/IIIFService.php');

class IIIFController extends BaseServiceController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	# -------------------------------------------------------
	public function __call($ps_identifier, $pa_args) {
		try {
			$va_content = IIIFService::dispatch($ps_identifier, $this->getRequest(), $this->getResponse());
		} catch(Exception $e) {
			$this->getView()->setVar('errors', array($e->getMessage()));
			$this->render('json_error.php');
			return;
		}

		if(intval($this->getRequest()->getParameter('pretty', pInteger))>0) {
			$this->getView()->setVar('pretty_print', true);
		}

		$this->getView()->setVar('content', $va_content);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function manifest() {
		$path = explode('/', $this->request->getPathInfo()); // path = /IIIF/manifest/<identifier> ; identifier index = 3
		try {
			$manifest = IIIFService::manifest($path[3], $this->getRequest());
		} catch(IIIFAccessException $e) {
			$this->getView()->setVar('errors', array($e->getMessage()));
			$this->render('json_error.php');
			return;
		}

		if(intval($this->getRequest()->getParameter('pretty', pInteger))>0) {
			$this->getView()->setVar('pretty_print', true);
		}

		$this->getView()->setVar('dontEmitOK', true);
		$this->getView()->setVar('content', $manifest);
		$this->render('json.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function cliplist() {
		$path = explode('/', $this->request->getPathInfo()); // path = /IIIF/clips/<identifier> ; identifier index = 3
		$vtt = $this->request->getParameter('vtt', pInteger); // return clip list in VTT format?
		
		try {
			$clip_list = IIIFService::clipList($path[3], $this->getRequest(), ['vtt' => $vtt]);
		} catch(IIIFAccessException $e) {
			$this->getView()->setVar('errors', array($e->getMessage()));
			$this->render('json_error.php');
			return;
		}

		if($vtt) {
			header('Content-type: text/vtt');
			print $clip_list;
			exit;	
		} 
		if(intval($this->getRequest()->getParameter('pretty', pInteger))>0) {
			$this->getView()->setVar('pretty_print', true);
		}

		$this->getView()->setVar('dontEmitOK', true);
		$this->getView()->setVar('content', $clip_list);
		$this->render('json.php');
	}
	# -------------------------------------------------------
}
