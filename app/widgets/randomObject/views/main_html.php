<?php
/* ----------------------------------------------------------------------
 * app/widgets/lolCat/views/main_html.php : 
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
 
 	$po_request 			= $this->getVar('request');
	$va_instances			= $this->getVar('instances');
	$va_settings				= $this->getVar('settings');
	$vs_widget_id 			= $this->getVar('widget_id');
 

?>
<div class="dashboardWidgetContentContainer" style="text-align:center; margin-right:20px;">
<?php 
		print "<div><a href='".caEditorUrl($po_request, "ca_objects", $this->getVar("object_id"))."'>".$this->getVar("image")."</a></div>";
		print "<div style='font-weight:bold; font-size:13px; margin-top:3px;'><a href='".caEditorUrl($po_request, "ca_objects", $this->getVar("object_id"))."'>".$this->getVar("label")."</a></div>";
?>	
</div>
