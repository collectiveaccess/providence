<?php
/* ----------------------------------------------------------------------
 * manage/search_forms_list_html.php :
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
 	$t_form 		= $this->getVar('t_form');
	$va_form_list 	= $this->getVar('form_list');


	$t_list = new ca_lists();
	
	$vs_set_type_menu = '<div class="sf-small-menu form-header-button rounded">'.
							'<div style="float:right; margin: 3px;">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#tableList\').val());">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__).'</a>'.
							'</div>'.
						'<form action="#">'._t('New search form for ').' '.caHTMLSelect('table_num', $this->getVar('table_list'), array('id' => 'tableList')).'</form>'.
						'</div>';
						
	//$vs_set_type_menu = caNavHeaderButton($this->request, __CA_NAV_BUTTON_ADD__, _t('New form'), 'manage/search_forms', 'SearchFormEditor', 'Edit', array('form_id' => 0));
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function(){
		jQuery('#caFormList').caFormatListTable();
	});
	
	function _navigateToNewForm(table_num) {
		document.location = '<?php print caNavUrl($this->request, 'manage/search_forms', 'SearchFormEditor', 'Edit', array('form_id' => 0)); ?>' + '/table_num/' + table_num;
	}
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caFormList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			$vs_set_type_menu
		); 
	?>
	
	<table id="caFormList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Form name'); ?>
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
	if (sizeof($va_form_list)) {
		foreach($va_form_list as $va_form) {
?>
			<tr>
				<td>
					<?php print $va_form['name']; ?>
				</td>
				<td>
					<?php print $va_form['fname'].' '.$va_form['lname']; ?>
				</td>
				<td>
					<?php print $va_form['search_form_content_type']; ?>
				</td>
				<td>
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'manage/search_forms', 'SearchFormEditor', 'Edit', array('form_id' => $va_form['form_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'manage/search_forms', 'SearchFormEditor', 'Delete', array('form_id' => $va_form['form_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='4'>
				<div align="center">
					<?php print _t('No forms have been configured'); ?>
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