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
 	
 	$t_item = $this->getVar('t_subject');
 	
	if($this->request->config->get('summary_header_enabled')) {
?>
<div id='footer'>
<?php
	if($this->request->config->get('summary_show_identifier')) {
		print "<span class='footerText'>".$t_item->getLabelForDisplay()." (".$t_item->get($t_item->getProperty('ID_NUMBERING_ID_FIELD')).")</span>";
	}
	
	if($this->request->config->get('summary_show_timestamp')) {
		print "<span class='footerText'>".caGetLocalizedDate(null, array('dateFormat' => 'delimited'))."</span>";
	}
?>
</div>
<?php
	}
?>