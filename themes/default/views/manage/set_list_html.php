<?php
/* ----------------------------------------------------------------------
 * manage/set_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 
 	$t_set 				= $this->getVar('t_set');
	$va_set_list 		= $this->getVar('set_list');

	$t_list = new ca_lists();
	$vs_set_type_menu = '<div class="sf-small-menu form-header-button rounded" style="padding: 6px;">'.
							'<div style="float:right; margin: 3px;">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#typeList\').val(), jQuery(\'#tableList\').val());">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD_LARGE__).'</a>'.
							'</div>'.
						'<form action="#">'._t('Create New').' '.$t_list->getListAsHTMLFormElement('set_types', 'set_type', array('id' => 'typeList')).' '._t('containing').' '.caHTMLSelect('table_num', $this->getVar('table_list'), array('id' => 'tableList')).'</form>'.
						'</div>';
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function(){
		jQuery('#caSetList').caFormatListTable();
	});
	
	function _navigateToNewForm(type_id, table_num) {
		document.location = '<?php print caNavUrl($this->request, 'manage/sets', 'SetEditor', 'Edit', array('set_id' => 0, 'type_id' => '')); ?>' + type_id + '/table_num/' + table_num;
	}
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caSetList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			$vs_set_type_menu
		); 
	?>
	
	<table id="caSetList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Set name'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Content type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('# Items'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Owner'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Access'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Status'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_set_list)) {
		foreach($va_set_list as $va_set) {
?>
			<tr>
				<td>
					<?php print $va_set['name'].($va_set['set_code'] ? "<br/>(".$va_set['set_code'].")" : ""); ?>
				</td>
				<td>
					<?php print $va_set['set_content_type']; ?>
				</td>
				<td>
					<?php print $va_set['set_type']; ?>
				</td>
				<td align="center">
<?php 
					
					if (($va_set['item_count'] > 0) && ($this->request->user->canDoAction('can_batch_edit_'.$t_set->getAppDatamodel()->getTableName($va_set['table_num'])))) {
						print $va_set['item_count']; 
						print caNavButton($this->request, __CA_NAV_BUTTON_BATCH_EDIT__, _t('Batch edit'), 'batch', 'Editor', 'Edit', array('set_id' => $va_set['set_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true));
					} else {
						print $va_set['item_count']; 
					}
?>
				</td>
				<td>
					<?php print $va_set['fname'].' '.$va_set['lname'].($va_set['email'] ? "<br/>(<a href='mailto:".$va_set['email']."'>".$va_set['email']."</a>)" : ""); ?>
				</td>
				<td>
					<?php print $t_set->getChoiceListValue('access', $va_set['access']); ?>
				</td>
				<td>
					<?php print $t_set->getChoiceListValue('status', $va_set['status']); ?>
				</td>
				<td width="50">
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'manage/sets', 'SetEditor', 'Edit', array('set_id' => $va_set['set_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?php ($va_set['can_delete'] == TRUE) ? print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'manage/sets', 'SetEditor', 'Delete', array('set_id' => $va_set['set_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)) : ''; ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='8'>
				<div align="center">
					<?php print _t('No sets have been created'); ?>
				</div>
			</td>
		</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>

	<div class="editorBottomPadding"><!-- empty --></div>