<?php
/* ----------------------------------------------------------------------
 * app/widgets/notifications/views/main_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

	/** @var RequestHTTP $po_request */
 	$po_request				= $this->getVar('request');
	$va_settings 			= $this->getVar('settings');
	$vs_widget_id 			= $this->getVar('widget_id');
	$va_notification_list	= $this->getVar('notification_list');
?>

<div class="dashboardWidgetContentContainer dashboardWidgetScrollMedium">
	<table class='dashboardWidgetTable'>
		<tr>
			<th><?php print _t('Date/time');?></th>
			<th><?php print _t('Message');?></th>
		</tr>
			
<?php
	foreach($va_notification_list as $vn_i => $va_notifications) {
		$vs_short_message = caTruncateStringWithEllipsis($va_notifications['message'], 50);
		if(strlen($va_notifications['message']) != strlen($vs_short_message)) {
			TooltipManager::add('#notificationWidgetMessage'.$vn_i, $va_notifications['message']);
		}

		print "<tr>";
		print "<td>".date("n/d/y, g:iA T", $va_notifications['datetime'])."</td>";
		print "<td id='notificationWidgetMessage{$vn_i}'>".$vs_short_message."</td>";
		print "</tr>\n";
	}
?>
	</table>
</div>
