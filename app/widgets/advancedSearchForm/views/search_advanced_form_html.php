<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/search_advanced_form_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
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

$type_id_form_element = '';
if ($type_id = intval($this->getVar('type_id'))) {
	$type_id_form_element = '<input type="hidden" name="type_id" value="'.$type_id.'"/>';
}

$t_form = $this->getVar('t_form');
$form_id = $t_form->getPrimaryKey();
$controller_name = $this->getVar('controller_name');
$widget_id = $this->getVar('widget_id');


if (!$form_id) {
	//
	// no form defined
	//
?>
	<div>
		<?= _t("You must define a search form before you can use the advanced search.").'<br/>'.caNavLink($this->request, '<strong>'._t('Click here to create a new form.').'</strong>', '', 'manage', 'SearchForm', 'ListForms'); ?>
	</div>
<?php
} else {
	if(!$t_form->haveAccessToForm($this->request->getUserID(), __CA_SEARCH_FORM_READ_ACCESS__, $form_id)) {
		//
		// No access to form - shouldn't ever happen
		//
?>
		<div class="notification-error-box">
			<ul class='notification-error-box'>
				<li class='notification-error-box'><?= _t('You do not have access to this form'); ?></li>
			</ul>
		</div>
<?php
	} else {
		//
		// Generate form
		//
		
		print "<div class='dashboardWidgetHeading'>".caUcFirstUTF8Safe(Datamodel::getTableProperty($t_form->get('table_num'), 'NAME_PLURAL')).": ".$t_form->getLabelForDisplay()."</div>\n";
		
		$va_form_element_list = $this->getVar('form_elements');
		$va_flds = array();
		foreach($va_form_element_list as $i => $va_element) {
			$va_flds[] = "'".$va_element['name']."'";
		}
?>
<?= caFormTag($this->request, 'Index', "AdvancedSearchForm_{$widget_id}", "find/{$controller_name}", 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true)); ?>
<?php 
		print "<div style='float: right;'>".caFormSearchButton($this->request, __CA_NAV_ICON_SEARCH__, _t("Search"), "AdvancedSearchForm_{$widget_id}").'<br/>'.
			caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), "AdvancedSearchForm_{$widget_id}", array('onclick' => "caAdvancedSearchFormReset{$widget_id}()"))."</div>\n";
		print $this->render('search_form_table_html.php');
		print caHTMLHiddenInput('form_id', array('value' => $form_id));
?>
	<script type="text/javascript">
		function caAdvancedSearchFormReset<?= $widget_id; ?>() {
			jQuery('#AdvancedSearchForm_<?= $widget_id; ?> textarea').val('');
			jQuery('#AdvancedSearchForm_<?= $widget_id; ?> input[type=text]').val('');
			jQuery('#AdvancedSearchForm_<?= $widget_id; ?> input[type=hidden]').val('');
			jQuery('#AdvancedSearchForm_<?= $widget_id; ?> select').prop('selectedIndex', -1);
			jQuery('#AdvancedSearchForm_<?= $widget_id; ?> input[type=checkbox]').attr('checked', 0);
		}
	</script>
</form>
<?php
	}
}
