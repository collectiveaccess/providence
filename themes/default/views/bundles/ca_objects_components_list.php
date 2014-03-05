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

	$va_errors = array();
	
	$vn_num_components = ($qr_components = $t_subject->getComponents(null, array('returnAs' => 'searchResult'))) ? $qr_components->numHits() : 0;
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>

<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div>
<?php
	print ($vn_num_components == 1) ? _t('%1 component', $vn_num_components) : _t('%1 components', $vn_num_components);
?>
		</div>
<?php
	if ($vn_num_components) {
		while($qr_components->nextHit()) {
?>
		<div>
			<?php print $qr_components->getWithTemplate("<l>^ca_objects.preferred_labels.name</l> ^ca_objects.idno <br/>"); ?>
		</div>
<?php
		}
	} else {
?>
		<div><?php print _t('No components defined'); ?></div>
<?php
	}
	
	print _t('Add component').' <a href="#" onclick=\'caObjectComponentPanel.showPanel("'.caNavUrl($this->request, '*', 'ObjectComponent', 'Form', array('parent_id' => $t_subject->getPrimaryKey())).'"); return false;\')>'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__).'</a>';
			
?>

	</div>
</div>