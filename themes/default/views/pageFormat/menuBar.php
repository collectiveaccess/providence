<?php 
	$va_menu_color = $this->request->config->get('menu_color');
?>
<div><div id="topNavContainer">
	<div id="topNav" style="background-color:#<?php print $va_menu_color; ?>;">
		<div class="roundedNav" >
			<div id="logo" onclick='document.location="<?php print $this->request->getBaseUrlPath().'/'; ?>";'><?php print "<img src='".$this->request->getUrlPathForThemeFile("graphics/logos/".$this->request->config->get('header_img'))."' border='0' alt='"._t("Search")."'/>" ?></div>
				<div id="navWrapper">
<?php
		if ($this->request->isLoggedIn()) {
			if ($this->request->user->canDoAction('can_quicksearch')) {
?>
				<div class="sf-menu sf-menu-search" >
					
					<!-- Quick search -->
<?php 	
						if ($vs_target_table = $this->request->config->get('one_table_search')) {	
							print caFormTag($this->request, 'Index', 'caQuickSearchForm', 'find/'.$vs_target_table, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
						} else {
							print caFormTag($this->request, 'Index', 'caQuickSearchForm', 'find/QuickSearch', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
						}
					
						if ($this->request->isLoggedIn() && ($this->request->user->getPreference('clear_quicksearch') == 'auto_clear')) { 
?>
						<input type="text" name="search" length="15" id="caQuickSearchFormText" value="<?php print htmlspecialchars($this->request->session->getVar('quick_search_last_search'), ENT_QUOTES, 'UTF-8'); ?>" onfocus="this.value='';"/>
<?php						
						} else {
?>
						<input type="text" name="search" length="15" id="caQuickSearchFormText" value="<?php print htmlspecialchars($this->request->session->getVar('quick_search_last_search'), ENT_QUOTES, 'UTF-8'); ?>" onfocus="<?php print htmlspecialchars($this->request->session->getVar('quick_search_last_search'), ENT_QUOTES, 'UTF-8'); ?>"/>	
<?php
						}
						print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_SEARCH__, 1, array('style' => 'float: right; margin: 5px 3px 0 0; color: #777')), 'caQuickSearchFormSubmit', 'caQuickSearchForm'); 
?>
						<!--<input type="hidden" name="no_cache" value="1"/>-->
					</form>
				</div>
<?php
			}
?>
			<ul class="sf-menu" style="background-color:#<?php print $va_menu_color; ?>;">
	<?php
			print $va_menu_bar = $this->getVar('nav')->getHTMLMenuBar('menuBar', $this->request);
?>
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
