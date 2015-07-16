<?php
/* ----------------------------------------------------------------------
 * library/checkin/widget_checkout_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
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
 
 
 	$pn_user_id = $this->getVar('user_id');
 	$t_user = $this->getVar('t_user');
?>
	<h3 class='libraryCheckOut'><?php print _t('Library check out'); ?>:
	<div>
<?php
	if ($t_user->getPrimaryKey()) {
		print _t('Checkout for %1 (%2)', trim($t_user->get('fname').' '.$t_user->get('lname')),  $t_user->get('email'));
		
		if(
			is_array($va_checkouts = ca_object_checkouts::getOutstandingCheckoutsForUser($pn_user_id, "<unit relativeTo='ca_objects'><l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)</unit> <em>Due ^ca_object_checkouts.due_date%timeOmit=1</em>"))
			&&
			(sizeof($va_checkouts) > 0)
		) {
			print "<div class='caLibraryCheckoutList'>\n";
			print "<h4>"._t('User holds:')."</h4>\n";
			print "<ul class='caLibraryCheckoutList'>\n";
			foreach($va_checkouts as $va_checkout) {
				print "<li>".$va_checkout['_display']."</li>\n";
			}
			print "</ul>\n";
			print "</div>\n";
		}
	} else {
		// User not selected yet
		print _t('Items out (all users): %1', ca_object_checkouts::numOutstandingCheckouts())."<br/>\n";
	}
?>
	</div>
	</h3>