<?php
/* ----------------------------------------------------------------------
 * views/editor/object_lots/delete_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 	$t_lot = $this->getVar('t_subject');
	$vn_lot_id = $this->getVar('subject_id');
?>
<div class="sectionBox">
<?php
	if (!$this->getVar('confirmed')) {
		$va_parameters = array('lot_id' => $vn_lot_id);
		$vs_warning = '';
		
		if ($vn_num_objects = $t_lot->numObjects()) {
			if ($vn_num_objects == 1) {
				$vs_warning = _t('There is 1 object linked to this lot. If you continue this object will no longer be associated with a lot.');
			} else {
				$vs_warning = _t('There are %1 objects linked to this lot. If you continue these objects will no longer be associated with a lot.', $vn_num_objects);
			}
			
			$va_parameters['warning'] = $vs_warning;
		}
		
		// show delete confirmation notice
		print caDeleteWarningBox($this->request, $t_lot, $this->getVar('subject_name'), 'editor/object_lots', 'ObjectLotEditor', 'Edit/'.$this->request->getActionExtra(), $va_parameters);
	}
?>
</div>