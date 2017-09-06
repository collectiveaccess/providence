<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/search_advanced_form_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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
 	
	$t_form = $this->getVar('t_form');
	$vn_form_id = $t_form->getPrimaryKey();
	
	if (!$vn_form_id) {
		//
		// no form defined
		//
?>
		<div class="notification-warning-box">
			<ul class='notification-warning-box'>
				<li class='notification-warning-box'><?php print _t("You must define a search form before you can use the advanced search.").' '.caNavLink($this->request, _t('Click here to create a new form.'), '', 'manage', 'SearchForm', 'ListForms'); ?></li>
			</ul>
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
			
			$va_form_element_list = $this->getVar('form_elements');
			$va_flds = array();
			foreach($va_form_element_list as $vn_i => $va_element) {
				$va_flds[] = "'".$va_element['name']."'";
			}
?>
	<?php print caFormTag($this->request, 'Index', 'AdvancedSearchForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); ?>
		<div class="control-box rounded">
			<div class="simple-search-box">
				<?php print $this->render('Search/search_forms/search_form_table_html.php'); ?>
			</div>
			
			<br style="clear: both;"/>
			
			<div style="float:right; ">
				<?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Reset"), 'AdvancedSearchForm', array('onclick' => 'caAdvancedSearchFormReset()'), array()); ?>			
				<?php print caFormSearchButton($this->request, __CA_NAV_ICON_SEARCH__, _t("Search"), 'AdvancedSearchForm'); ?>
			</div>
			<div class="saveAs" style="float: right; margin-right:20px;">
				<?php print _t("Save search as"); ?>:
				<?php print caHTMLTextInput('_label', array('size' => 10, 'id' => 'caAdvancedSearchSaveLabelInput')); ?>
				<a href="#" onclick="caSaveSearch('AdvancedSearchForm', jQuery('#caAdvancedSearchSaveLabelInput').val(), [<?php print join(',', $va_flds); ?>]); return false;" class="button"><?php print caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
			</div>
		</div>
	</form>
<?php
		}
	}
?>

<script type="text/javascript">
	function caSaveSearch(form_id, label, field_names) {
		var vals = {};
		jQuery(field_names).each(function(i, field_name) { 					// process all fields in form
			var field_name_with_no_period = field_name.replace('.', '_');	// we need a bundle name without periods for compatibility
			vals[field_name] = jQuery('#' + form_id + ' [id=' + field_name_with_no_period + ']').val();
		});
		vals['_label'] = label;											// special "display" title, used if all else fails
		vals['_field_list'] = field_names;								// an array for form fields to expect
		vals['_form_id'] = <?php print (int)$vn_form_id; ?>;			// the current form_id, if running with an interface that passed a form_id
		
		jQuery.getJSON('<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "addSavedSearch"); ?>', vals, function(data, status) {
			if ((data) && (data.md5)) {
				jQuery('.savedSearchSelect').prepend(jQuery("<option></option>").attr("value", data.md5).text(data.label)).attr('selectedIndex', 0);
					
			}
		});
	}
	
	function caAdvancedSearchFormReset() {
		jQuery('#AdvancedSearchForm textarea').val('');
		jQuery('#AdvancedSearchForm input[type=text]').val('');
		jQuery('#AdvancedSearchForm input[type=hidden]').val('');
		jQuery('#AdvancedSearchForm select').val('');
		jQuery('#AdvancedSearchForm input[type=checkbox]').attr('checked', 0);
	}
</script>