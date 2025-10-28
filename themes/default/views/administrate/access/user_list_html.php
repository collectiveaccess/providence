<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/user_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
$user_list = $this->getVar('user_list');
$num_pages = $this->getVar('num_pages');
$page = $this->getVar('page');

if(!$this->request->isAjax()) {
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caItemList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
<?php 
		print caFormTag($this->request, 'ListUsers', 'caUserListForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true));
		print caFormControlBox(
			'<div class="list-filter">'._t('Search').': <input type="text" name="search" id="userSearch" value="" size="20"/></div>', 
			''._t('Show %1 users', caHTMLSelect('userclass', $this->request->user->getFieldInfo('userclass', 'BOUNDS_CHOICE_LIST'), array('onchange' => 'jQuery("#caUserListForm").submit();'), array('value' => $this->getVar('userclass')))), 
			caNavHeaderButton($this->request, __CA_NAV_ICON_ADD__, _t("New user"), 'administrate/access', 'Users', 'Edit', array('user_id' => 0), [], ['size' => '30px'])
		); 
?>		
		</form>
<script language="JavaScript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#userSearch').autocomplete(
			{
				minLength: 3, delay: 800, html: true,
				source: '<?= caNavUrl($this->request, 'lookup', 'User', 'Get', ['noInline' => 1]); ?>',
				select: function(event, ui) {
					if (parseInt(ui.item.id) > 0) {
						jQuery('#userSearch').val('');
						document.location = '<?= caNavUrl($this->request, '*', '*', 'Edit'); ?>/user_id/' + ui.item.id;
					}
				}
			}
		).click(function() { this.select(); });
	});
	
	function _navigateToNewForm(type_id, table_num) {
		document.location = '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'Edit', array('set_id' => 0)); ?>/type_id/' + type_id + '/table_num/' + table_num;
	}
</script>
		<h1 style='float:left; margin:10px 0px 10px 0px;'><?= _t('%1 users', ucfirst($this->getVar('userclass_displayname'))); ?></h1>
<?php
if(sizeof($user_list)){	
?>	
	<a href='#' id='showTools' style="float:left;margin-top:10px;" onclick='jQuery("#searchToolsBox").slideDown(250); jQuery("#showTools").hide(); return false;'><?= caNavIcon(__CA_NAV_ICON_SETTINGS__, "24px");?></a>
<?php
	print $this->render('user_tools_html.php');
}
?>
<div id='resultBox' style="clear: both;">
<?php
} // fixed - not loaded on ajax calls
?>
<div class='searchNav' style="width: 100%;">
<?php
if(($num_pages > 1) && !$this->getVar('dontShowPages')){
	print "<div class='nav'>";
	if ($page > 0) {
		print "<a href='#' onclick='jQuery(\"#resultBox\").load(\"".caNavUrl($this->request, '*', '*', '*', ['page' => $page - 1])."\"); return false;' class='button'>&lsaquo; "._t("Previous")."</a>";
	}
	print '&nbsp;&nbsp;&nbsp;'._t("Page").' '.($page + 1).'/'.$num_pages.'&nbsp;&nbsp;&nbsp;';
	if ($page < ($num_pages - 1)) {
		print "<a href='#' onclick='jQuery(\"#resultBox\").load(\"".caNavUrl($this->request, '*', '*', '*', ['page' => $page + 1])."\"); return false;' class='button'>"._t("Next")." &rsaquo;</a>";
	}
	print "</div>";
	print '<form action="#">'._t('Jump to page').': <input type="text" size="3" name="page" id="jumpToPageNum" value=""/> <a href="#" onclick=\'jQuery("#resultBox").load("'.caNavUrl($this->request, '*', '*', '*', []).'/page/" + (jQuery("#jumpToPageNum").val() - 1));\' class="button">'.caNavIcon(__CA_NAV_ICON_GO__, "14px").'</a></form>';
}
print _t('%1 users', $this->getVar('count'));
print "</div>";
?>
<table id="caItemList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
	<thead>
		<tr>
			<th class="list-header-unsorted">
				<?= _t('Login name'); ?>
			</th>
			<th class="list-header-unsorted">
				<?= _t('Name'); ?>
			</th>
			<th class="list-header-unsorted">
				<?= _t('Email'); ?>
			</th>
			<th class="list-header-unsorted">
				<?= _t('Active?'); ?>
			</th>
			<th class="list-header-unsorted">
				<?= _t('Last login'); ?>
			</th>
			<th class="{sorter: false} list-header-nosort listtableEditDelete"></th>
		</tr>
	</thead>
	<tbody>
<?php
$o_tep = new TimeExpressionParser();
foreach($user_list as $user) {
if ($user['last_login'] > 0) {
	$o_tep->setUnixTimestamps($user['last_login'], $user['last_login']);
}
?>
	<tr>
		<td>
			<?= $user['user_name']; ?>
		</td>
		<td>
			<?= $user['lname'].', '.$user['fname']; ?>
		</td>
		<td>
			<?= $user['email']; ?>
		</td>
		<td>
			<?= $user['active'] ? _t('Yes') : _t('No'); ?>
		</td>
		<td>
			<?= ($user['last_login'] > 0) ? "<span style='display:none;'>".$user['last_login']."</span>".$o_tep->getText() : '-'; ?>
		</td>
		<td class="listtableEditDelete">
			<?= caNavButton($this->request, __CA_NAV_ICON_EDIT__, _t("Edit"), '', 'administrate/access', 'Users', 'Edit', array('user_id' => $user['user_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
			<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'administrate/access', 'Users', 'Delete', array('user_id' => $user['user_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
		</td>
	</tr>
<?php
TooltipManager::add('.deleteIcon', _t("Delete"));
TooltipManager::add('.editIcon', _t("Edit"));
TooltipManager::add('#showTools', _t("Tools"));
}
?>
	</tbody>
</table>
<?php
	if(!$this->request->isAjax()) {
?>
</div>
	<div class="editorBottomPadding"><!-- empty --></div>
<?php
	}
	