<?php
/* ----------------------------------------------------------------------
 * app/views/batch/metadataimport/importer_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

$va_importer_list = $this->getVar('importer_list');

?>
<script language="JavaScript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#caImporterList').caFormatListTable();
	});
</script>
<div class="sectionBox">
	<?php
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="jQuery(\'#caImporterList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'',
			caNavHeaderButton($this->request, __CA_NAV_BUTTON_ADD_LARGE__, _t("New"), 'batch', 'MetadataImport', 'Edit', array('importer_id' => 0))
		);
	?>

	<table id="caImporterList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
		<tr>
			<th>
				<?php _p('Name'); ?>
			</th>
			<th>
				<?php _p('Code'); ?>
			</th>
			<th>
				<?php _p('Type'); ?>
			</th>
			<th class="{sorter: false} list-header-nosort" style="width: 75px">&nbsp;</th>
		</tr>
		</thead>
		<tbody>
<?php
	foreach($va_importer_list as $va_importer) {
?>
		<tr>
			<td>
				<?php print $va_importer['label']; ?>
			</td>
			<td>
				<?php print $va_importer['importer_code']; ?>
			</td>
			<td>
				<?php print $va_importer['importer_type']; ?>
			</td>
			<td>
				<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'batch', 'MetadataImport', 'Edit', array('importer_id' => $va_importer['importer_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				<?php print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'batch', 'MetadataImport', 'Delete', array('importer_id' => $va_importer['importer_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				<?php print caNavButton($this->request, __CA_NAV_BUTTON_GO__, _t("Import data"), 'batch', 'MetadataImport', 'Run', array('importer_id' => $va_importer['importer_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
			</td>
		</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>
<div class="editorBottomPadding"><!-- empty --></div>