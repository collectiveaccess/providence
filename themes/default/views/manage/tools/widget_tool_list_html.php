<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/tools/widget_tool_list_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 
	$vn_tool_count 	= $this->getVar('tool_count');
	$o_tool_manager = $this->getVar('tool_manager');
	$va_tools = $o_tool_manager->getTools();
	
	if(strtolower($this->request->getAction()) != 'index') {
		print "<h3 class='nextPrevious'>".caNavLink($this->request, _t('Back to list'), '', 'manage', 'Tools', 'Index')."</h3>";
	}
?>
<h4><div id='caColorbox' style='border: 6px solid #444444; padding-bottom:15px;'>
<strong><?php print _t('Tools for your installation'); ?>:</strong>
<p><?php
	print (is_array($va_tools) && sizeof($va_tools)) ? join("<br/>", array_keys($va_tools)) : _t('None available');
?></p>
</div></h4>