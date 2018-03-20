<?php
/* ----------------------------------------------------------------------
 * default/views/mailTemplates/notification_digest.tpl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 	$notifications = $this->getVar('notifications');
 	$num_notifications = sizeof($notifications);
?>
	<h2><?php print ($num_notifications == 1) ? _t('You have %1 notification', $num_notifications) : _t('You have %1 notifications', $num_notifications); ?></h2>
	
	<ul>
<?php
	foreach($notifications as $notification) { 
		print "<li>".$notification['message']." <em>".$notification['datetime_display']."</em></li>\n";
	}
?>
	</ul>
