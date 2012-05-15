<?php
/* ----------------------------------------------------------------------
 * manage/displays_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 	$t_display 			= $this->getVar('t_display');
	$va_display_list 	= $this->getVar('display_list');


	$vs_type_menu = '<div class="sf-small-menu form-header-button rounded">'.
							'<div style="float:right; margin: 3px;">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#tableList\').val());">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__).'</a>'.
							'</div>'.
						'<form action="#">'._t('New display for ').' '.caHTMLSelect('table_num', $this->getVar('table_list'), array('id' => 'tableList')).'</form>'.
						'</div>';
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function(){
		jQuery('#caBundleList').caFormatListTable();
	});
	
	function _navigateToNewForm(table_num) {
		document.location = '<?php print caNavUrl($this->request, 'manage/bundle_displays', 'BundleDisplayEditor', 'Edit', array('display_id' => 0)); ?>' + '/table_num/' + table_num;
	}
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caBundleList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			($this->request->user->canDoAction('can_create_ca_bundle_displays')) ? $vs_type_menu : ''
		); 
	?>
	
	<table id="caBundleList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Display name'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Owner'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Content type'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_display_list)) {
		foreach($va_display_list as $va_display) {
?>
			<tr>
				<td>
					<?php print $va_display['name']; ?>
				</td>
				<td>
					<?php print $va_display['fname'].' '.$va_display['lname']; ?>
				</td>
				<td>
					<?php print $va_display['bundle_display_content_type']; ?>
				</td>
				<td>
<?php
	if ($this->request->user->canDoAction('can_edit_ca_bundle_displays')) {
?>
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'manage/bundle_displays', 'BundleDisplayEditor', 'Edit', array('display_id' => $va_display['display_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
<?php
	}
	
	if ($this->request->user->canDoAction('can_delete_ca_bundle_displays')) {
?>					
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'manage/bundle_displays', 'BundleDisplayEditor', 'Delete', array('display_id' => $va_display['display_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
<?php
	}
?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='4'>
				<div align="center">
					<?php print _t('No displays lists have been configured'); ?>
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