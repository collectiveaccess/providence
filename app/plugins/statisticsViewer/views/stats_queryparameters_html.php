<?php

	// TODO : Simplify all of these parameters to get a more readable code
	
	// loading the rendering helpers functions
	require_once(__CA_BASE_DIR__."/app/plugins/statisticsViewer/helpers/viewRenderingHelpers.php");	

	// local variables
	$va_informations = $this->getVar('informations');
	$va_queryparameters = $this->getVar('queryparameters');
		
	// Path to the icons for the drop-down options form
	$va_views_images_path = __CA_URL_ROOT__."/app/plugins/statisticsViewer/views/images";	
		

	// Final print
	// Printing statistics title & comment
	print "<h2>".$va_informations->title."</h2>\n";
	print "<p>".$va_informations->comment."</p>\n";
	
	// Form destination & buttons
	print caFormTag($this->request, 'ShowStat', 'queryParametersForm', null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));												
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Continue"), 'queryParametersForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), $this->request->getModulePath(), $this->request->getController(), 'Index', array()), 
		'', 
		''
	);
	print "<input type=hidden name=stat value=".$va_informations->stat." />\n";
	print "<input type=hidden name=id value=".$va_informations->id." />\n";
	
	foreach($va_queryparameters as $va_queryparameter) {
		//$parameters[$i]["string"];
		print "<div class=\"formLabel\">";
		print $va_queryparameter["name"]." : <br/><input type=\"text\" name=\"".$va_queryparameter["name"]."\" /><br/>\n";
		print "</div>";
		//$parameters[$i]["arguments"];
	}
	print "</form>";
	
	
	// Include the CSS
	MetaTagManager::addLink('stylesheet', __CA_URL_ROOT__."/app/plugins/statisticsViewer/css/statisticsViewer.css",'text/css');	
	print "	<div class=\"editorBottomPadding\"><!-- empty --></div>\n" .
			"<div class=\"clear\"><!--empty--></div>";
?>