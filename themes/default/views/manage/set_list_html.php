<?php
/* ----------------------------------------------------------------------
 * manage/set_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
$t_set 				= $this->getVar('t_set');
$va_set_list 		= $this->getVar('set_list');
$vn_type_id 		= $this->getVar('list_set_type_id');
$vs_current_sort 	= $this->getVar('current_sort');
$vs_current_sort_direction 	= $this->getVar('current_sort_direction');
$vs_type_name_singular		= $this->getVar('type_name_singular');

$user_id = $this->request->getUserID();
	
if (!$this->request->isAjax()) {
	$vs_set_type_menu = '<div class="sf-small-menu form-header-button rounded" style="padding: 6px;">'.
							'<div class="caNavHeaderIcon">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#typeList\').val(), jQuery(\'#tableList\').val());">'.caNavIcon(__CA_NAV_ICON_ADD__, 2).'</a>'.
							'</div>'.
						'<form action="#">'._t('Create new').' ';
	if(!$vn_type_id){
		$t_list = new ca_lists();
		$vs_set_type_menu .= $t_list->getListAsHTMLFormElement('set_types', 'set_type', ['id' => 'typeList']).' ';
	}else{
		$vs_set_type_menu .= " <b>".mb_strtolower($vs_type_name_singular)."</b><input type='hidden' id='typeList' name='set_type' value='".$vn_type_id."'> ";
	}
	$vs_set_type_menu .= _t('containing').' '.caHTMLSelect('table_num', $this->getVar('table_list'), ['id' => 'tableList'], ['value' => Datamodel::getTableNum($t_set->getAppConfig()->get('set_editor_default_table'))]).'</form>'.'</div>';

?>
<script language="JavaScript" type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#setSearch').autocomplete(
			{
				minLength: 3, delay: 800, html: true,
				source: '<?= caNavUrl($this->request, 'lookup', 'Set', 'Get', array('noInline' => 1, 'type' => $vn_type_id)); ?>',
				select: function(event, ui) {
					if (parseInt(ui.item.id) > 0) {
						jQuery('#setSearch').val('');
						document.location = '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'Edit'); ?>/set_id/' + ui.item.id;
					}
				}
			}
		).click(function() { this.select(); });
	});
	
	function _navigateToNewForm(type_id, table_num) {
		document.location = '<?= caNavUrl($this->request, 'manage/sets', 'SetEditor', 'Edit', array('set_id' => 0)); ?>/type_id/' + type_id + '/table_num/' + table_num;
	}
</script>
<div class="sectionBox">
	<?php 
		$vs_type_id_form_element = '';
		if ($vn_type_id = intval($this->getVar('list_set_type_id'))) {
			$vs_type_id_form_element = '<input type="hidden" name="type_id" value="'.$vn_type_id.'"/>';
		}
		print caFormTag($this->request, 'ListSets', 'SetSearchForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true));
		print caFormControlBox(
			'<div class="simple-search-box">'._t('Search').': <input type="text" id="setSearch" name="search" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'" size="20"/></div>'.$vs_type_id_form_element,
			'',
			$vs_set_type_menu
		); 
?>
		</form>
<?php
}
?>
	<div id="resultBox">
<?php
	print $this->render('sets/paging_controls_html.php');
?>
	<?= caFormTag($this->request, 'Algebra', 'algebraSetForm', null, 'post', 'multipart/form-data', '_top', ['disableUnsavedChangesWarning' => true, 'noCSRFToken' => true, 'submitOnReturn' => false]); ?>
		<div id="algebraSetControls">
			<?= _t("%1 <span id='algebraCreateText'>new set %2 from the %3 of </span>%4 selected sets", caHTMLSelect("algebra_set_mode", [_t("Create") => "CREATE", _t("Delete") => "DELETE"], ['id' => 'algebraModeSelect']), "<input type='text' size='10' name='algebra_set_name' id='algebraSetName'/>", caHTMLSelect("algebra_set_operation", [_t("combination") => "UNION", _t("intersection") => "INTERSECTION", _t("difference") => "DIFFERENCE"]), '<span id="selectedSetCount"></span>');?>  
			
			<?= caFormSubmitButton($this->request, __CA_NAV_ICON_ADD__, '', 'algebraSetForm', ['size' => '18px', 'id' => 'algebraAddButton']); ?>
			<?= caFormSubmitButton($this->request, __CA_NAV_ICON_DELETE__, '', 'algebraSetForm', ['size' => '18px', 'id' => 'algebraDeleteButton']); ?>
		</div>
	
	<table id="caItemList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-nosort"> </th>
				<th class="<?= (($vs_current_sort == "name") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
					<?= caNavLink($this->request, _t('Name'), '', 'manage', 'Set', 'ListSets', array('sort' => 'name', 'direction' => ((($vs_current_sort == "name") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
				</th>
				<th class="<?= (($vs_current_sort == "set_content_type") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
					<?= caNavLink($this->request, _t('Content'), '', 'manage', 'Set', 'ListSets', array('sort' => 'set_content_type', 'direction' => ((($vs_current_sort == "set_content_type") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
				</th>
<?php
				if(!$vn_type_id){
?>
					<th class="<?= (($vs_current_sort == "set_type") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
						<?= caNavLink($this->request, _t('Type'), '', 'manage', 'Set', 'ListSets', array('sort' => 'set_type', 'direction' => ((($vs_current_sort == "set_type") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
					</th>
<?php
				}
?>
				<th class="<?= (($vs_current_sort == "item_count") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
					<?= caNavLink($this->request, _t('# Items'), '', 'manage', 'Set', 'ListSets', array('sort' => 'item_count', 'direction' => ((($vs_current_sort == "item_count") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
				</th>
				<th class="<?= (($vs_current_sort == "lname") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
					<?= caNavLink($this->request, _t('Owner'), '', 'manage', 'Set', 'ListSets', array('sort' => 'lname', 'direction' => ((($vs_current_sort == "lname") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
				</th>
				<th class="<?= (($vs_current_sort == "access") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
					<?= caNavLink($this->request, _t('Access'), '', 'manage', 'Set', 'ListSets', array('sort' => 'access', 'direction' => ((($vs_current_sort == "access") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
				</th>
				<th class="<?= (($vs_current_sort == "created") ? "list-header-sorted-".$vs_current_sort_direction : ""); ?> list-header-nolink">
					<?= caNavLink($this->request, _t('Created'), '', 'manage', 'Set', 'ListSets', array('sort' => 'created', 'direction' => ((($vs_current_sort == "created") && ($vs_current_sort_direction != "desc")) ? "desc" : "asc"))); ?>
				</th>
				<th class="{sorter: false} list-header-nosort listtableEdit"> </th>
			</tr>
		</thead>
		<tbody id="setListBody">
<?php
	if (sizeof($va_set_list)) {
		foreach($va_set_list as $va_set) {
?>
			<tr>
				<td>
					<input type="checkbox" class="algebraSetSelector set-table-<?= $va_set["table_num"]; ?>" name="algebra_set_id[]" data-table_num="<?= $va_set["table_num"]; ?>" value="<?= $va_set["set_id"]; ?>">
				</td>
				<td>
					<div class="caItemListName"><?= $va_set['name'].($va_set['set_code'] ? 
					((mb_strlen($va_set['set_code']) > 20) ? "<br/>(<span class='abbreviatedPath' title='{$va_set['set_code']}'>".
					caTruncateStringWithEllipsis($va_set['set_code'], 20, 'start')."</span>)" : '<br/>('.$va_set['set_code'].')') : ""); ?></div>
				</td>
				<td>
					<div><?= $va_set['set_content_type']; ?></div>
				</td>
<?php
				if(!$vn_type_id){
?>
				<td>
					<div><?= $va_set['set_type']; ?></div>
				</td>
<?php
				}
?>
				<td align="center">
					<div>
<?php 	
					if (($va_set['item_count'] > 0) && ($this->request->user->canDoAction('can_batch_edit_'.Datamodel::getTableName($va_set['table_num'])))) {
						print caNavButton($this->request, __CA_NAV_ICON_BATCH_EDIT__, _t('Batch edit'), 'batchIcon', 'batch', 'Editor', 'Edit', array('id' => 'ca_sets:'.$va_set['set_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true));
						print $va_set['item_count']; 
					} else {
						print $va_set['item_count']; 
					}
?>
					</div>
				</td>
				<td>
					<div class="caItemListOwner"><?= $va_set['fname'].' '.$va_set['lname'].($va_set['email'] ? "<br/>(<a href='mailto:".$va_set['email']."'>".$va_set['email']."</a>)" : ""); ?></div>
				</td>
				<td>
					<div><?= $t_set->getChoiceListValue('access', $va_set['access']); ?></div>
				</td>
				<td>
					<div><?= caGetLocalizedDate($va_set['created'], ['timeOmit' => true, 'dateFormat' => 'delimited']); ?></div>
				</td>
				<td class="listtableEditDelete">
					<?= caNavButton($this->request, __CA_NAV_ICON_EDIT__, _t("Edit"), '', 'manage/sets', 'SetEditor', 'Edit', array('set_id' => $va_set['set_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?php ($t_set->haveAccessToSet($user_id, __CA_SET_EDIT_ACCESS__, $va_set['set_id'])) ? print caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage/sets', 'SetEditor', 'Delete', array('set_id' => $va_set['set_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)) : ''; ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='<?= $vn_type_id ? 9 : 10; ?>'>
				<div align="center">
					<?= _t('No sets have been created'); ?>
				</div>
			</td>
		</tr>
<?php
	}
	TooltipManager::add('.deleteIcon', _t("Delete"));
	TooltipManager::add('.editIcon', _t("Edit"));
	TooltipManager::add('.batchIcon', _t("Batch"));
?>
		</tbody>
	</table>
	
	</form>
</div>
<?php
if (!$this->request->isAjax()) {
?>
</div>
	<div class="editorBottomPadding"><!-- empty --></div>
<?php
}
?>
<script type="text/javascript">
	var caAlgebraSetTableNum = null;
	jQuery(document).ready(function() {
		jQuery('#algebraSetControls, #algebraDeleteButton').hide();
		jQuery('#selectedSetCount').html(0);
		
		jQuery('#setListBody').on('click', '.algebraSetSelector', function(e) {
			var c = jQuery('.algebraSetSelector:checked').length;
			
			if (c > 1) {
				jQuery('#algebraSetControls').show(100);
				jQuery('#selectedSetCount').html(c);
			} else {
				if (c == 1) {
					caAlgebraSetTableNum = jQuery('.algebraSetSelector:checked').data('table_num');
					jQuery(".algebraSetSelector").hide();
					jQuery(".set-table-" + caAlgebraSetTableNum).show();
				} else {
					jQuery(".algebraSetSelector").show();
				}
				jQuery('#algebraSetControls').hide(100);
			}
		});
		
		jQuery('#algebraModeSelect').on('change', function(e) {
			if (jQuery(this).val() == 'DELETE') {
				jQuery('#algebraCreateText, #algebraAddButton').hide();
				jQuery('#algebraDeleteButton').show();
			} else {
				jQuery('#algebraDeleteButton').hide();
				jQuery('#algebraCreateText, #algebraAddButton').show();
			}
		});
	});
</script>
