<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/Search/ca_item_comments_search_controls_html.php 
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
 	
 	$vs_type_id_form_element = '';
	if ($vn_type_id = intval($this->getVar('type_id'))) {
		$vs_type_id_form_element = '<input type="hidden" name="type_id" value="'.$vn_type_id.'"/>';
	}
	if (!$this->request->isAjax()) {
?>
		<?php print caFormTag($this->request, 'Index', 'BasicSearchForm'); ?>
<?php 
			print caFormControlBox(
				'<div class="simple-search-box">'._t('Search').': <input type="text" id="BasicSearchInput" name="search" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'" size="40"/>'.$vs_type_id_form_element.'</div>',
				'',
				caFormSubmitButton($this->request, __CA_NAV_BUTTON_SEARCH__, _t("Search"), 'BasicSearchForm')
			); 
?>
		</form>
	<?php
	}
?>