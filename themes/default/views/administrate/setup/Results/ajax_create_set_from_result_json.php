<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/results/ajax_create_set_from_result_json.php :
 * 		basic object search form view script 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
 
	if ($vs_error = $this->getVar('error')) {
		print json_encode(array('status' => 'error', 'error' => $vs_error));
	} else {
		print json_encode(array(
			'status' 						=> 'ok', 
			'set_id' 						=> $this->getVar('set_id'), 
			'set_name' 						=> $this->getVar('set_name'),
			'set_code' 						=> $this->getVar('set_code'), 
			'num_items_added' 				=> $this->getVar('num_items_added')
		));
	}
?>