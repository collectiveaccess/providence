<?php
/* ----------------------------------------------------------------------
 * themes/default/views/dashboard/ajax_settings_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
	$po_request 		= $this->getVar('request');
	$vs_widget_id 		= $this->getVar('widget_id');
	
?>
<div id="<?php print "caWidgetSettingForm_{$vs_widget_id}"; ?>">
	<form id="<?php print "caWidgetSettings_{$vs_widget_id}"; ?>" action="#" method="get">
<?php
			if ($vs_form = $this->getVar('form')) {
?>
		<h1><?php print _t('Settings'); ?></h1>
<?php
				print $vs_form;
				print caJSButton(
					$po_request, __CA_NAV_BUTTON_SAVE__, _t('Save'), '', 
					array('onclick' => 'jQuery("#caWidgetSettingForm_'.$vs_widget_id.'").load("'.caNavUrl($this->request, '', 'Dashboard', 'saveSettings', array()).'", jQuery("#caWidgetSettings_'.$vs_widget_id.'").serializeArray());'),
					array()
				).' ';
			} else {
?>
		<h1><?php print _t('No settings available'); ?></h1>
<?php
			}
			
			print caJSButton(
				$po_request, __CA_NAV_BUTTON_CANCEL__, _t('Cancel'), '', 
				array('onclick' => 'jQuery("#caWidgetSettingForm_'.$vs_widget_id.'").load("'.caNavUrl($this->request, '', 'Dashboard', 'getWidget', array()).'", jQuery("#caWidgetSettings_'.$vs_widget_id.'").serializeArray());',
				array())
			);
?>
			<?php print caHTMLHiddenInput('widget_id', array('value' => $vs_widget_id)); ?>
	</form>
</div>

<?php
	print TooltipManager::getLoadHTML();
?>