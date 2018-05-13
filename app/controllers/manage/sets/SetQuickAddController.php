<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/sets/SetQuickAddController.php :
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

require_once(__CA_MODELS_DIR__.'/ca_sets.php');
require_once(__CA_LIB_DIR__.'/BaseQuickAddController.php');

class SetQuickAddController extends BaseQuickAddController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_sets';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
	}

	public function Save($pa_options=null) {
		$vn_rc = parent::Save($pa_options);

		$va_response = $this->getView()->getVar('response');

		if(is_array($va_response) && isset($va_response['id']) && ($va_response['id'] > 0)) {
			$t_set = Datamodel::getInstance('ca_sets', true);
			$t_set->getDb()->query('UPDATE ca_sets SET user_id=? WHERE set_id=?', $this->getRequest()->getUserID(), $va_response['id']);
		}
		return $vn_rc;
	}
	# -------------------------------------------------------
}
