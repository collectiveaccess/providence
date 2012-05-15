<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/sets/ajax_set_item_info_json.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
	$va_errors = $this->getVar('errors');
	
	if (sizeof($va_errors)) {
		print json_encode(array('status' => 'error', 'errors' => $va_errors, 'set_id' => $this->getVar('set_id'), 'item_id' => $this->getVar('item_id')));
	} else {
		print json_encode(array('status' => 'ok', 'set_id' => $this->getVar('set_id'), 'item_id' => $this->getVar('item_id'), 'idno' => $this->getVar('idno'),  'idno_sort' => $this->getVar('idno_sort'), 'representation_tag' => $this->getVar('representation_tag'), 'representation_url' => $this->getVar('representation_url'),  'representation_width' => $this->getVar('representation_width'),  'representation_height' => $this->getVar('representation_height'), 'set_item_label' => $this->getVar('set_item_label')));
	}
?>