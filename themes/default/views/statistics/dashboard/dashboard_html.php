<?php
/* ----------------------------------------------------------------------
 * themes/default/statistics/dashboard/dashboard_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2022 Whirl-i-Gig
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
	$data = $this->getVar('data');
	$groups = $this->getVar('groups');
	$panels = $this->getVar('panels');
		
	if(!is_array($site_links = $this->getVar('site_links'))) { $site_links = []; }
	if(!is_array($group_links = $this->getVar('group_links'))) { $group_links = []; }
	
	if (sizeof($group_links) > 0) {
?>
	<div class="statisticsDashboardGroupList">
<?php
	print ($all_link = $this->getVar('all_link')) ? _t("View statistics for group: %1 or %2", join(", ", $group_links), $all_link) : _t("View statistics for group: %1", join(", ", $group_links));
?>
	</div>
<?php
	}
	if(sizeof($site_links) > 0) {
?>
	<div class="statisticsDashboardsiteList">
<?php
		print _t("View statistics for site: %1", join(", ", $this->getVar('site_links')));
?>
	</div>	
<?php
	}
?>
	<h2><?= $this->getVar('message'); ?>
<?php
    if ($last_update = $this->getVar('last_update')) {
?>
	(<?= _t('Last updated %1', $last_update); ?>)
<?php
    }
?>
    </h2>
<?php
    if (sizeof($data)) {
        foreach($panels as $panel => $panel_options) {
            print "<div class='statisticsDashboardPanel'>".StatisticsDashboard::renderPanel($this->request, $panel, $data, $panel_options)."</div>";
        }
    }
