<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/ui_list_html.php :
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
$va_editor_ui_list = $this->getVar('editor_ui_list');

$vs_type_menu = '<div class="sf-small-menu form-header-button rounded">'.
							'<div style="float:right; margin: 3px;">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#tableList\').val());">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__).'</a>'.
							'</div>'.
						'<form action="#">'._t('New interface for ').' '.caHTMLSelect('editor_type', $this->getVar('table_list'), array('id' => 'tableList')).'</form>'.
						'</div>';
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caUIList').caFormatListTable();
	});
	
	function _navigateToNewForm(editor_type) {
		document.location = '<?php print caNavUrl($this->request, 'administrate/setup/interface_editor', 'InterfaceEditor', 'Edit', array('ui_id' => 0)); ?>' + '/editor_type/' + editor_type;
	}
/* ]]> */
</script>
<div class="sectionBox">
	<?php
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caUIList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			$vs_type_menu
		);
	?>

	<table id="caUIList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
		<tr>
			<th>
				<?php _p('Name'); ?>
			</th>
			<th>
				<?php _p('Type'); ?>
			</th>
			<th>
				<?php _p('Code'); ?>
			</th>
			<th>
				<?php _p('System?'); ?>
			</th>
			<th class="{sorter: false} list-header-nosort">&nbsp;</th>
		</tr>
		</thead>
		<tbody>
<?php
	foreach($va_editor_ui_list as $va_ui) {
?>
		<tr>
			<td>
				<?php print $va_ui['name']; ?>
			</td>
			<td>
				<?php print $va_ui['editor_type']; ?>
			</td>
			<td>
				<?php print $va_ui['editor_code']; ?>
			</td>
			<td>
				<?php print $va_ui['is_system_ui'] ? _t('Yes') : _t('No'); ?>
			</td>
			<td>
				<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'administrate/setup/interface_editor', 'InterfaceEditor', 'Edit', array('ui_id' => $va_ui['ui_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
			</td>
		</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>
<div class="editorBottomPadding"><!-- empty --></div>