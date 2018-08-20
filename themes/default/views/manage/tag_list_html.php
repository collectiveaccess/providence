<?php
/* ----------------------------------------------------------------------
 * manage/tag_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2018 Whirl-i-Gig
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
 	$va_tags_list = $this->getVar('tags_list');
	if(sizeof($va_tags_list) > 0){
?>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caTagsList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
<?php 
				print caFormControlBox(
					'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caTagsList\').caFilterTable(this.value); return false;" size="20"/></div>', 
					'', 
					''
				); 
?>
			<form id="tagListForm" method="post">
			
			<table id="caTagsList" class="listtable">
				<div style="text-align:right;">
					<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#tagListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Tags', 'Approve'); ?>").submit();' class='form-button'><span class='form-button approveDelete'><?php print caNavIcon(__CA_NAV_ICON_APPROVE__, 1); ?><span class='formtext'><?php print _t("Approve"); ?></span></span></a>
					<a href='#' onclick='jQuery("#tagListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'tags', 'Delete'); ?>").submit();' class='form-button'><span class='form-button approveDelete'><?php print caNavIcon(__CA_NAV_ICON_DELETE__, 1); ?><span class='formtext'><?php print _t("Delete"); ?></span></span></a>
				</div>
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Item'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Tag'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Author'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Date'); ?>
						</th>
						<th class="{sorter: false} list-header-nosort"><?php print _t('Select'); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			foreach($va_tags_list as $va_tag) {
?>
					<tr>
						<td>
							<?php print caEditorLink($this->request, $va_tag['item_tagged'], '', $va_tag['table_num'], $va_tag['row_id']); ?>
						</td>
						<td>
							<?php print caNavLink($this->request, $tag = $va_tag['tag'], '', 'find', 'QuickSearch', 'Index', ['search' => "ca_item_tags.tag:\"{$tag}\""]); ?>
						</td>
						<td>
<?php 
							if($va_tag['user_id']){
								print $va_tag['fname']." ".$va_tag['lname']." (".$va_tag['user_email'].")";
							}else{
								print $va_tag['name']." (".$va_tag['user_email'].")";
							}
?>
						</td>
						<td>
							<?php print $va_tag['created_on']; ?>
						</td>
						<td>
							<input type="checkbox" name="tag_relation_id[]" value="<?php print $va_tag['relation_id']; ?>">
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
