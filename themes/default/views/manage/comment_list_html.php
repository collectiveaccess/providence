<?php
/* ----------------------------------------------------------------------
 * manage/comment_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
	$va_comments_list = $this->getVar('comments_list');
	if(sizeof($va_comments_list) > 0){
?>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caCommentsList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
<?php 
				print caFormControlBox(
					'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caCommentsList\').caFilterTable(this.value); return false;" size="20"/></div>', 
					'', 
					''
				); 
?>
			<form id="commentListForm"><input type="hidden" name="mode" value="list">
			
			<div style="text-align:right;">
				<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Comments', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'>Approve</span></a>
				<a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Comments', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
			</div>
			<table id="caCommentsList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Author'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Comment'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Media'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Rating'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Date'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Commented On'); ?>
						</th>
						<th class="{sorter: false} list-header-nosort"><?php print _t('Select'); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			foreach($va_comments_list as $va_comment) {
?>
					<tr>
						<td>
<?php 
							if($va_comment['user_id']){
								print $va_comment['fname']." ".$va_comment['lname']."<br/>".$va_comment['user_email'];
							}else{
								print $va_comment['name']."<br/>".$va_comment['user_email'];
							}
?>
						</td>
						<td>
							<?php print $va_comment['comment']; ?>
						</td>	
						<td>
<?php
							if(is_array($va_comment['media1']) && (sizeof($va_comment['media1']) > 0)){
								print "<span style='white-space: nowrap;'>".$va_comment['media1']['thumbnail']['TAG'];
								print caNavButton($this->request, __CA_NAV_BUTTON_DOWNLOAD__, 'Download', 'manage', 'Comments', 'DownloadMedia', array('version' => 'original', 'comment_id' => $va_comment['comment_id'], 'mode' => 'list', 'download' => 1), array(), array('no_background' => true, 'dont_show_content' => true));
								print "</span>";
							}
?>
						</td>
						<td>
							<?php print ($va_comment['rating']) ? $va_comment['rating'] : "-"; ?>
						</td>
						<td>
							<?php print $va_comment['created_on']; ?>
						</td>
						<td>
							<?php print $va_comment['commented_on']; ?>
						</td>
						<td>
							<input type="checkbox" name="comment_id[]" value="<?php print $va_comment['comment_id']; ?>">
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