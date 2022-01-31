<?php 
/* ----------------------------------------------------------------------
 * views/pageFormat/menuBar.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2021 Whirl-i-Gig
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

$menu_color = $this->request->config->get('menu_color');
?>
<div><div id="topNavContainer">
	<div id="topNav" style="background-color:#<?= $menu_color; ?>;">
		<div class="roundedNav" >
			<div id="logo" onclick='document.location="<?= $this->request->getBaseUrlPath().'/'; ?>";'><?= caGetMenuBarLogo(); ?>></div>
				<div id="navWrapper">
<?php
		if ($this->request->isLoggedIn()) {
			if ($this->request->user->canDoAction('can_quicksearch')) {
?>
				<div class="sf-menu sf-menu-search" >
					
					<!-- Quick search -->
<?php 	
						if ($target_table = $this->request->config->get('one_table_search')) {	
							print caFormTag($this->request, 'Index', 'caQuickSearchForm', 'find/'.$target_table, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true)); 
						} else {
							print caFormTag($this->request, 'Index', 'caQuickSearchForm', 'find/QuickSearch', 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true)); 
						}
					
						if ($this->request->isLoggedIn() && ($this->request->user->getPreference('clear_quicksearch') == 'auto_clear')) { 
?>
						<input type="text" name="search" length="15" id="caQuickSearchFormText" value="<?= htmlspecialchars(Session::getVar('quick_search_last_search'), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" onfocus="this.value='';"/>
<?php						
						} else {
?>
						<input type="text" name="search" length="15" id="caQuickSearchFormText" value="<?= htmlspecialchars(Session::getVar('quick_search_last_search'), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>"/>	
<?php
						}
						print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_SEARCH__, 1, array('style' => 'float: right; margin: 5px 3px 0 0; color: #777',)), 'caQuickSearchFormSubmit', 'caQuickSearchForm', null, ['aria-label' => _t('Perform a quick search')]);
?>
					</form>
				</div>
<?php
			}
?>
			<ul class="sf-menu" style="background-color:#<?= $menu_color; ?>;">
				<?= $this->getVar('nav')->getHTMLMenuBar('menuBar', $this->request); ?>
			</ul>
	<?php
		}
?>	
			</div><!-- END navWrapper -->
		</div><!-- END roundedNav -->
		<div style="clear:both;"><!--EMPTY--></div>
	</div><!-- END topNav -->
</div><!-- END topNavContainer --></div>
<div id="main" class="width">
