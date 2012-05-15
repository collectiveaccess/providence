<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/ui_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
$va_mapping_list = $this->getVar('mapping_list');

$vs_type_menu = '<div class="sf-small-menu form-header-button rounded">'.
							'<div style="float:right; margin: 3px;">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#tableList\').val(), jQuery(\'#targetList\').val(), jQuery(\'#directionList\').val());">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__).'</a>'.
							'</div>'.
						'<form action="#">'._t('New %1 %2 mapping for %3', caHTMLSelect('format', $this->getVar('format_list'), array('id' => 'targetList')), caHTMLSelect('direction', $this->getVar('direction_list'), array('id' => 'directionList')), caHTMLSelect('table_num', $this->getVar('table_list'), array('id' => 'tableList'))).'</form>'.
						'</div>';
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caMappingList').caFormatListTable();
	});
	
	function _navigateToNewForm(table_num, target, direction) {
		document.location = '<?php print caNavUrl($this->request, 'administrate/setup/bundle_mapping_editor', 'BundleMappingEditor', 'Edit', array('mapping_id' => 0)); ?>' + '/table_num/' + table_num + '/target/' + target + '/direction/' + direction;
	}
/* ]]> */
</script>
<div class="sectionBox">
	<?php
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caMappingList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			$vs_type_menu
		);
	?>

	<table id="caMappingList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
		<tr>
			<th>
				<?php _p('Name'); ?>
			</th>
			<th>
				<?php _p('Type'); ?>
			</th>
			<th>
				<?php _p('Format'); ?>
			</th>
			<th class="{sorter: false} list-header-nosort">&nbsp;</th>
		</tr>
		</thead>
		<tbody>
<?php
	if(sizeof($va_mapping_list)) {
		foreach($va_mapping_list as $va_mapping) {
?>
			<tr>
				<td>
					<?php print $va_mapping['name']; ?>
				</td>
				<td>
					<?php print $va_mapping['type'].' ('.$va_mapping['directionForDisplay'].')'; ?>
				</td>
				<td>
					<?php print $va_mapping['target']; ?>
				</td>
				<td>
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'administrate/setup/bundle_mapping_editor', 'BundleMappingEditor', 'Edit', array('mapping_id' => $va_mapping['mapping_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan="4">
				<div align="center">
					<?php print _t('No mappings have been configured'); ?>
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