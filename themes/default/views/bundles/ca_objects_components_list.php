<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_components_list.php : 
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
	
	if (!($vs_add_label 		= $this->getVar('add_label'))) { $vs_add_label = _t('Add component'); }
	$vs_display_template		= caGetOption('displayTemplate', $va_settings, $t_subject->getAppConfig()->get('ca_objects_component_display_settings'));

	$va_errors = array();
	
	$vn_num_components = ($qr_components = $t_subject->getComponents(array('returnAs' => 'searchResult'))) ? $qr_components->numHits() : 0;
	
	print "({$vn_num_components})";	// print number of components next to the bundle title
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>

<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">	
<?php
	if ($vn_num_components) {
?>
		<div style="column-count:3; -webkit-column-count:3; -moz-column-count:3;">
<?php
		while($qr_components->nextHit()) {
?>
				<div style="font-weight: normal;">
					<?php print $qr_components->getWithTemplate($vs_display_template); ?>
				</div>
<?php
		}
?>
		</div>
<?php
	} else {
?>
				<div><?php print _t('No components defined'); ?></div>
<?php
	}
			
?>
	<div class='button labelInfo caAddItemButton'><?php print '<a href="#" onclick=\'caObjectComponentPanel.showPanel("'.caNavUrl($this->request, '*', 'ObjectComponent', 'Form', array('parent_id' => $t_subject->getPrimaryKey())).'"); return false;\')>'; ?><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print $vs_add_label; ?></a></div>

			</div>
		</div>
	</div>
</div>