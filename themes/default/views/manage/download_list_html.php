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
$download_list 		= $this->getVar('download_list');	
$downloaded_count 	= array_reduce($download_list, function($c, $v) { 
	if($v['status'] == 'DOWNLOADED') { $c++; }
	return $c;
 }, 0); 	
 			
?>
<script type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function(){
		jQuery('#caFormList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
		<div style="float: left;  height: 40px;">
			<?= caJSButton($this->request, __CA_NAV_ICON_ROTATE__, _t("Refresh"), 'refreshDownloadList', ['class' => 'form-button', 'style' => ''], []); ?>
		</div>
<?php
	if(is_array($download_list) && sizeof($download_list)) {
?>
		<div style="text-align:right; height: 40px;">
<?php
		if($downloaded_count > 0) {
?>
			<?= caJSButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete downloaded"), 'deleteDownloaded', ['class' => 'form-button'], []); ?>
<?php
		}
?>
			<?= caJSButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete selected"), 'deleteSelected', ['class' => 'form-button', 'style' => 'display: none'], []); ?>
		</div>
<?php
	}
	
	if($downloaded_count > 0) {
?>
		<div><?= _t('Updated at %1', date('H:i')); ?></div>
<?php
	}
?>
		<table id="caFormList" class="listtable">
			<thead>
				<tr>
					<th class="{sorter: false} list-header-nosort listtableEdit"><?= caHTMLCheckboxInput('selectall', ['value' => 1, 'class' => 'downloadSelectAllInput']); ?> </th>
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
						<?= caHTMLCheckboxInput('delete_id', ['value' => $download['download_id'], 'class' => 'downloadSelectedInput']); ?>
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
						<span id='downloadStatus<?= $download['download_id']; ?>'><?= $download['status']; ?></span>
					</td>
					<td class="listtableEditDelete">
<?php
		if(in_array($download['status'], ['COMPLETE', 'DOWNLOADED'], true)) {
?>						
					<?= caJSButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t("Download"), 'download'.$download['download_id'], ['class' => 'downloadLink', 'data-download_id' => $download['download_id']], []); ?>
<?php
		}
	?>			
					</td>
				</tr>
<?php
				if(isset($md['error'])) {
					TooltipManager::add('#downloadStatus'.$download['download_id'], $md['error']);	
				}
		
			}
			
			TooltipManager::add('.downloadLink', _t("Download"));
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
</div>
<div class="editorBottomPadding"><!-- empty --></div>

<?php
	if (!$this->request->isAjax()) {
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#mainContent').on('click', '.downloadSelectedInput', caUpdateDeleteSelectedButton);

		setInterval(function() {
			jQuery('#mainContent').load('<?= caNavUrl($this->request, '*', '*', 'List', []);?>');
		}, 30000);
		
		jQuery('#mainContent').on('click', '.downloadLink', function(e) {
			window.location = '<?= caNavUrl($this->request, '*', '*', 'Download');?>/download_id/' + jQuery(this).data('download_id');
			setTimeout(function() {
				jQuery('#mainContent').load('<?= caNavUrl($this->request, '*', '*', 'List', []);?>');
			}, 750);
		});
		
		jQuery('#mainContent').on('click', '#deleteSelected', caDeleteSelected);
		jQuery('#mainContent').on('click', '#deleteDownloaded', caDeleteDownloaded);
		jQuery('#mainContent').on('click', '#refreshDownloadList', caRefreshDownloadList);
		jQuery('#mainContent').on('click', '.downloadSelectAllInput', caToggleSelected);
	});
	function caUpdateDeleteSelectedButton() {
		if(jQuery('input.downloadSelectedInput:checked').length > 0) {
			jQuery('#deleteSelected').show();
		} else {
			jQuery('#deleteSelected').hide();
		}
	}
	
	function caDeleteDownloaded() {
		jQuery('#mainContent').load('<?= caNavUrl($this->request, '*', '*', 'Delete', ['downloadedOnly' => 1]);?>', {});
	}
	function caToggleSelected() {
		jQuery('.downloadSelectedInput').each(function(k, v) {
			if(jQuery(v).prop('checked')) {
				jQuery(v).prop('checked', false);
			} else {
				jQuery(v).prop('checked', true);
			}
		});
		caUpdateDeleteSelectedButton();
	}
	function caDeleteSelected() {
		let ids = [];
		jQuery('.downloadSelectedInput:checked').each(function(k, v) {
			ids.push(jQuery(v).val());
		});
		
		jQuery('#mainContent').load('<?= caNavUrl($this->request, '*', '*', 'Delete');?>', {'delete_id' : ids });
	}
	function caRefreshDownloadList() {
		jQuery('#mainContent').load('<?= caNavUrl($this->request, '*', '*', 'List');?>', {});
	}
</script>
<?php
	} else {	
		print TooltipManager::getLoadHTML();
	}
