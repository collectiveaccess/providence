<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/Search/ca_item_comments_search_controls_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2019 Whirl-i-Gig
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
 	
	$table_list = $this->getVar('table_list');
	$user_list = $this->getVar('user_list');	
	
	$filter_daterange = $this->getVar('filter_daterange');
	$filter_user_id = $this->getVar('filter_user_id');
	$filter_search = $this->getVar('filter_search');
	$filter_moderation = $this->getVar('filter_moderation');
	
	if (!$this->request->isAjax()) {
?>
		<?= caFormTag($this->request, 'Index', 'BasicSearchForm'); ?>
<?php 
			print caFormControlBox(
				'',
				'<div class="list-filter" style="margin-top: -5px; margin-left: -5px; font-weight: normal;">'._t('Show from %1 by %2 with text %3 and status %4', 
					caHTMLTextInput('filter_daterange', array('size' => 10, 'value' => ($filter_daterange) ? $filter_daterange : '', 'class' => 'dateBg')),
					caHTMLSelect('filter_user', array_merge([_t('any user') => ''], $user_list), [], ['value' => $filter_user_id, 'width' => '100px']),
					caHTMLTextInput('search', ['value' => $filter_search, 'size' => '20', 'id' => 'BasicSearchInput']),
					caHTMLSelect('filter_moderation', [_t('any') => -1, _t('approved') => 1, _t('needs moderation') => 0], [], ['value' => $filter_moderation, 'width' => '100px'])
				).'</div>',
				caFormSearchButton($this->request, __CA_NAV_ICON_SEARCH__, _t("Search"), 'BasicSearchForm')
			); 
?>
		</form>
	<?php
	}