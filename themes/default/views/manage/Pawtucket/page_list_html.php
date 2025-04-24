<?php
/* ----------------------------------------------------------------------
 * manage/Pawtucket/page_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2024 Whirl-i-Gig
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
$t_page				= $this->getVar('t_page');
$page_list 		= $this->getVar('page_list');

$page_select     = ca_site_templates::getTemplateListAsHTMLSelect(['id' => 'templateList']);

$site_page_template_menu = $page_select ? '<div class="sf-small-menu form-header-button rounded" style="padding: 6px;">'.
					'<div class="caNavHeaderIcon">'.
						'<a href="#" onclick="_navigateToNewForm(jQuery(\'#templateList\').val(), jQuery(\'#tableList\').val());">'.caNavIcon(__CA_NAV_ICON_ADD__, 2).'</a>'.
					'</div>'.
				'<form action="#">'._t('New %1 page', $page_select).'</form>'.
				'</div>' : '';
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#caItemList').caFormatListTable();
	});
	
	function _navigateToNewForm(template_id) {
		document.location = '<?= caNavUrl($this->request, 'manage/site_pages', 'SitePageEditor', 'Edit', array('page_id' => 0, 'template_id' => '')); ?>' + template_id;
	}
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caItemList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			$site_page_template_menu
		); 
	?>
	
	<table id="caItemList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Rank'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Title'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('URL path'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Template'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Locale'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Access'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Views'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort listtableEditDelete"> </th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($page_list)) {
		foreach($page_list as $page) {
?>
			<tr>
				<td>
					<div><?= $page['rank']; ?></div>
				</td>
				<td>
					<div class="caPageListName"><?= $page['title']; ?></div>
				</td>
				<td>
					<div><?= ($page['path'] === 'PROVIDENCE_HELP_MENU') ? _t('<em>Providence help menu</em>') : $page['path']; ?></div>
				</td>
				<td>
					<div><?= $page['template_title']; ?></div>
				</td>
				<td>
					<div><?= $page['locale']; ?></div>
				</td>
				<td>
					<div><?= $t_page->getChoiceListValue('access', $page['access']); ?></div>
				</td>
				<td>
					<div><?= $page['view_count']; ?></div>
				</td>
				<td class="listtableEditDelete">
					<?= caNavButton($this->request, __CA_NAV_ICON_EDIT__, _t("Edit"), '', 'manage/site_pages', 'SitePageEditor', 'Edit', array('page_id' => $page['page_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage/site_pages', 'SitePageEditor', 'Delete', array('page_id' => $page['page_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='8'>
				<div align="center">
					<?= $page_select ? _t('No pages have been created') : _t('No page templates have been defined'); ?>
				</div>
			</td>
		</tr>
<?php
	}
	TooltipManager::add('.deleteIcon', _t("Delete"));
	TooltipManager::add('.editIcon', _t("Edit"));
?>
		</tbody>
	</table>
</div>

<div class="editorBottomPadding"><!-- empty --></div>
