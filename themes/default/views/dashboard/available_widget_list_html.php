<?php
/* ----------------------------------------------------------------------
 * themes/default/views/dashboard/available_widget_list_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
	$o_widget_manager = $this->getVar('widget_manager');
	$va_widget_list = $o_widget_manager->getWidgetNames();
	
	print caFormTag($this->request, 'addWidget', 'caWidgetManagerForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
?>
		<input type="hidden" name="widget" value="" id='caWidgetManagerFormWidgetValue'/>
<?php
	foreach($va_widget_list as $vs_widget_name) {
		$va_status = WidgetManager::checkWidgetStatus($vs_widget_name);
		if(!$va_status["available"]) continue;
		
		print "<a href='#' onclick='jQuery(\"#caWidgetManagerFormWidgetValue\").val(\"{$vs_widget_name}\"); jQuery(\"#caWidgetManagerForm\").submit();'><img src='".$this->request->getThemeUrlPath()."/graphics/buttons/addwidget.jpg' style='height:10px; width:10px; border:0px;'> ".$o_widget_manager->getWidgetTitle($vs_widget_name)."</a><br/>\n";
		print "<div id='widgetDescription'>".$o_widget_manager->getWidgetDescription($vs_widget_name)."</div>";
	}
?>
	</form>