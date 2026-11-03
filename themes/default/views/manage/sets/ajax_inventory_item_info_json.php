<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/sets/ajax_inventory_item_info_json.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2025 Whirl-i-Gig
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
$errors = $this->getVar('errors');

if (is_array($errors) && sizeof($errors)) {
	print json_encode(array('status' => 'error', 'errors' => $errors, 'set_id' => $this->getVar('set_id'), 'row_id' => $this->getVar('row_id')));
} else {
	print json_encode(array(
		'status' => 'ok',
		'set_id' => $this->getVar('set_id'),
		'row_id' => $this->getVar('row_id'),
		'idno' => $this->getVar('idno'),
		'representation_tag' => $this->getVar('representation_tag'),
		'label' => $this->getVar('label'),
		'name' => $this->getVar('name'),
		'displayTemplate' => $this->getVar('displayTemplate'),
		'name' => $this->getVar('name'),
		'rank' => $this->getVar('rank'),
		'rank' => $this->getVar('rank'),
	));
}
