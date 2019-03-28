<?php
	$data = $this->getVar('data');
	$groups = $this->getVar('groups');
	$panels = $this->getVar('panels');
		
	if(!is_array($site_links = $this->getVar('site_links'))) { $site_links = []; }
	if(!is_array($group_links = $this->getVar('group_links'))) { $group_links = []; }
	
	if (sizeof($group_links) > 1) {
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
	<h2><?php print $this->getVar('message'); ?></h2>
<?php
	foreach($panels as $panel => $panel_options) {
		print "<div class='statisticsDashboardPanel'>".StatisticsDashboard::renderPanel($this->request, $panel, $data, $panel_options)."</div>";
	}
