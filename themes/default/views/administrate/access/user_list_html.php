<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/user_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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
	$va_user_list = $this->getVar('user_list');

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caUserList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
<?php 
		print caFormTag($this->request, 'ListUsers', 'caUserListForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caUserList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			''._t('Show %1 users', caHTMLSelect('userclass', $this->request->user->getFieldInfo('userclass', 'BOUNDS_CHOICE_LIST'), array('onchange' => 'jQuery("#caUserListForm").submit();'), array('value' => $this->getVar('userclass')))), 
			caNavHeaderButton($this->request, __CA_NAV_BUTTON_ADD__, _t("New user"), 'administrate/access', 'Users', 'Edit', array('user_id' => 0))
		); 

	if(sizeof($va_user_list)){	
?>	
		<a href='#' id='showTools' onclick='jQuery("#searchToolsBox").slideDown(250); jQuery("#showTools").hide(); return false;'><?php print _t("Tools"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>
<?php
		print $this->render('user_tools_html.php');
	}
?>
	
		<h1><?php print _t('%1 users', ucfirst($this->getVar('userclass_displayname'))); ?></h1>
		
		<table id="caUserList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
			<thead>
				<tr>
					<th class="list-header-unsorted">
						<?php print _t('Login name'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Name'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Email'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Active?'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Last login'); ?>
					</th>
					<th class="{sorter: false} list-header-nosort">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
<?php
	$o_tep = new TimeExpressionParser();
	foreach($va_user_list as $va_user) {
		if ($va_user['last_login'] > 0) {
			$o_tep->setUnixTimestamps($va_user['last_login'], $va_user['last_login']);
		}
?>
			<tr>
				<td>
					<?php print $va_user['user_name']; ?>
				</td>
				<td>
					<?php print $va_user['lname'].', '.$va_user['fname']; ?>
				</td>
				<td>
					<?php print $va_user['email']; ?>
				</td>
				<td>
					<?php print $va_user['active'] ? _t('Yes') : _t('No'); ?>
				</td>
				<td>
					<?php print ($va_user['last_login'] > 0) ? $o_tep->getText() : '-'; ?>
				</td>
				<td>
					<nobr><?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'administrate/access', 'Users', 'Edit', array('user_id' => $va_user['user_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/access', 'Users', 'Delete', array('user_id' => $va_user['user_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?></nobr>
				</td>
			</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
</div>
	<div class="editorBottomPadding"><!-- empty --></div>