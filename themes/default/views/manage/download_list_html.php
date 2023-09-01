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
	<form id="downloadListForm">
		<div style="text-align:right;">
			<a href='#' onclick='jQuery("#downloadListForm").attr("action", "<?= caNavUrl($this->request, '*', '*', 'delete', ['downloadedOnly' => 1]); ?>").submit();' class='form-button' id='deleteDownloaded'><span class='form-button approveDelete'><?= caNavIcon(__CA_NAV_ICON_DELETE__, 1); ?><span class='formtext'><?= _t('Delete downloaded'); ?></span></span></a>
			<a href='#' onclick='jQuery("#downloadListForm").attr("action", "<?= caNavUrl($this->request, '*', '*', 'delete'); ?>").submit();' class='form-button' id='deleteSelected' style='display: none;'><span class='form-button approveDelete'><?= caNavIcon(__CA_NAV_ICON_DELETE__, 1); ?><span class='formtext'><?= _t('Delete selected'); ?></span></span></a>
		</div>
		<table id="caFormList" class="listtable">
			<thead>
				<tr>
					<th class="{sorter: false} list-header-nosort listtableEdit"> </th>
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
					<th class="{sorter: false} list-header-nosort listtableEdit"><?= _t('Download'); ?></th>
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
						<?= caHTMLCheckboxInput('delete_id[]', ['value' => $download['download_id'], 'class' => 'downloadSelectedInput']); ?>
					</td>
					<td>
						<?= $md['searchExpressionForDisplay']." (".$md['findType'].")"; ?>
					</td>
					<td>
						<?= caGetLocalizedDate($download['created_on'], ['dateFormat' => 'delimited']); ?>
					</td>
					<td>
						<?= $download['generated_on'] ? caGetLocalizedDate($download['generated_on'], ['dateFormat' => 'delimited']) : '-'; ?>
					</td>
					<td>
						<?= $download['downloaded_on'] ? caGetLocalizedDate($download['downloaded_on'], ['dateFormat' => 'delimited']) : '-'; ?>
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
		if(in_array($download['status'], ['COMPLETE', 'DOWNLOADED'], true)) {
?>
						<?= caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t("Download"), 'downloadIcon', 'manage', 'Downloads', 'Download', ['download_id' => $download['download_id']], [], ['icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true]); ?>
<?php
		}
	?>			
					</td>
				</tr>
<?php
		
			}
			
			TooltipManager::add('.downloadIcon', _t("Download"));
		} else {
?>
			<tr>
				<td colspan='9'>
					<div align="center">
						<?= _t('No downloads are available'); ?>
					</div>
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

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.downloadSelectedInput').on('click', caUpdateDeleteSelectedButton);
	});
	function caUpdateDeleteSelectedButton() {
		if(jQuery('input.downloadSelectedInput:checked').length > 0) {
			jQuery('#deleteSelected').show();
		} else {
			jQuery('#deleteSelected').hide();
		}
	}
</script>
