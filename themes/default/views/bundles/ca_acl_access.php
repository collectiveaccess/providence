<?php
/* ----------------------------------------------------------------------
 * bundles/ca_acl_access.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
	$t_instance 		= $this->getVar('t_instance');
	
	
	$vb_can_edit	 	= $t_instance->isSaveable($this->request);
	$vb_can_delete		= $t_instance->isDeletable($this->request);
?>
<div class="sectionBox">
<?php
	if ($vb_can_edit) {
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'caAccessControlList').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), $this->request->getModulePath(), $this->request->getController(), 'Access/'.$this->request->getActionExtra(), array($t_instance->primaryKey() => $t_instance->getPrimaryKey())),
			'',
			''
		);
	}
	print caFormTag($this->request, 'SetAccess', 'caAccessControlList');
?>	
	<div class='globalAccess'>
		<div class='title'><?php print _t('Global access'); ?></div>
<?php 	
		$va_global_access = $t_instance->getACLWorldAccess(array('returnAsInitialValuesForBundle' => true));
		$va_global_access_status = $va_global_access['access_display'];
		print "<p>"._t('All groups and users ')." <b>"; 
		print "<span class='accessName'>".$va_global_access_status."</span>";
		print"</b> "._t('this record, unless you create an exception')."</p>";

?>
		<div id='editGlobalAccess'>
	<?php
		print $t_instance->getACLWorldHTMLFormBundle($this->request, 'caAccessControlList');	
	?>	
		</div>
		<div id='editGlobalAccessLink' class='editLink'><a href='#' onclick='jQuery("#editGlobalAccess").show(250); jQuery("#editGlobalAccessLink").hide()'><img src='<?php print $this->request->getThemeUrlPath() ?>/graphics/buttons/edit.png' border='0' /> <?php print _t('Edit Global Access'); ?></a></div>
		<div style='width:100%; clear:both; height: 1px;'></div> 
	</div>
	<div class='globalAccess'>
		<div class='title'><?php print _t('Exceptions'); ?></div>
<?php
		if (($t_instance->getACLUserGroups()) || ($t_instance->getACLUsers())) {
		 	print "<p>"._t('The following groups and users have special access or restrictions for this record').".</p>";
		} else {
			print "<p>"._t('No access exceptions exist for this record')."</p>";
		}
		$va_group_access = $t_instance->getACLUserGroups(array('returnAsInitialValuesForBundle' => true));
		foreach($va_group_access as $va_group_access_item) {
			print "<div><span class='accessName'>".ucwords($va_group_access_item['name'])."</span> <b>(group)</b>: ".$va_group_access_item['access_display']."</div>";
		}
		$va_user_access = $t_instance->getACLUsers(array('returnAsInitialValuesForBundle' => true));
		foreach($va_user_access as $va_user_access_item) {
			print "<div><span class='accessName'>".$va_user_access_item['lname'].", ".$va_user_access_item['fname']."</span>: ".$va_user_access_item['access_display']."</div>";
		}
?>		
		<div id='editUserAccess'>
		<h2><?php print _t('Group access'); ?></h2>
	<?php
		print $t_instance->getACLGroupHTMLFormBundle($this->request, 'caAccessControlList');
		
		print caHTMLHiddenInput($t_instance->primaryKey(), array('value' => $t_instance->getPrimaryKey()));
	?>	
		<h2><?php print _t('User access'); ?></h2>
	<?php
		print $t_instance->getACLUserHTMLFormBundle($this->request, 'caAccessControlList');
	?>

		</div>
<?php
		if (($t_instance->getACLUserGroups()) || ($t_instance->getACLUsers())) {
?>
			<div id='editUserAccessLink' class='editLink'><a href='#' onclick='jQuery("#editUserAccess").show(250); jQuery("#editUserAccessLink").hide()'><img src='<?php print $this->request->getThemeUrlPath() ?>/graphics/buttons/edit.png' border='0' /> <?php print _t('Edit Exceptions'); ?></a></div>
<?php   
		} else {
?>
		<div id='editUserAccessLink' class='editLink'><a href='#' onclick='jQuery("#editUserAccess").show(250); jQuery("#editUserAccessLink").hide()'><img src='<?php print $this->request->getThemeUrlPath() ?>/graphics/buttons/edit.png' border='0' /> <?php print _t('Create an Exception'); ?></a></div>
<?php
		}		
?>
		<div style='width:100%; clear:both; height: 1px;'></div>
	</div>
<?php
		if ($t_instance->hasField('acl_inherit_from_ca_collections')) {
			print $t_instance->getBundleFormHTML('acl_inherit_from_ca_collections', 'acl_inherit_from_ca_collections', array('forACLAccessScreen' => true), array('request' => $this->request));
		}
		if ($t_instance->hasField('acl_inherit_from_parent')) {
			print $t_instance->getBundleFormHTML('acl_inherit_from_parent', 'acl_inherit_from_parent', array('forACLAccessScreen' => true), array('request' => $this->request));
		}
?>
	</form>	
	<div class="editorBottomPadding"><!-- empty --></div>
</div>
