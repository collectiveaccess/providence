<?php
/* ----------------------------------------------------------------------
 * app/widgets/notifications/views/main_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2022 Whirl-i-Gig
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

	/** @var RequestHTTP $request */
 	$request				= $this->getVar('request');
	$settings 			= $this->getVar('settings');
	$widget_id 			= $this->getVar('widget_id');
	$notification_list	= $this->getVar('notification_list');

	if(!is_array($notification_list) || !sizeof($notification_list)) {
		print "<div class=\"dashboardWidgetContentContainer dashboardWidgetScrollMedium\">"._t("You have no new notifications")."</div>";
	} else {
?>
		<a href='#' onclick='caMarkAllNotificationsAsRead(); return false;'><?= caNavIcon(__CA_NAV_ICON_CLOSE__, '14px').' '._t('Mark all as read'); ?></a>
		<div class="dashboardWidgetContentContainer dashboardWidgetScrollLarge" style="width:430px">
			<table class='dashboardWidgetTable'>
				<tr>
					<th>&nbsp;</th>
					<th><?= _t('Date/time'); ?></th>
					<th><?= _t('Message'); ?></th>
				</tr>

<?php
				foreach ($notification_list as $vn_notification_id => $notification) {
					print "<tr class='notificationWidgetMessages'>";
					print "<td><a href='#' onclick='caMarkNotificationAsRead(" . $notification['subject_id'] . ", " . $vn_notification_id . "); return false;'>" . caNavIcon(__CA_NAV_ICON_CLOSE__, '14px') . "</a></td>";
					print "<td>" .$notification['datetime_display'] . "</td>";
					print "<td id='notificationWidgetMessage{$vn_notification_id}'>";
					print "<div>" . $notification['message'] . "</div>";
					if ($notification['delivery_email_sent_on'] > 0) {
						print "<div class='dashboardWidgetContentSmallNote'>("._t("Email sent on %1", $notification['delivery_email_sent_on_display']).")</div>";
					}
					print "</td>";
					print "</tr>\n";
				}
?>
			</table>
		</div>

<?php
	}
?>

<script type="text/javascript">
	function caMarkNotificationAsRead(subject_id, notification_id) {
		jQuery.get("<?= caNavUrl($request, 'manage', 'Notifications', 'markAsRead')?>", { subject_id: subject_id });
		jQuery('#notificationWidgetMessage' + notification_id).parent().hide();
	}
	function caMarkAllNotificationsAsRead(notification_id) {
		jQuery.get("<?= caNavUrl($request, 'manage', 'Notifications', 'markAllAsRead')?>", { }, function() {
			jQuery('.notificationWidgetMessages').remove();
		});
	}
</script>
