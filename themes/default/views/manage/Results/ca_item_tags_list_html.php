<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/Results/ca_item_tags_list_html.php :
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
	<div id="tagsResults">
		<form id="tagListForm"><input type="hidden" name="mode" value="search">
		
		<div style="text-align:right;">
			<?php print _t('Batch actions'); ?>: </a>
			<a href='#' onclick='jQuery("#tagListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'Tags', 'DeleteTags'); ?>").submit();' class='form-button'><span class='form-button'><?php print _t("Delete"); ?></span></a>
		</div>
		<table id="caTagsList" class="listtable" border="0" cellpadding="0" cellspacing="1" style="margin-top:3px;">
			<thead>
				<tr>
					<th class="list-header-unsorted">
						<?php print _t('Tag'); ?>
					</th>
					<th class="list-header-unsorted">
						<?php print _t('Number of tagged items'); ?>
					</th>
					<th class="{sorter: false} list-header-nosort"><?php print _t('Select'); ?></th>
				</tr>
			</thead>
			<tbody>

<?php
		$i = 0;
		$vn_item_count = 0;
		//$o_tep = new TimeExpressionParser();
		//$o_datamodel = Datamodel::load();
		
		while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
?>
				<tr>
					<td>
						<?php print $vo_result->get('ca_item_tags.tag'); ?>
					</td>
					<td>
<?php
		$o_db = new Db();
		$qr_c = $o_db->query("
			SELECT count(*) c
			FROM ca_items_x_tags
			WHERE tag_id = ?
		", $vo_result->get('ca_item_tags.tag_id'));
		
		if ($qr_c->nextRow()) {
			print (int)$qr_c->get('c');
		}
						
?>
					</td>
					<td>
						<input type="checkbox" name="tag_id[]" value="<?php print $vo_result->get('ca_item_tags.tag_id'); ?>">
					</td>
				</tr>
<?php
			$i++;
			$vn_item_count++;
		}
?>
			</tbody>
		</table></form>
	</div><!--end tagsResults -->