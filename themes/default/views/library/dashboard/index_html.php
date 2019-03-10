<?php
/* ----------------------------------------------------------------------
 * themes/default/views/library/dashboard/index_html.php : 
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
	$va_stats 				= $this->getVar('stats');
	$va_panels				= $this->getVar('panels');
	
	$ps_daterange 			= $this->getVar('daterange');
	$va_dates 				= caDateToUnixTimestamps($ps_daterange);
	$vs_daterange_proc 		= caGetLocalizedDateRange($va_dates[0], $va_dates[1], array('timeOmit' => true));

?>
	<h1><?php print _t('Statistics Dashboard'); ?></h1>
<?php

	print caFormTag($this->request, 'Index', 'libraryDashboardOptions', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true));
	print _t('Dates').': '.caHTMLTextInput('daterange', array('value' => $ps_daterange, 'class' => 'dateBg'), array('width' => '200px'));
?>
</form>

<br style="clear"/>

<div class="caLibraryDashboardPanel">
	<div class="caLibraryDashboardCallout">
<?php
		print _t('Items out: %1', (int)$va_stats['numCheckouts']);
?>
	</div>
<?php
	if ($vs_daterange_proc) { print "<div class=\"caLibraryDashboardCalloutDate\">{$vs_daterange_proc}</div>"; }
?>
	<div class="caLibraryDashboardUserList">
<?php
	print join(", ", $this->getVar('checkout_user_list'));
?>
	</div>
</div>

<div class="caLibraryDashboardPanel">
	<div class="caLibraryDashboardCallout">
<?php
		print _t('Items returned: %1', (int)$va_stats['numCheckins']);
?>
	</div>
<?php
	if ($vs_daterange_proc) { print "<div class=\"caLibraryDashboardCalloutDate\">{$vs_daterange_proc}</div>"; }
?>
	<div class="caLibraryDashboardUserList">
<?php
	print join(", ", $this->getVar('checkin_user_list'));
?>
	</div>
</div>

<div class="caLibraryDashboardPanel">
	<div class="caLibraryDashboardCallout">
<?php
		print _t('Reservations: %1', (int)$va_stats['numReservations']);
?>
	</div>
	<div class="caLibraryDashboardCalloutDate"><?php print _t('Current'); ?></div>
	<div class="caLibraryDashboardUserList">
<?php
	print join(", ", $this->getVar('reservation_user_list'));
?>
	</div>
</div>

<div class="caLibraryDashboardPanel">
	<div class="caLibraryDashboardCallout">
<?php
		print _t('Overdue items: %1', (int)$va_stats['numOverdueCheckouts']);
?>
	</div>
<?php
	if ($vs_daterange_proc) { print "<div class=\"caLibraryDashboardCalloutDate\">{$vs_daterange_proc}</div>"; }
?>
	<div class="caLibraryDashboardUserList">
<?php
	print join(", ", $this->getVar('overdue_checkout_user_list'));
?>
	</div>
</div>

<?php
	// Output configured panels
	foreach($va_panels as $vs_panel => $va_panel_info) {
		$va_panel_data = $this->getVar("panel_{$vs_panel}");
		if (!is_array($va_panel_data)) { continue; }
		asort($va_panel_data);
?>
<div class="caLibraryDashboardPanel">
	<div class="caLibraryDashboardCallout">
<?php
		print $va_panel_info['name'];
?>
	</div>
<?php
	if ($vs_daterange_proc) { print "<div class=\"caLibraryDashboardCalloutDate\">{$vs_daterange_proc}</div>"; }
?>
	<div class="caLibraryDashboardUserList">
		<ol>
<?php
	foreach(array_reverse($va_panel_data) as $vs_label => $vn_count) {
		print "<li>{$vs_label} ({$vn_count})</li>\n";
	}
?>
		</ol>
	</div>
</div>
<?php
	}
?>

<div id="caLibraryDashboardDetailContainer">

</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#caLibraryDashboardDetailContainer").hide();
		jQuery(".caLibraryUserLink").bind("click", function(e) {
			jQuery("#caLibraryDashboardDetailContainer").slideDown(250);
			jQuery("#caLibraryDashboardDetailContainer").load('<?php print caNavUrl($this->request, '*', '*', 'getUserDetail'); ?>', { daterange: '<?php print addslashes($vs_daterange_proc); ?>', user_id: jQuery(this).data('user_id') },
			function() {
				jQuery.scrollTo('#caLibraryDashboardDetailContainer',600);
			});
			e.preventDefault();
			return false;
		});
	});
	
</script>
