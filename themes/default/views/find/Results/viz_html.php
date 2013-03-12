<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/viz_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
	print $this->getVar('viz_html');
	
	$vn_num_items_total = $this->getVar('num_items_total');
	$vn_num_items_rendered = $this->getVar('num_items_rendered');
	$t_item = $this->getVar('t_item');
?>
<div class="caMediaOverlayControls">
<?php
	print ($vn_num_items_rendered == 1) ? _t("Displaying %1 of %2 %3", $vn_num_items_rendered, $vn_num_items_total, $t_item->getProperty('NAME_SINGULAR')) : _t("Displaying %1 of %2 %3", $vn_num_items_rendered, $vn_num_items_total, $t_item->getProperty('NAME_PLURAL'));
?>
		<div class='close'><a href="#" onclick="caMediaPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
</div>