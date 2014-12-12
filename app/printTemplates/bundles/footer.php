<?php
/* ----------------------------------------------------------------------
 * app/templates/footer.php : standard PDF report footer
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name Footer
 * @type fragment
 *
 * ----------------------------------------------------------------------
 */
 	
 	
	$vo_result 				= $this->getVar('result');
	$vn_num_items			= (int)$vo_result->numHits();
	
	if($this->request->config->get('report_header_enabled')) {
?>
<div id='footer'>
<?php
	if($this->request->config->get('report_show_search_term')) {
		print "<span class='footerText'>".$this->getVar('criteria_summary_truncated')."</span>";
	}
	
	if($this->request->config->get('report_show_number_results')) {
		print "<span class='footerText'>".(($vn_num_items == 1) ? _t('%1 item', $vn_num_items) : _t('%1 items', $vn_num_items))."</span>";
	}
	
	if($this->request->config->get('report_show_timestamp')) {
		print "<span class='footerText'>".caGetLocalizedDate(null, array('dateFormat' => 'delimited'))."</span>";
	}
?>
</div>
<?php
	}
?>