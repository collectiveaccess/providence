<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/notifications.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2021 Whirl-i-Gig
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

if (sizeof($this->getVar('notifications'))) {
	foreach($this->getVar('notifications') as $notification) {
		switch($notification['type']) {
			case __NOTIFICATION_TYPE_ERROR__:
				print '<div class="notification-error-box rounded">';
				print '<ul class="notification-error-box">';
				print "<li class='notification-error-box'>".$notification['message']."</li>\n";
				break;
			case __NOTIFICATION_TYPE_WARNING__:
				print '<div class="notification-warning-box rounded">';
				print '<ul class="notification-warning-box">';
				print "<li class='notification-warning-box'>".$notification['message']."</li>\n";
				break;
			default:
				print '<div class="notification-info-box rounded">';
				print '<ul class="notification-info-box">';
				print "<li class='notification-info-box'>".$notification['message']."</li>\n";
				break;
		}
?>
			</ul>
		</div>
<?php
	}
}