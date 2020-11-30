<?php
/* ----------------------------------------------------------------------
 * manage/comment_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2019 Whirl-i-Gig
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
 	$t_comments = $this->getVar('t_comments');
	$result = $this->getVar('comments_list');
	if($result->numHits() > 0){
?>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caItemList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
<?php 
				print caFormControlBox(
					'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caItemList\').caFilterTable(this.value); return false;" size="20"/></div>', 
					'', 
					''
				); 
?>
			<form id="commentListForm"><input type="hidden" name="mode" value="list">
			
			<div style="text-align:right;">
				<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Comments', 'Approve'); ?>").submit();' class='form-button'><span class='form-button approveDelete'><?php print caNavIcon(__CA_NAV_ICON_APPROVE__, 1); ?><span class='formtext'>Approve</span></span></a>
				<a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Comments', 'Delete'); ?>").submit();' class='form-button'><span class='form-button approveDelete'><?php print caNavIcon(__CA_NAV_ICON_DELETE__, 1); ?><span class='formtext'>Delete</span></span></a>
			</div>
			<table id="caItemList" class="listtable">
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Item'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Comment'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Author'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Date'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Notes'); ?>
						</th>
						<th class="{sorter: false} list-header-nosort"><?php print _t('Select'); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			//foreach($va_comments_list as $va_comment) {
			
			$comment_data = ca_item_comments::getItemCommentDataForResult($result, ['itemsPerPage' => 100, 'request' => $this->request]);
        
			while($result->nextHit()) {
			    $d = ca_item_comments::getItemCommentDataForDisplay($result, $comment_data);
?>
					<tr>
						<td>
<?php
						print !$d['id'] ? $d['label'] : caEditorLink($this->request, $d['label'], '', $d['table_num'], $d['id'])." ({$d['idno']})<br/>Source: {$d['source']}";
?>
						</td>
						<td>
							<div class="caUserCommentsListComment">
								<?php print $d['comment']; ?>
							</div>
						</td>	
						<td>
							<div class="caUserCommentsListName">
<?php 
							print $d['name']." (".$d['email'].")";
?>
							</div>
						</td>
						<td>
							<?php print $d['created_on']; ?>
						</td>
						<td>							
<?php
                            if ($d['notes']) { print "{$d['notes']}<br/>\n"; }
							if(is_array($va_comment['media1']) && (sizeof($va_comment['media1']) > 0)){
								print "<span style='white-space: nowrap;'>".$va_comment['media1']['thumbnail']['TAG'];
								print caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t('Download'), '', 'manage', 'Comments', 'DownloadMedia', array('version' => 'original', 'comment_id' => $va_comment['comment_id'], 'mode' => 'list', 'download' => 1), array(), array('no_background' => true, 'dont_show_content' => true));
								print "</span>";
							}
?>
						</td>
						<td>
							<input type="checkbox" name="comment_id[]" value="<?php print $d['comment_id']; ?>">
						</td>
					</tr>
<?php
			}
?>
				</tbody>
			</table></form>
		</div><!-- end sectionBox -->
<?php
	}
?>
