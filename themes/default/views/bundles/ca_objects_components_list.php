<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_components_list.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
$id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
$placement_id			= $this->getVar('placement_id');
$table_num 				= $this->getVar('table_num');
$t_subject				= $this->getVar('t_subject');	// parent artwork
$t_instance				= $this->getVar('t_instance');	// currently selected record (currently edited artwork or element)
$settings 				= $this->getVar('settings');
$read_only				= (isset($settings['readonly']) && $settings['readonly']);

if (!($add_label 		= $this->getVar('add_label'))) { $add_label = _t('Add component'); }

$container_template		= caGetOption('containerTemplate', $settings, null);
if(!$container_template) { $container_template = $t_subject->getAppConfig()->get('ca_objects_component_container_template'); }

$display_template		= caGetOption('displayTemplate', $settings, null);
if(!$display_template) { $display_template = $t_subject->getAppConfig()->get('ca_objects_component_display_template'); }

$current_display_template		= caGetOption('currentDisplayTemplate', $settings, null);
if(!$current_display_template) { $current_display_template = $t_subject->getAppConfig()->get('ca_objects_component_current_display_template'); }

$num_columns			= caGetOption('numColumns', $settings, $t_subject->getAppConfig()->get('ca_objects_component_num_columns'));
if(!$num_columns) { $num_columns = 1; }

$component_list  		= $this->getVar('component_list');
$num_components  		= $this->getVar('component_count');

$errors = [];

if (!$this->request->isAjax()) {
	print "({$num_components})";	// print number of components next to the bundle title
	print caEditorBundleShowHideControl($this->request, $id_prefix);
}
?>

<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
		<div class='bundleSubLabel'>
			<div class="button batchEdit" id="batchEdit<?= $id_prefix; ?>"><a href="#"><?= caNavIcon(__CA_NAV_ICON_BATCH_EDIT__, '15px')._t(' Batch edit'); ?></a></div>
			<div class="button addComponent" id="addComponent<?= $id_prefix; ?>"><?= '<a href="#" onclick=\'caObjectComponentPanel.showPanel("'.caNavUrl($this->request, '*', 'ObjectComponent', 'Form', array('parent_id' => $t_subject->getPrimaryKey())).'"); return false;\')>'; ?><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label; ?></a></div>
		</div>
		<br style="clear"/>
		
		<div class="caItemList">
			<div class="labelInfo">	
<?php
	if ($num_components) {
		$component_display_list = [];
		while($component_list->nextHit()) {
			$component_display_list[] = $component_list->getWithTemplate(($component_list->getPrimaryKey() == $t_instance->getPrimaryKey()) ? $current_display_template : $display_template);
		}
		
		if($container_template && (strpos($container_template, '^COMPONENT_LIST') !== false)) {
			$component_display = str_replace("^COMPONENT_LIST", join('', $component_display_list), $container_template);
		} else {
			$component_display = join('', $component_display_list);
		}
?>
		<div id="<?= $id_prefix; ?>componentList" style="column-count:<?= $num_columns; ?>; -webkit-column-count:<?= $num_columns; ?>; -moz-column-count:<?= $num_columns; ?>;">
			<?= $component_display ?>
		</div>
<?php
	} else {
?>
				<div><?= _t('No components defined'); ?></div>
<?php
	}			
?>
				<div class='button labelInfo caAddItemButton'><?= '<a href="#" onclick=\'caObjectComponentPanel.showPanel("'.caNavUrl($this->request, '*', 'ObjectComponent', 'Form', array('parent_id' => $t_subject->getPrimaryKey())).'"); return false;\')>'; ?><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label; ?></a></div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#batchEdit<?= $id_prefix; ?>').on('click', function(e) {
			window.location = '<?= caNavUrl($this->request, '*', '*', 'BatchEdit', ['placement_id' => $placement_id, 'primary_id' => $t_subject->getPrimaryKey(), 'screen' => $this->request->getActionExtra()]);?>'
		});
	});
</script>
