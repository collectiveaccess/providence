<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/Results/ca_item_comments_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 	$vo_result = $this->getVar('result');
	$vn_items_per_page = $this->getVar('current_items_per_page');
	
?>
	<div id="commentsResults">
		<form id="commentListForm"><input type="hidden" name="mode" value="search">
		
		<div style="text-align:right;">
			<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Comments', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'><?php print _t("Approve"); ?></span></a>
			<a href='#' onclick='jQuery("#commentListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Comments', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'><?php print _t("Delete"); ?></span></a>
		</div>
		<table id="caCommentsList" class="listtable" border="0" cellpadding="0" cellspacing="1" style="margin-top:3px;">
			<thead>
				<tr>
					<th class="list-header-nosort">
						<?php print _t('Author'); ?>
					</th>
					<th class="list-header-nosort">
						<?php print _t('Comment'); ?>
					</th>
					<th class="list-header-nosort">
						<?php print _t('Media'); ?>
					</th>
					<th class="list-header-nosort">
						<?php print _t('Rating'); ?>
					</th>
					<th class="list-header-nosort">
						<?php print _t('Date'); ?>
					</th>
					<th class="list-header-nosort">
						<?php print _t('Commented On'); ?>
					</th>
					<th class="list-header-nosort">
						<?php print _t('Status'); ?>
					</th>
					<th class="list-header-nosort"><?php print _t('Select'); ?></th>
				</tr>
			</thead>
			<tbody>

<?php
		$i = 0;
		$vn_item_count = 0;
		$o_tep = new TimeExpressionParser();
		$o_datamodel = Datamodel::load();
		
		while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
			if (!($t_table = $o_datamodel->getInstanceByTableNum($vo_result->get('ca_item_comments.table_num'), true))) {
				continue;
			}
?>
				<tr>
					<td>
<?php 
						if($vo_result->get('ca_item_comments.user_id')){
							print $vo_result->get('ca_users.fname')." ".$vo_result->get('ca_users.lname')."<br/>".$vo_result->get('ca_users.email');
						}else{
							print $vo_result->get('ca_item_comments.name')."<br/>".$vo_result->get('ca_item_comments.user_email');
						}
?>
					</td>
					<td>
						<?php print $vo_result->get('ca_item_comments.comment'); ?>
					</td>	
					<td>
<?php
						if($vo_result->getMediaTag('ca_item_comments.media1', "thumbnail")){
							print "<span style='white-space: nowrap;'>".$vo_result->getMediaTag("ca_item_comments.media1", "thumbnail");
							print caNavButton($this->request, __CA_NAV_BUTTON_DOWNLOAD__, 'Download', 'manage', 'Comments', 'DownloadMedia', array('version' => 'original', 'comment_id' => $vo_result->get('ca_item_comments.comment_id'), 'mode' => 'search', 'download' => 1), array(), array('no_background' => true, 'dont_show_content' => true));
							print "</span>";
						}
?>
					</td>
					<td>
						<?php print ($vn_tmp = $vo_result->get('ca_item_comments.rating')) ? $vn_tmp : "-"; ?>
					</td>
					<td>
<?php 
						$o_tep->setUnixTimestamps($vn_tmp = $vo_result->get('ca_item_comments.created_on'), $vn_tmp);
						print $o_tep->getText();
?>
					</td>
					<td>
<?php
						$vs_commented_on = "";
						if ($t_table->load($vo_result->get('ca_item_comments.row_id'))) {
							$vs_commented_on = $t_table->getLabelForDisplay(false);
							if ($vs_idno = $t_table->get('idno')) {
								$vs_commented_on .= ' ['.$vs_idno.']';
							}
						}

						print $vs_commented_on;
?>
					</td>
					<td>
						<?php print $vo_result->get('ca_item_comments.moderated_on') ? _t("Approved") : _t("Needs moderation"); ?>
					</td>
					<td>
						<input type="checkbox" name="comment_id[]" value="<?php print $vo_result->get('comment_id'); ?>">
					</td>
				</tr>
<?php
			$i++;
			$vn_item_count++;
		}
?>
			</tbody>
		</table></form>
	</div><!--end commentsResults -->