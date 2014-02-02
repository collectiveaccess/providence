<?php 
	$vs_footer_color = $this->request->config->get('footer_color');
?>
					<div style="clear:both;"><!-- EMPTY --></div>
				</div><!-- end mainContent -->
				<div style="clear:both;"><!-- EMPTY --></div>
			</div><!-- end main -->
		<div id="footerContainer" style="background-color:#<?php print $vs_footer_color; ?>;">
			<div id="footer" ><div style="position: relative;">
<?php
				if ($this->request->isLoggedIn()) {
					print _p("User").': '.$this->request->user->getName().' &gt; '.caNavLink($this->request, _t('Preferences'), '', 'system', 'Preferences', 'EditUIPrefs').' &gt; '.caNavLink($this->request, _t('Logout'), '', 'system', 'auth', 'logout');
				} else {
					print caNavLink($this->request, _t('Login'), '', 'system', 'auth', 'login');
				}
?>
				&nbsp;&nbsp;|&nbsp;&nbsp; &copy; 2013 Whirl-i-Gig, <a href="http://www.collectiveaccess.org" target="_blank">CollectiveAccess</a> <?php _p("is a trademark of"); ?> <a href="http://www.whirl-i-gig.com" target="_blank">Whirl-i-Gig</a>
				[<?php print $this->request->session->elapsedTime(4).'s'; ?>/<?php print caGetMemoryUsage(); ?>]
				<?php if (Db::$monitor) { print " [<a href='#' onclick='jQuery(\"#caApplicationMonitor\").slideToggle(100); return false;'>$</a>]"; } ?>
			</div></div><!-- end footer -->
		</div><!-- end footerContainer -->
		</div><!-- end center -->
		
		
		<!-- Activate super-roundiness technology - anything with the classname rounded gets rounded corners here -->
		<script type="text/javascript">
			jQuery(document).ready(function() { jQuery('.rounded').corner('round 8px'); }); 
		
			// force content to fill window height
			jQuery(document).ready(function() {
				jQuery('#mainContent').css('min-height', (window.innerHeight - 40) + 'px');
			});
		</script>
<?php
	print TooltipManager::getLoadHTML();
	print FooterManager::getLoadHTML();
?>

	<!-- Overlay for media display triggered from left sidenav widget or quicklook -->
	<div id="caMediaPanel" class="caMediaPanel"> 
		<div id="caMediaPanelContentArea"></div>
	</div>
	
	<!-- Overlay for search/browse results-based editing -->
	<div id="caResultsEditorPanel" class="caResultsEditorPanel"> 
		<div id="caResultsEditorPanelContentArea"></div>
	</div>
	
	<div id="editorFieldList">
		<div id="editorFieldListHeader"><?php print _t('Form table of contents'); ?></div>
		<div id="editorFieldListContentArea"></div>
	</div>
	
	<div id="caHierarchyOverviewPanel">
		<div id="caHierarchyOverviewClose" class="close"> </div>
		<div id="caHierarchyOverviewHeader"><?php print _t('Browse hierarchy'); ?></div>
		<div id="caHierarchyOverviewContentArea"></div>
	</div>
	
	<script type="text/javascript">
	/*
		Set up the "quicklook" panel that will be triggered by links in each search result
		Note that the actual <div>'s implementing the panel are located in views/pageFormat/pageFooter.php
	*/
	var caMediaPanel, caResultsEditorPanel, caEditorFieldList, caHierarchyOverviewPanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caMediaPanel = caUI.initPanel({ 
				panelID: 'caMediaPanel',						/* DOM ID of the <div> enclosing the panel */
				panelContentID: 'caMediaPanelContentArea',		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: '#000000',				/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
				exposeBackgroundOpacity: 0.7,					/* opacity of background color masking out page content; 1.0 is opaque */
				panelTransitionSpeed: 400,						/* time it takes the panel to fade in/out in milliseconds */
				closeButtonSelector: '.close',					/* anything with the CSS classname "close" will trigger the panel to close */
				onOpenCallback: function() {
					jQuery('#topNavContainer').hide(250);
				},
				onCloseCallback: function() {
					jQuery('#topNavContainer').show(250);
				}
			});
			
			caResultsEditorPanel = caUI.initPanel({ 
				panelID: 'caResultsEditorPanel',						/* DOM ID of the <div> enclosing the panel */
				panelContentID: 'caResultsEditorPanelContentArea',		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: '#000000',				/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
				exposeBackgroundOpacity: 0.7,					/* opacity of background color masking out page content; 1.0 is opaque */
				panelTransitionSpeed: 100,						/* time it takes the panel to fade in/out in milliseconds */
				closeButtonSelector: '.close',					/* anything with the CSS classname "close" will trigger the panel to close */
				onOpenCallback: function() {
					jQuery('#topNavContainer').hide(250);
				},
				onCloseCallback: function() {
					jQuery('#topNavContainer').show(250);
					caResultsEditor.caResultsEditorCloseFullScreen(true);
				}
			});
			
			caEditorFieldList = caUI.initPanel({ 
				panelID: 'editorFieldList',						/* DOM ID of the <div> enclosing the panel */
				panelContentID: 'editorFieldListContentArea',		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: '#000000',				/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
				exposeBackgroundOpacity: 0.7,					/* opacity of background color masking out page content; 1.0 is opaque */
				panelTransitionSpeed: 200,						/* time it takes the panel to fade in/out in milliseconds */
				closeButtonSelector: '.close',					/* anything with the CSS classname "close" will trigger the panel to close */
				center: true
			});
			
			caHierarchyOverviewPanel = caUI.initPanel({ 
				panelID: 'caHierarchyOverviewPanel',			/* DOM ID of the <div> enclosing the panel */
				panelContentID: 'caHierarchyOverviewContentArea',/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: '#000000',				/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
				exposeBackgroundOpacity: 0.7,					/* opacity of background color masking out page content; 1.0 is opaque */
				panelTransitionSpeed: 200,						/* time it takes the panel to fade in/out in milliseconds */
				closeButtonSelector: '.close',					/* anything with the CSS classname "close" will trigger the panel to close */
				center: true
			});
		}
		
		// Show "more" navigation button?
		if ((jQuery('#leftNav').height() > 0) && (jQuery('#leftNav').height() - jQuery('#widgets').height()) < (jQuery('#leftNav #leftNavSidebar').height() + 50)) {
			jQuery('#caSideNavMoreToggle').show();
		} else {
			jQuery('#caSideNavMoreToggle').hide();
		}
	});
	</script>
<?php
	if (Db::$monitor) { print $this->render('system/monitor_html.php'); }
?>
	</body>
</html>