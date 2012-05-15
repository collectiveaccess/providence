<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/search_advanced_form_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 	
 	$vs_type_id_form_element = '';
	if ($vn_type_id = intval($this->getVar('type_id'))) {
		$vs_type_id_form_element = '<input type="hidden" name="type_id" value="'.$vn_type_id.'"/>';
	}
	
	$t_form = $this->getVar('t_form');
	$vn_form_id = $t_form->getPrimaryKey();
	$vs_controller_name = $this->getVar('controller_name');
	
	$o_dm = Datamodel::load();
	
	if (!$vn_form_id) {
		//
		// no form defined
		//
?>
		<div>
			<?php print _t("You must define a search form before you can use the advanced search.").'<br/>'.caNavLink($this->request, '<strong>'._t('Click here to create a new form.').'</strong>', '', 'manage', 'SearchForm', 'ListForms'); ?>
		</div>
<?php
	} else {
		if(!$t_form->haveAccessToForm($this->request->getUserID(), __CA_SEARCH_FORM_READ_ACCESS__, $vn_form_id)) {
			//
			// No access to form - shouldn't ever happen
			//
?>
			<div class="notification-error-box">
				<ul class='notification-error-box'>
					<li class='notification-error-box'><?php print _t('You do not have access to this form'); ?></li>
				</ul>
			</div>
<?php
		} else {
			//
			// Generate form
			//
			
			print "<div class='dashboardWidgetHeading'>".unicode_ucfirst($o_dm->getTableProperty($t_form->get('table_num'), 'NAME_PLURAL')).": ".$t_form->getLabelForDisplay()."</div>\n";
			
			$va_form_element_list = $this->getVar('form_elements');
			$va_flds = array();
			foreach($va_form_element_list as $vn_i => $va_element) {
				$va_flds[] = "'".$va_element['name']."'";
			}
?>
	<?php print caFormTag($this->request, 'Index', 'AdvancedSearchForm', "find/{$vs_controller_name}", 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); ?>
<?php 
			print "<div style='float: right;'>".caFormSubmitButton($this->request, __CA_NAV_BUTTON_SEARCH__, _t("Search"), 'AdvancedSearchForm').'<br/>'.
				caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Reset"), 'AdvancedSearchForm', array('onclick' => 'caAdvancedSearchFormReset()'))."</div>\n";
			print $this->render('search_form_table_html.php');
			
?>
		<script type="text/javascript">
			function caAdvancedSearchFormReset() {
				jQuery('#AdvancedSearchForm input[type=text]').val('');
				jQuery('#AdvancedSearchForm input[type=checkbox]').attr('checked', 0);
				jQuery('#AdvancedSearchForm select').attr('selectedIndex', 0);
			}
		</script>
	</form>
<?php
		}
	}
?>