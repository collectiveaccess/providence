<?php
/** ---------------------------------------------------------------------
 * themes/default/views/client/bundle/ca_commerce_order_items_additional_fees.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
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
 * @package CollectiveAccess
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */
 	$t_subject = $this->getVar('t_subject');
 	$va_options = $this->getVar('options');
 	$o_config = $va_options['config'];
 	$vs_currency_symbol = $va_options['currency_symbol'];
 	
 	$va_fees = $this->getVar('fee_list');
 	
 	if (is_array($va_fees)) {
 		$va_fee_values = $t_subject->get('additional_fees');
 		foreach($va_fees as $vs_code => $va_info) {
 			$vn_fee = isset($va_fee_values[$vs_code]) ? $va_fee_values[$vs_code] : null;
 			if (!strlen($vn_fee)) { $vn_fee = $va_info['default_cost']; }
 			$vn_fee = sprintf("%4.2f", $vn_fee);
 			print "<div class='formLabel' style='float: left; width: 180px;'>".$va_info['label']."<br/>{$vs_currency_symbol}".caHTMLTextInput('additional_fee_'.$vs_code, array('width' => 10, 'height' => 1, 'class'=>'currencyBg', 'value' => $vn_fee))."</div>\n";
 		}
 	}
?>
