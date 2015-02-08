<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/ca_object_checkouts.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
 	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	$va_settings 				= $this->getVar('settings');

	$vb_read_only				=	(isset($va_settings['readonly']) && $va_settings['readonly']);
	
	$va_history 				= $this->getVar('checkout_history');
	$vn_checkout_count 			= $this->getVar('checkout_count');
	$va_client_list 			= $this->getVar('client_list');
	$vn_client_count 			= $this->getVar('client_count');
	
	if (!($vs_add_label 		= $this->getVar('add_label'))) { $vs_add_label = _t('Update location'); }

	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div id="<?php print $vs_id_prefix; ?>Container" class="editorHierarchyBrowserContainer">		
				<div  id="<?php print $vs_id_prefix; ?>Tabs">
					<ul>
						<li><a href="#<?php print $vs_id_prefix; ?>Tabs-status"><span><?php print _t('Current status'); ?></span></a></li>
						<li><a href="#<?php print $vs_id_prefix; ?>Tabs-history"><span><?php print _t('History'); ?></span></a></li>
					</ul>
					<div id="<?php print $vs_id_prefix; ?>Tabs-status" class="hierarchyBrowseTab">	
<?php
				if ($t_subject->canBeCheckedOut() && ($va_checkout_status = $t_subject->getCheckoutStatus(array('returnAsArray' => true)))) {
					
					print _t('<strong>Status:</strong> %1', $va_checkout_status['status_display'])."<br/>\n";
							
					switch($vn_status = $va_checkout_status['status']) {
						case __CA_OBJECTS_CHECKOUT_STATUS_AVAILABLE__:
							break;
						case __CA_OBJECTS_CHECKOUT_STATUS_OUT__:
						case __CA_OBJECTS_CHECKOUT_STATUS_OUT_WITH_RESERVATIONS__:
						case __CA_OBJECTS_CHECKOUT_STATUS_RESERVED__:
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
				} else {
					print "<h2>"._t('Cannot be checked out')."</h2>";
				}
				
				//
				// Checkout history
				//
?>
					</div>
					<div id="<?php print $vs_id_prefix; ?>Tabs-history" class="hierarchyBrowseTab caLocationHistoryTab">	
						<h2>
							<?php print ($vn_checkout_count != 1) ? _t('Checked out %1 times', $vn_checkout_count) : _t('Checked out %1 time', $vn_checkout_count); ?>
							<?php print ($vn_client_count != 1) ? _t('by %1 clients', $vn_client_count) : _t('by %1 client', $vn_client_count); ?>
						</h2>
						<table class='caLibraryHistory'>
							<thead class='caLibraryHistory'>
								<th class='caLibraryHistory'><?php print _t('User'); ?></th>
								<th class='caLibraryHistory'><?php print _t('Check out'); ?></th>
								<th class='caLibraryHistory'><?php print _t('Check out notes'); ?></th>
								<th class='caLibraryHistory'><?php print _t('Due'); ?></th>
								<th class='caLibraryHistory'><?php print _t('Returned'); ?></th>
								<th class='caLibraryHistory'><?php print _t('Return notes'); ?></th>
							</thead>
							<tbody>
<?php
					foreach($va_history as $va_event) {
						print "<tr class='caLibraryHistory'><td class='caLibraryHistory'>".$va_event['user_name']."</td><td class='caLibraryHistory'>".$va_event['checkout_date']."</td><td class='caLibraryHistory'>".$va_event['checkout_notes']."</td><td class='caLibraryHistory'>".$va_event['due_date']."</td><td>".$va_event['return_date']."</td><td class='caLibraryHistory'>".$va_event['return_notes']."</td></td></tr>\n";
					}
?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	
	jQuery(document).ready(function() {
		//jQuery('#<?php print $vs_id_prefix; ?>DeaccessionDate').datepicker({constrainInput: false});
		jQuery("#<?php print $vs_id_prefix; ?>Tabs").tabs({ selected: 0 });	
	});
</script>