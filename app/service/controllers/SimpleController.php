<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/SimpleController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Service/SimpleService.php');

class SimpleController extends BaseServiceController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}
	# -------------------------------------------------------
	public function __call($ps_endpoint, $pa_args) {
		try {
			$va_content = SimpleService::dispatch($ps_endpoint, $this->getRequest());
		} catch(Exception $e) {
			$this->getView()->setVar('errors', array($e->getMessage()));
			$this->render('json_error.php');
			return;
		}

		if(intval($this->getRequest()->getParameter('pretty', pInteger))>0) {
			$this->getView()->setVar('pretty_print', true);
		}

		$this->getView()->setVar('content', $va_content);
		$this->render('json.php');
	}
	# -------------------------------------------------------
}
