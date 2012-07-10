<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_orders_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 	$va_orders = $this->getVar('order_list');
 	
 	$vn_num_orders = is_array($va_orders) ? sizeof($va_orders) : 0;
?>
	<h3><?php print _t('Client orders'); ?>:
	<div><?php
		print ($vn_num_orders == 1) ? _t('%1 order', $vn_num_orders) : _t('%1 orders', $vn_num_orders);
	?></div>
	</h3>