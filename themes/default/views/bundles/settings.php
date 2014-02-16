<?php
/* ----------------------------------------------------------------------
 * bundles/settings.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 
 
	$vs_id_prefix 					= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject							= $this->getVar('t_subject');
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$vn_table_num.'Settings');
?>
<div id="<?php print $vs_id_prefix.$vn_table_num.'Settings'; ?>">
	<div class="bundleContainer">
		<div class="caItemList settingsBundle">
<?php 
				if ($vs_form = $t_subject->getHTMLSettingForm(array('id' => 'setting'))) {
					print $vs_form;
				} else {
					print _t('No settings');
				}	
?>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#<?php print $vs_id_prefix.$vn_table_num.'Settings'; ?> input").change();
	});
</script>