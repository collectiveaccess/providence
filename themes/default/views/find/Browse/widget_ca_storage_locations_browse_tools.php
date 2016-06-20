<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/widget_ca_storage_locations_browse_tools.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
 
  	$vo_result_context 			= $this->getVar('result_context');
 	$vo_result					= $this->getVar('result');
?>
<h3 class='storage'>
	<?php print _t("Browse %1", $this->getVar('mode_type_plural'))."<br/>\n"; ?>
</h3>
<?php 
	if ($vo_result) {
		print $this->render('Results/current_sort_html.php');
		print $this->render('Search/search_sets_html.php'); 
	}
?>