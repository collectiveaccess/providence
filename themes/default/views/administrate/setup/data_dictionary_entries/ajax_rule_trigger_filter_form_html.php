<?php
/* ----------------------------------------------------------------------
 * app/views/manage/metadata_alert_triggers/ajax_rule_trigger_filter_form_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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

	/** @var ca_metadata_alert_triggers $t_trigger */
	$t_trigger = $this->getVar('t_trigger');
	$vs_id_prefix = $this->getVar('id_prefix');
	$vn_trigger_id = $t_trigger->getPrimaryKey();	
	$vn_element_id = $this->getVar('element_id');	
	
	if ($va_filters = $this->getVar('filters')) {
?>
		<div class="formLabel">
			<?php print _t('Limit to elements with'); ?><br/>
			<?php print join("; ", $va_filters); ?>
		</div>
	
	
		<script type="text/javascript">
			jQuery(document).ready(function() {
			
			});
		</script>
<?php
		print TooltipManager::getLoadHTML();
	}
