<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/DisplayTemplateController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2015 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__ . '/Controller/ActionController.php');

class DisplayTemplateController extends ActionController {
	# -------------------------------------------------------
	public function Get() {
		$ps_template = $this->getRequest()->getParameter('template', pString);
		$ps_table = $this->getRequest()->getParameter('table', pString);
		$pn_id = $this->getRequest()->getParameter('id', pString);

		$t_instance = Datamodel::getInstance($ps_table);
		if(!($t_instance instanceof BundlableLabelableBaseModelWithAttributes)) {
			return false;
		}

		if(!($t_instance->load($pn_id))) {
			return false;
		}

		print @$t_instance->getWithTemplate($ps_template);
	}
	# -------------------------------------------------------
}
