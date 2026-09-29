<?php
/* ----------------------------------------------------------------------
 * themes/default/views/dashboard/dashboard_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2025 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/DashboardManager.php');

AssetLoadManager::register('draggableUI');		// adds jQuery UI draggable 
AssetLoadManager::register('dashboard');			// adds caUI dashboard library
?>
<!-- Empty DIV uses for the "popup" widget chooser panel -->
<div id="dashboardWidgetPanel" class="dashboardWidgetPanel">
	<a href="#" onclick="caDashboardWidgetPanel.hideWidgetPanel();" class="dashboardWidgetPanelHide">&nbsp;</a>
	<div id="dashboardWidgetPanelTitle"><?= _t('Add a Widget'); ?></div>
	<div id="dashboardWidgetPanelContent">
		<!-- empty -->
	</div>
</div>

<script type="text/javascript">
	// create javascript dashboard UI object - handles logic for the Javascript elements of the dashboard
	var caDashboard = caUI.initDashboard({
		reorderURL: '<?= caNavUrl($this->request, '', 'Dashboard', 'moveWidgets'); ?>',
		
		dashboardClass: 'dashboard',
		columnClass: 'dashboardColumn',
		landingClass: 'dashboardLanding',
		widgetClass: 'portlet',
		widgetRemoveClass: 'dashboardRemoveWidget',
		widgetSettingsClass: 'dashboardWidgetSettingsButton',
		
		addID: 'dashboardAddButton',
		editID: 'dashboardEditButton',
		doneEditingID: 'dashboardDoneEditingButton',
		defaultID: 'dashboardDefaultButton',
		clearID: 'dashboardClearButton',
		welcomeMessageID: 'dashboard_welcome_message',
		editMessageID: 'dashboard_edit_message'
	});
	
	var caDashboardWidgetPanel = caUI.initWidgetPanel({widgetUrl: '<?= caNavUrl($this->request, '', 'Dashboard', 'getAvailableWidgetList'); ?>'});
</script>
<div id="dashboardControls">
	<div id="clearDashboardControl" style="float: left;">
		<?= caNavLink($this->request, _t('Default dashboard'), 'button-gray', '', 'Dashboard', 'default', array(), array('id' => 'dashboardDefaultButton')); ?>
		<?= caNavLink($this->request, _t('Clear dashboard'), 'button-gray', '', 'Dashboard', 'clear', array(), array('id' => 'dashboardClearButton')); ?> 
	</div>
	
	<a href="#" onclick='caDashboard.editDashboard(1);' class='button-gray' id='dashboardEditButton'><?= _t('Edit dashboard'); ?></a>
	<a href="#" onclick='caDashboardWidgetPanel.showWidgetPanel();' class='button-gray' id='dashboardAddButton'><?= _t('Add widget'); ?></a>
	<a href="#" onclick='caDashboard.editDashboard(0);' class='button-gray' id='dashboardDoneEditingButton'><?= _t('Done'); ?></a>
</div>
<div class="dashboard">
	<div class="dashboardWelcomeMessage" id="dashboard_welcome_message"><?= _t('This is your CollectiveAccess dashboard.  Click the "Edit Dashboard" button above to add widgets to your dashboard that will allow you to monitor system activity.  You\'ll see your dashboard whenever you login or  click the CollectiveAccess logo above.'); ?></div>
	<div class="dashboardWelcomeMessage" id="dashboard_edit_message"><?= _t('Use the button above to add a widget to your dashboard.  You can drag and drop the widgets in the left or right columns in the order you would like them to appear.  To customize the information in each widget, click the <i>"i"</i> button in the upper right corner of the widget.  To remove the widget from your dashboard click the "X" button in the upper right corner of the widget.  Click the "Clear dashboard" button above to remove all widgets from your dashboard.  When you are finished editing your dashboard, click the "Done" button above.'); ?></div>
	<div class="dashboardColumn"  id="dashboard_column_1">
	
		<div class="dashboardLanding" id="dashboardWidget_placeholder_1">
			<?= _t("To place a dashboard widget in this column drag it here"); ?>
		</div>
		
		<?= caGetDashboardWidgetHTML($this->request, 1);		// generate column 1  ?>
	
	</div><!-- end column -->
	
	<div class="dashboardColumn" id="dashboard_column_2">
		<div class="dashboardLanding" id="dashboardWidget_placeholder_2">
			<?= _t("To place a dashboard widget in this column drag it here"); ?>
		</div>

		<?php caGetDashboardWidgetHTML($this->request, 2);		// generate column 2	?>

	</div>
</div><!-- End dashboard -->

<?php	
// 
// PHP convenience function to generate HTML for dashboard columns
//
function caGetDashboardWidgetHTML($request, $column) {
	$o_dashboard_manager = DashboardManager::load($request);
	$widget_list = $o_dashboard_manager->getWidgetsForColumn($column);
	foreach($widget_list as $vn_i => $widget_info) {
		if (!($widget_content = $o_dashboard_manager->renderWidget($widget_info['widget'], $widget_info['widget_id'], $widget_info['settings']))) { continue; }
		print "<div class='portlet' id='dashboardWidget_{$column}_{$vn_i}'>";
		print caNavLink($request, caNavIcon(__CA_NAV_ICON_DELETE__, '16px'), 'dashboardRemoveWidget', '', 'Dashboard', 'removeWidget', array('widget' => $widget_info['widget'], 'widget_id' => $widget_info['widget_id']));
		if($o_dashboard_manager->widgetHasSettings($widget_info['widget'])) {
			print "<a href='#' class='dashboardWidgetSettingsButton' onclick='jQuery(\"#content_".$widget_info['widget_id']."\").load(\"".caNavUrl($request, '', 'Dashboard', 'getSettingsForm')."\", { widget_id: \"".$widget_info['widget_id']."\" }); return false;'>".caNavIcon(__CA_NAV_ICON_INFO__, '16px')."</a>";
		}
		print '<div class="portlet-header">'.WidgetManager::getWidgetTitle($widget_info['widget']).'</div>';
		print '<div class="portlet-content" id="content_'.$widget_info['widget_id'].'">'.$widget_content.'</div>';
		print '</div>';
	}
}
