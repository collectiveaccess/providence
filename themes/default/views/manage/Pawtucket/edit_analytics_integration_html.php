<?php
/** ---------------------------------------------------------------------
 * themes/default/views/manage/Pawtucket/edit_analytics_integration_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
$form_elements = $this->getVar('form_elements');
?>
<div>
	<h1><?= _t('Web analytics integration'); ?></h1>
</div>
<div class="searchReindexHelpText">
	<?= _t('Web analytics services track and report on application usage and user behavior patterns. Typically, inclusion of service-specific analytics integration code is required. This code should have been provided by your analytics provider and is specific to your account.  Enter it below and it will be embedded on all Pawtucket-served pages.'); ?>
</div>
<div style="clear:both; height:1px;"><!-- empty --></div>
<?php	
	print caFormTag($this->request, 'saveAnalyticsIntegration', 'analyticsIntegrationForm', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]);

	if (sizeof($form_elements) > 0) {
		print $control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'analyticsIntegrationForm').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', '*', '*', 'editAnalyticsIntegration'),
			null, null
		);
		foreach($form_elements as $name => $info) {
?>
			<div class="bundleLabel">
				<span class="formLabelText"><?= $info['label']; ?></span>
				<div class="bundleContainer">
					<div class="caLabelList">
						<div style="padding: 10px 0px 10px 10px;">
							<?= $info['element']; ?>
						</div>
					</div>
				</div>
			</div>
<?php
		}
		print $control_box;
	}
?>
</form>
<div class="editorBottomPadding"><!-- empty --></div>
