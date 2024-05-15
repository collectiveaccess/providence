<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/objects/ListItemQuickAddController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/BaseQuickAddController.php");

class ListItemQuickAddController extends BaseQuickAddController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_list_items';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
}
