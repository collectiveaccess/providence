<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_checkout_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
?>
 	<h3><?php print _t('Client check-in'); ?>:
	<div><?php
		$t_order = new ca_commerce_orders();
		$va_outstanding_loans = $t_order->getOrders(array(
			'type' => 'L',
			'is_outstanding' => true 
		));
		$vn_num_outstanding_loans = sizeof($va_outstanding_loans);
		
		$va_overdue_loans = $t_order->getOrders(array(
			'type' => 'L',
			'is_overdue' => true 
		));
		
		$vn_num_overdue_loans = sizeof($va_overdue_loans);
		
		print ($vn_num_outstanding_loans == 1) ? _t('%1 outstanding loan', $vn_num_outstanding_loans) : _t('%1 outstanding loans', $vn_num_outstanding_loans);
		print "<br/>\n";
		print ($vn_num_overdue_loans == 1) ? _t('%1 overdue loan', $vn_num_overdue_loans) : _t('%1 overdue loans', $vn_num_overdue_loans);
	?></div>
	</h3>