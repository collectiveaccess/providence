<?php
/* ----------------------------------------------------------------------
 * themes/default/views/library/dashboard/user_detail_html.php : 
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
 $vs_user 				= $this->getVar('name');
 $va_checkouts 			= $this->getVar('checkouts');
 $va_overdue_checkouts 	= $this->getVar('overdue_checkouts');
 $va_checkins	 		= $this->getVar('checkins');
 $va_reservations 		= $this->getVar('reservations');
 
 ?>
 <h2><?php print _t('User activity for %1', $vs_user); ?></h2>
 
 <?php
 	foreach(array(
 		_t('Checkouts (%1)', sizeof($va_checkouts)) => &$va_checkouts,
 		_t('Overdue (%1)', sizeof($va_overdue_checkouts)) => &$va_overdue_checkouts,
 		_t('Check ins (%1)', sizeof($va_checkins)) => &$va_checkins,
 		_t('Reservations (%1)', sizeof($va_reservations)) => &$va_reservations,
 	) as $vs_heading => $va_list) {
 		if (sizeof($va_list) > 0) {
 ?>
 	<h3><?php print $vs_heading; ?></h3>
 <ul>
 <?php
		foreach($va_list as $va_data) {
			print "<li>".$va_data['_display']." <em>";
			
			$va_dates = array();
			if (isset($va_data['created_on']) && $va_data['created_on']) {
				$va_dates[] = _t('created %1', $va_data['created_on']);
			}
			if (isset($va_data['checkout_date']) && $va_data['checkout_date']) {
				$va_dates[] = _t('checked out %1', $va_data['checkout_date']);
			}
			if (isset($va_data['due_date']) && $va_data['due_date']) {
				$va_dates[] = _t('due %1', $va_data['due_date']);
			}
			if (isset($va_data['return_date']) && $va_data['return_date']) {
				$va_dates[] = _t('returned %1', $va_data['return_date']);
			}
			print join("; ", $va_dates).(($vs_notes = trim($va_data['checkout_notes'])) ? "<blockquote>{$vs_notes}</blockquote>" : '');
			
			print "</em></li>\n";
		}
 ?>
 </ul>
 <?php 
 		}
 	}

 ?>