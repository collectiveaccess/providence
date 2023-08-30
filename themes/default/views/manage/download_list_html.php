<?php
/* ----------------------------------------------------------------------
 * manage/search_forms_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
$t_download 		= $this->getVar('t_download_');
$download_list 	= $this->getVar('download_list');					
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function(){
		jQuery('#caFormList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<table id="caFormList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('File'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Created'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Generated'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Downloaded'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Format'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Status'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort listtableEdit"> </th>
			</tr>
		</thead>
		<tbody>
<?php
	if (is_array($download_list) && sizeof($download_list)) {
		foreach($download_list as $download) {
		$md = caUnserializeForDatabase($download['metadata']);
?>
			<tr>
				<td>
					<?= $md['searchExpressionForDisplay']." (".$md['findType'].")"; ?>
				</td>
				<td>
					<?= caGetLocalizedDate($download['created_on']); ?>
				</td>
				<td>
					<?= $download['generated_on'] ? caGetLocalizedDate($download['generated_on']) : ''; ?>
				</td>
				<td>
					<?= $download['downloaded_on'] ? caGetLocalizedDate($download['downloaded_on']) : ''; ?>
				</td>
				<td>
					<?= $md['format']; ?>
				</td>
				<td>
					<?= $download['download_type']; ?>
				</td>
				<td>
					<?= $download['status']; ?>
				</td>
				<td class="listtableEditDelete">
<?php
	if(in_array($download['status'], ['COMPLETE', 'ERROR'], true)) {
?>
					<?= caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t("Download"), '', 'manage', 'Downloads', 'Download', ['download_id' => $download['download_id']], [], ['icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true]); ?>
					<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage', 'Downloads', 'Delete', ['download_id' => $download['download_id']], [], ['icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true]); ?>
<?php
	}
?>			
				</td>
			</tr>
<?php
		TooltipManager::add('.deleteIcon', _t("Delete"));
		TooltipManager::add('.downloadIcon', _t("Download"));
		
		}
	} else {
?>
		<tr>
			<td colspan='4'>
				<div align="center">
					<?= _t('No download are available'); ?>
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
