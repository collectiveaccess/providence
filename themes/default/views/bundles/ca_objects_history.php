<?php
/* ----------------------------------------------------------------------
 * bundles/ca_objects_history.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 
 	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	$va_settings 				= $this->getVar('settings');

	$vb_read_only				=	(isset($va_settings['readonly']) && $va_settings['readonly']);
	
	if (!($vs_add_label 		= $this->getVar('add_label'))) { $vs_add_label = _t('Update location'); }
	
	$va_history					= $this->getVar('history');
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
<?php
	foreach($va_history as $vn_date => $va_history_entries_for_date) {
		foreach($va_history_entries_for_date as $va_history_entry) {
?>
			<div class="caUseHistoryEntry">
				<?php print $va_history_entry['icon']; ?>
				<div><?php print $va_history_entry['display']; ?></div>
				<div class="caUseHistoryDate"><?php print $va_history_entry['date']; ?></div>
				<br class="clear"/>
			</div>
<?php
		}
	}
?>
		</div>
		
	</div>
</div>

<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Change location'); ?></div>
		
	</div>
</div>	