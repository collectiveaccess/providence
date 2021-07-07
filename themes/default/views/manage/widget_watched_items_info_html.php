<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_watched_items_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2021 Whirl-i-Gig
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

/** @var ca_watch_list $t_watch_list */
$t_watch_list = $this->getVar('t_watch_list');

?>
<h3 class='watchedItems'>
	<?= _t('Your watched items'); ?>
	<div>
		<?= "<span class='header'>"._t("Create set").":</span><br/>"; ?>
		<?= caFormTag($this->request, 'CreateSet', 'caCreateSetFromWatchedItems'); ?>
			<?= caHTMLTextInput('set_name', [
					'id' => 'caCreateSetFromWatchedItemsInput',
					'class' => 'searchSetsTextInput',
					'value' => _t('watchlist').'_'.$this->request->getUser()->get('user_name').'_'.date('Y-m-d')
				], ['width' => '150px']).
				_t("containing watched").
				$t_watch_list->getWatchedTablesAsHTMLSelect($this->request->getUserID(), 'set_table').
				caBusyIndicatorIcon($this->request, ['id' => 'caCreateSetFromResultsIndicator'])."\n";
			?>
			<a href='#' onclick="jQuery('#caCreateSetFromWatchedItems').submit(); return false;" class="button"><?= _t('Create'); ?> &rsaquo;</a>
		</form>
	</div>
</h3>
