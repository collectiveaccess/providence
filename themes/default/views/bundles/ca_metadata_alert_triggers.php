<?php
/* ----------------------------------------------------------------------
 * bundles/ca_metadata_alert_rule_type_restrictions.php :
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
 *q
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

$vs_id_prefix = $this->getVar('placement_code').$this->getVar('id_prefix');
$vn_table_num = $this->getVar('table_num');
/** @var ca_metadata_alert_rules $t_rule */
$t_rule = $this->getVar('t_rule');
$t_trigger = $this->getVar('t_trigger');


$va_errors = array();
if(is_array($va_action_errors = $this->getVar('errors'))) {
	foreach($va_action_errors as $o_error) {
		$va_errors[] = $o_error->getErrorDescription();
	}
}

print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">
				<?php
				if (is_array($va_errors) && sizeof($va_errors)) {
					?>
					<span class="formLabelError"><?php print join('; ', $va_errors); ?></span>
					<?php
				}
				?>
				<div class="formLabel"><?php print _t('Trigger Element'); ?><br/>
				<?php print ca_metadata_elements::getElementListAsHTMLSelect($vs_id_prefix . 'element_id', [], false, $vn_table_num); ?>
				</div>
				<?php print $t_rule->htmlFormElement('trigger_type'); ?>
			</div>
		</div>
	</div>
</div>
