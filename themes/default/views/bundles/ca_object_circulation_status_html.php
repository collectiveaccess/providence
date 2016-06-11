<?php
/* ----------------------------------------------------------------------
 * bundles/circulation_status.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
/** @var ca_objects $t_subject */
$t_subject = $this->getVar('t_subject');
$vs_id_prefix = $this->getVar('id_prefix');
$vs_placement_code = $this->getVar('placement_code');

?>

<div class="bundleContainer" id="<?php print $vs_id_prefix; ?>">
	<div class="caItemList">
		<div class="labelInfo">
<?php

// print basic form element for circulation status
print "<div style='float:left;'>";
print $t_subject->htmlFormElement('circulation_status_id', null, ['name' => $vs_placement_code.$vs_id_prefix.'ca_object_circulation_status']);
print "</div>";

// print checkout status
if ($t_subject->canBeCheckedOut() && ($va_checkout_status = $t_subject->getCheckoutStatus(array('returnAsArray' => true)))) {
	print "<div style='float:right; font-weight:normal; margin: 10px 0 5px 0; padding-right: 5px;'>";

	switch($vn_status = $va_checkout_status['status']) {
		case __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__:
		case __CA_OBJECTS_CHECKOUT_STATUS_UNAVAILABLE__:
			break;
		case __CA_OBJECTS_CHECKOUT_STATUS_OUT__:
		case __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__:
		case __CA_OBJECTS_CHECKOUT_STATUS_RESERVED__:
			print _t('<strong>Status:</strong> %1', $va_checkout_status['status_display'])."<br/>\n";
			if (in_array($vn_status, array(__CA_OBJECTS_CHECKOUT_STATUS_OUT__, __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__))) {
				print _t('<strong>Borrowed by:</strong> %1 on %2', $va_checkout_status['user_name'], $va_checkout_status['checkout_date'])."<br/>\n";
				if ($va_checkout_status['due_date']) {
					print _t('<strong>Due on:</strong> %1', $va_checkout_status['due_date'])."<br/>\n";
				}
				if ($va_checkout_status['checkout_notes']) {
					print _t('<strong>Notes:</strong> %1', $va_checkout_status['checkout_notes'])."<br/>\n";
				}
			}
			if (in_array($vn_status, array(__CA_OBJECTS_CHECKOUT_STATUS_RESERVED__, __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__))) {
				$va_reservations = $t_subject->getCheckoutReservations();
				print _t("<strong>Reservations:</strong> %1", $vn_num_reservations = sizeof($va_reservations))."<br/>\n";
				if ($vn_num_reservations > 0) {
					$va_reservation_users = array();
					foreach($va_reservations as $va_reservation) {
						$va_reservation_users[] = $va_reservation['user_name'];
					}
					print _t("<strong>Reserved for:</strong> %1", join(", ", $va_reservation_users));
				}
			}
			break;

	}
	print "</div>";
}

?>
		</div>
	</div>
</div>
