<?php
/* ----------------------------------------------------------------------
 * bundles/ca_acl_access.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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
$t_instance 	= $this->getVar('t_instance');
	
$can_edit	 	= $t_instance->isSaveable($this->request);
$can_delete		= $t_instance->isDeletable($this->request);

$stats			= $this->getVar('statistics');
?>
<div class="sectionBox">
<?php
	if ($can_edit) {
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'caAccessControlList').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', $this->request->getModulePath(), $this->request->getController(), 'Access/'.$this->request->getActionExtra(), [$t_instance->primaryKey() => $t_instance->getPrimaryKey()]),
			'',
			''
		);
	}
	print caFormTag($this->request, 'SetAccess', 'caAccessControlList');
?>	
	<div class='globalAccess'>
		<div class='title'><?= _t('Global access'); ?></div>
<?php 	
		$global_access = $t_instance->getACLWorldAccess(['returnAsInitialValuesForBundle' => true]);
		$global_access_status = $global_access['access_display'];
		print "<p>"._t('All groups and users ')." <b>"; 
		print "<span class='accessName'>{$global_access_status}</span>";
		print"</b> "._t('this record, unless you create an exception')."</p>";

?>
		<div id='editGlobalAccess'>
<?= $t_instance->getACLWorldHTMLFormBundle($this->request, 'caAccessControlList');	?>	
		</div>
		<div id='editGlobalAccessLink' class='editLink'><a href='#' onclick='jQuery("#editGlobalAccess").show(250); jQuery("#editGlobalAccessLink").hide()'><?= caNavIcon(__CA_NAV_ICON_EDIT__, 2); ?>  <?= _t('Edit Global Access'); ?></a></div>
		<div style='width:100%; clear:both; height: 1px;'></div> 
	</div>
	<div class='globalAccess'>
		<div class='title'><?= _t('Exceptions'); ?></div>
<?php
		if (($t_instance->getACLUserGroups()) || ($t_instance->getACLUsers())) {
		 	print "<p>"._t('The following groups and users have special access or restrictions for this record').".</p>";
		} else {
			print "<p>"._t('No access exceptions exist for this record')."</p>";
		}
		$group_access = $t_instance->getACLUserGroups(['returnAsInitialValuesForBundle' => true]);
		foreach($group_access as $group_access_item) {
			print "<div><span class='accessName'>".ucwords($group_access_item['name'])."</span> <b>(group)</b>: ".$group_access_item['access_display']."</div>";
		}
		$user_access = $t_instance->getACLUsers(['returnAsInitialValuesForBundle' => true]);
		foreach($user_access as $user_access_item) {
			print "<div class='accessName'><span class='accessName'>".$user_access_item['lname'].", ".$user_access_item['fname']."</span>: ".$user_access_item['access_display'];
			if($user_access_item['inherited_from'] ?? null) {
				print '<div class="inheritName">'._t('Inherited from %1', $user_access_item['inherited_from']).'</div>';
			}
			print "</div>";
		}
?>		
		<div id='editUserAccess'>
			<h2><?= _t('Group access'); ?></h2>
<?php
			print $t_instance->getACLGroupHTMLFormBundle($this->request, 'caAccessControlList');			
			print caHTMLHiddenInput($t_instance->primaryKey(), ['value' => $t_instance->getPrimaryKey()]);
?>	
			<h2><?= _t('User access'); ?></h2>
			<?= $t_instance->getACLUserHTMLFormBundle($this->request, 'caAccessControlList'); ?>

		</div>
<?php
		if (($t_instance->getACLUserGroups()) || ($t_instance->getACLUsers())) {
?>
			<div id='editUserAccessLink' class='editLink'><a href='#' onclick='jQuery("#editUserAccess").show(250); jQuery("#editUserAccessLink").hide()'><?= caNavIcon(__CA_NAV_ICON_EDIT__, 2); ?> <?= _t('Edit Exceptions'); ?></a></div>
<?php   
		} else {
?>
			<div id='editUserAccessLink' class='editLink'><a href='#' onclick='jQuery("#editUserAccess").show(250); jQuery("#editUserAccessLink").hide()'><?= caNavIcon(__CA_NAV_ICON_EDIT__, 2); ?> <?= _t('Create an Exception'); ?></a></div>
<?php
		}		
?>
		<div style='width:100%; clear:both; height: 1px;'></div>
	</div>
<?php
		if ($t_instance->hasField('acl_inherit_from_ca_collections')) {
			print $t_instance->getBundleFormHTML('acl_inherit_from_ca_collections', '',['forACLAccessScreen' => true], ['request' => $this->request]);
		}
		if ($t_instance->hasField('acl_inherit_from_parent')) {
			print $t_instance->getBundleFormHTML('acl_inherit_from_parent', '', ['forACLAccessScreen' => true], ['request' => $this->request]);
		}
?>
<?php
if(
	($t_instance->hasField('acl_inherit_from_parent') && (($stats['subRecordCount'] ?? null) > 0))
	||
	(($t_instance->tableName() === 'ca_collections') && (($stats['relatedObjectCount'] ?? null) > 0))
) {
?>	
	<div class='globalAccess'>
		<div class='title'><?= _t('Usage'); ?></div>
<?php
	if(
		($t_instance->hasField('acl_inherit_from_parent') && (($stats['subRecordCount'] ?? null) > 0))
	) {
?>
		<p>
			<?= ($stats['inheritingSubRecordCount'] === 1) ? 
				_t('%1 %2 (out of %4 total) will inherit access settings from this %3', $stats['inheritingSubRecordCount'], $t_instance->getProperty('NAME_SINGULAR'), $t_instance->getProperty('NAME_SINGULAR'), $stats['subRecordCount']) 
				: 
				_t('%1 %2 (out of %4 total) will inherit access settings from this %3', $stats['inheritingSubRecordCount'], $t_instance->getProperty('NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['subRecordCount'])  
			?>
<?php
			if(
				($stats['subRecordCount'] !== $stats['inheritingSubRecordCount'])
				||
				($stats['inheritingSubRecordCount'] > 0)
			) {
?>
				<div style="margin-left: 10px;">
<?php
				if($stats['subRecordCount'] !== $stats['inheritingSubRecordCount']) {
?>
					<?= caHTMLCheckboxInput('set_all_acl_inherit_from_parent', ['id' => 'setAllACLInheritFromParent', 'value' => '1', ]); ?> <?= _t('Set all to inherit'); ?><span style='margin-left: 10px;'></span>
<?php
				}
				
				if($stats['inheritingSubRecordCount'] > 0) {
?>
					<?= caHTMLCheckboxInput('set_none_acl_inherit_from_parent', ['id' => 'setNoneACLInheritFromParent', 'value' => '1']); ?> <?= _t('Set all to not inherit'); ?>
<?php
				}
?>
				</div>
<?php
			}
?>			
		</p>
<?php
	}
?>
<?php
	if(($t_instance->tableName() === 'ca_collections') && (($stats['relatedObjectCount'] ?? null) > 0)) {
?>
		<p>
			<?= ($stats['inheritingRelatedObjectCount'] === 1) ? 
				_t('%1 %2 (out of %4 total) will inherit access settings from this %3', $stats['inheritingRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['relatedObjectCount']) 
				: 
				_t(' %1 %2 (out of %4 total) will inherit access settings from this %3', $stats['inheritingRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['relatedObjectCount'])  
			?>
<?php
			if(
				($stats['relatedObjectCount'] !== $stats['inheritingRelatedObjectCount'])
				||
				($stats['inheritingRelatedObjectCount'] > 0)
			) {
?>
				<div style="margin-left: 10px;">
<?php
				if($stats['relatedObjectCount'] !== $stats['inheritingRelatedObjectCount']) {
?>
					<?= caHTMLCheckboxInput('set_all_acl_inherit_from_ca_collections', ['id' => 'setAllACLInheritFromCollections', 'value' => '1']); ?> <?= _t('Set all to inherit'); ?><span style='margin-left: 10px;'></span>
<?php
				}
				
				if($stats['inheritingRelatedObjectCount'] > 0) {
?>
					<?= caHTMLCheckboxInput('set_none_acl_inherit_from_ca_collections', ['id' => 'setNoneACLInheritFromCollections', 'value' => '1']); ?> <?= _t('Set all to not inherit'); ?>
<?php
				}
?>
				</div>
<?php
			}
?>			
		</p>
<?php
	}
}
?>
	</form>	
	<div class="editorBottomPadding"><!-- empty --></div>
</div>

<script>
	jQuery(document).ready(function() {
		jQuery('#setAllACLInheritFromCollections, #setNoneACLInheritFromCollections').on('change', function(e) {
			if(jQuery(e.target).attr('id') == 'setAllACLInheritFromCollections') {
				jQuery('#setNoneACLInheritFromCollections').attr('checked', false);
			} else {
				jQuery('#setAllACLInheritFromCollections').attr('checked', false);;
			}
		});
		jQuery('#setAllACLInheritFromParent, #setNoneACLInheritFromParent').on('change', function(e) {
			if(jQuery(e.target).attr('id') == 'setAllACLInheritFromParent') {
				jQuery('#setNoneACLInheritFromParent').attr('checked', false);
			} else {
				jQuery('#setAllACLInheritFromParent').attr('checked', false);;
			}
		});
	});
</script>
