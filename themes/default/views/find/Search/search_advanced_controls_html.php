<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/search_advanced_controls_html.php 
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
	
	if (!$this->request->isAjax()) {
		$vs_form_list_select = $t_form->getFormsAsHTMLSelect('form_id', array('onchange' => 'caLoadAdvancedSearchForm(this.options[this.selectedIndex].value)', 'class' => 'searchFormSelector'), array('value' => $this->getVar('form_id'), 'access' => __CA_SEARCH_FORM_READ_ACCESS__, 'user_id' => $this->request->getUserID(), 'table' => $t_form->get('table_num')));
		if ($vs_form_list_select) {
?>
		<a href='#' class='button' id='advancedSearchFormContainerToggle'><?php print _t('Hide search form'); ?> &rsaquo;</a>
<?php
		}
?>
		<div class='searchFormSelector' style='float: right; margin-bottom: 3px; font-size: 9px;'>
			<form action='#' id='advancedSearchFormContainerFormSelector'><?php print ($vs_form_list_select) ? _t('Form').': '.$vs_form_list_select : ''; ?></form>
		</div>
		<div style="clear: both;"><!-- empty --></div>
		<div id="advancedSearchFormContainer">
<?php
	// load initial form
	print $this->render('Search/search_advanced_form_html.php');
?>
		</div>
<?php
	//print "Query string is <pre>".$t_form->getLuceneQueryStringForHTMLFormInput($_REQUEST)."</pre><br/>";
?>
<script type='text/javascript'>
	function caLoadAdvancedSearchForm(form_id) {
		jQuery('#advancedSearchFormContainer').load('<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getAdvancedSearchForm'); ?>', {form_id: form_id});
	}
	
	var caCookieJar = jQuery.cookieJar('caCookieJar');
	if (caCookieJar.get('<?php print $this->getVar('table_name'); ?>_hide_adv_search_form') == 1) {
		jQuery("#advancedSearchFormContainer").toggle(0);
		jQuery("#advancedSearchFormContainerFormSelector").hide();
		jQuery("#advancedSearchFormContainerToggle").html('<?php print addslashes(_t('Show search form'));?> &rsaquo;');
	}
	
	jQuery("#advancedSearchFormContainerToggle").click(function() {
		jQuery("#advancedSearchFormContainer").slideToggle(350, function() { 
			caCookieJar.set('<?php print $this->getVar('table_name'); ?>_hide_adv_search_form', (this.style.display == 'block') ? 0 : 1); 
			jQuery("#advancedSearchFormContainerToggle").html((this.style.display == 'block') ? '<?php print addslashes(_t('Hide search form'));?> &rsaquo;' : '<?php print addslashes(_t('Show search form'));?> &rsaquo;');
			
			if (this.style.display == 'block') {
				jQuery("#advancedSearchFormContainerFormSelector").show();
			} else { 
				jQuery("#advancedSearchFormContainerFormSelector").hide();
			}
		}); 
		return false;
	}); 
</script>
<?php
	}
?>