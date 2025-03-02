<?php
/* ----------------------------------------------------------------------
 * app/widgets/downloads/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
$request				= $this->getVar('request');
$download_list			= $this->getVar('download_list');
$download_count			= $this->getVar('download_count');
$widget_id				= $this->getVar('widget_id');
?>
<div class="dashboardWidgetContentContainer">
<?php
	if(is_array($download_list) && sizeof($download_list)) {
?>	
	<div class="dashboardWidgetHeading"><?= _t("Available downloads"); ?></div>
	
	<div class="dashboardWidgetScrollMedium"><table style="width: 100%">
		<tr>
			<th><?= _t('File'); ?></th>
			<th><?= _t('Generated'); ?></th>
			<th><?= _t('Format'); ?></th>
			<th><?= _t('Type'); ?></th>
			<th> </th>
		</tr>
<?php
	foreach($download_list as $id => $download) {
		$md = caUnserializeForDatabase($download['metadata']);
?>
		<tr>
			<td><?= ($md['searchExpressionForDisplay'] ?? ''); ?></td>
			<td><?= caGetLocalizedDate($download['generated_on'], ['dateFormat' => 'delimited']); ?></td>
			<td><?= ($md['format'] ?? ''); ?></td>
			<td><?= ($download['download_type'] ?? ''); ?></td>
			<td><?= caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t("Download"), '', 'manage', 'Downloads', 'Download', ['download_id' => $download['download_id']], [], ['icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true]); ?></td>
		</tr>
<?php
	}
?>
	</table>
	
<?php
		if($download_count > sizeof($download_list)) {
			$n = $download_count - sizeof($download_list);
?>
		<div style="text-align: right; margin: 10px 10px 0 0;">
			<?= caNavLink($this->request, (($n == 1) ? _t("+ %1 additional download", $n) : _t("+ %1 additional downloads", $n)), '', 'manage', 'downloads', 'List', [], [], ['size' => '16px']); ?>
		</div>
<?php
		}
?>
	</div>
<?php
	} else {
?>
		<div class="dashboardWidgetHeading"><?= _t("No downloads available"); ?></div>
<?php
	}
?>
</div>
