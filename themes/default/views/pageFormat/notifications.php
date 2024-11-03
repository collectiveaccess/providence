<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/notifications.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
		$class = "info";
		$icon_class = "fa fa-info-circle";
		
		switch($notification['type']) {
			case __NOTIFICATION_TYPE_ERROR__:
				$class = "error";
				$icon_class = "fa fa-exclamation-triangle";
				break;
			case __NOTIFICATION_TYPE_WARNING__:
				$class = "warning";
				$icon_class = "fa fa-exclamation-circle";
				break;
		}
?>
		<div class="notification-<?= $class; ?>-box rounded">
			<ul class="notification-<?= $class; ?>-box">
				<li class='notification-<?= $class; ?>-box'>
					<div class="notification-message-container">
						<div><i class="<?= $icon_class; ?>" aria-hidden="true" style="font-size: 36px;"></i></div>
						<div><?=$notification['message']; ?></div>
					</div>
				</li>
			</ul>
		</div>
<?php
	}
}
