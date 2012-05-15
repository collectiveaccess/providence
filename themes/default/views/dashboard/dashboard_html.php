<?php
/* ----------------------------------------------------------------------
 * themes/default/views/dashboard/dashboard_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/DashboardManager.php');
 
	JavascriptLoadManager::register('draggableUI');		// adds jQuery UI draggable 
	JavascriptLoadManager::register('dashboard');			// adds caUI dashboard library
?>

<!-- Empty DIV uses for the "popup" widget chooser panel -->
<div id="dashboardWidgetPanel" class="dashboardWidgetPanel">
	<a href="#" onclick="caDashboardWidgetPanel.hideWidgetPanel();" class="dashboardWidgetPanelHide">&nbsp;</a>
		<div id="dashboardWidgetPanelTitle"><?php print _t('Add a Widget'); ?></div>
	<div id="dashboardWidgetPanelContent">
		<!-- empty -->
	</div>
</div>



<script type="text/javascript">
	// create javascript dashboard UI object - handles logic for the Javascript elements of the dashboard
	var caDashboard = caUI.initDashboard({
		reorderURL: '<?php print caNavUrl($this->request, '', 'Dashboard', 'moveWidgets'); ?>',
		
		dashboardClass: 'dashboard',
		columnClass: 'dashboardColumn',
		landingClass: 'dashboardLanding',
		widgetClass: 'portlet',
		widgetRemoveClass: 'dashboardRemoveWidget',
		widgetSettingsClass: 'dashboardWidgetSettingsButton',
		
		addID: 'dashboardAddButton',
		editID: 'dashboardEditButton',
		doneEditingID: 'dashboardDoneEditingButton',
		clearID: 'dashboardClearButton',
		welcomeMessageID: 'dashboard_welcome_message',
		editMessageID: 'dashboard_edit_message'
	});
	
	var caDashboardWidgetPanel = caUI.initWidgetPanel({widgetUrl: '<?php print caNavUrl($this->request, '', 'Dashboard', 'getAvailableWidgetList'); ?>'});
</script>
<div id="dashboardControls">
	<div id="clearDashboardControl" style="float: left;">
		<?php print caNavLink($this->request, _t('Clear dashboard').' &rsaquo;', 'dashboardControl', '', 'Dashboard', 'clear', array(), array('id' => 'dashboardClearButton')); ?>
	</div>
	
	<a href="#" onclick='caDashboard.editDashboard(1);' class='dashboardControl' id='dashboardEditButton'><?php print _t('Edit dashboard'); ?> &rsaquo;</a>
	<a href="#" onclick='caDashboardWidgetPanel.showWidgetPanel();' class='dashboardControl' id='dashboardAddButton'><?php print _t('Add widget'); ?> &rsaquo;</a>
	<a href="#" onclick='caDashboard.editDashboard(0);' class='dashboardControl' id='dashboardDoneEditingButton'><?php print _t('Done'); ?> &rsaquo;</a>
</div>
<div class="dashboard">
	<div class="dashboardWelcomeMessage" id="dashboard_welcome_message"><?php print _t('This is your CollectiveAccess dashboard.  Click the "Edit Dashboard" button above to add widgets to your dashboard that will allow you to monitor system activity.  You\'ll see your dashboard whenever you login or  click the CollectiveAccess logo above.'); ?></div>
	<div class="dashboardWelcomeMessage" id="dashboard_edit_message"><?php print _t('Use the button above to add a widget to your dashboard.  You can drag and drop the widgets in the left or right columns in the order you would like them to appear.  To customize the information in each widget, click the <i>"i"</i> button in the upper right corner of the widget.  To remove the widget from your dashboard click the "X" button in the upper right corner of the widget.  Click the "Clear dashboard" button above to remove all widgets from your dashboard.  When you are finished editing your dashboard, click the "Done" button above.'); ?></div>
	
	<div class="dashboardColumn"  id="dashboard_column_1">
	
		<div class="dashboardLanding" id="dashboardWidget_placeholder_1">
			<?php print _t("To place a dashboard widget in this column drag it here"); ?>
		</div>
		
		<?php print caGetDashboardWidgetHTML($this->request, 1);		// generate column 1  ?>
	
	</div><!-- end column -->
	
	<div class="dashboardColumn" id="dashboard_column_2">
		<div class="dashboardLanding" id="dashboardWidget_placeholder_2">
			<?php print _t("To place a dashboard widget in this column drag it here"); ?>
		</div>

		<?php caGetDashboardWidgetHTML($this->request, 2);		// generate column 2	?>

	</div>
</div><!-- End dashboard -->

<?php	
	// 
	// PHP convenience function to generate HTML for dashboard columns
	//
	function caGetDashboardWidgetHTML($po_request, $pn_column) {
		$o_dashboard_manager = DashboardManager::load($po_request);
		$va_widget_list = $o_dashboard_manager->getWidgetsForColumn($pn_column);
		foreach($va_widget_list as $vn_i => $va_widget_info) {
			print "<div class='portlet' id='dashboardWidget_{$pn_column}_{$vn_i}'>";
			print caNavLink($po_request, '<img src="'.$po_request->getThemeUrlPath().'/graphics/spacer.gif" width="16" height="16" border="0" title="'._t("remove widget from dashboard").'">', 'dashboardRemoveWidget', '', 'Dashboard', 'removeWidget', array('widget' => $va_widget_info['widget'], 'widget_id' => $va_widget_info['widget_id']));
			if($o_dashboard_manager->widgetHasSettings($va_widget_info['widget'])) {
				print "<a href='#' class='dashboardWidgetSettingsButton' onclick='jQuery(\"#content_".$va_widget_info['widget_id']."\").load(\"".caNavUrl($po_request, '', 'Dashboard', 'getSettingsForm')."\", { widget_id: \"".$va_widget_info['widget_id']."\" }); return false;'><img src='".$po_request->getThemeUrlPath()."/graphics/spacer.gif' width='16' height='16' border='0' title='"._t("Modify settings for this widget")."'></a>";
			}
			print '<div class="portlet-header">'.WidgetManager::getWidgetTitle($va_widget_info['widget']).'</div>';
			print '<div class="portlet-content" id="content_'.$va_widget_info['widget_id'].'">'.$o_dashboard_manager->renderWidget($va_widget_info['widget'], $va_widget_info['widget_id'], $va_widget_info['settings']).'</div>';
			print '</div>';
		}
	}
?>	