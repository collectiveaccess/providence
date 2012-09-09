<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/orders/order_pdf_export_html.php 
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
 * ----------------------------------------------------------------------
 */

	$t_order				= new ca_commerce_orders();
	$t_item					= new ca_commerce_order_items();
	$va_order_list 			= $this->getVar('order_list');
	$vn_num_orders			= sizeof($va_order_list);
	
 	$va_filter_options = $this->getVar('filter_options');
 	
	$vs_currency_symbol = $this->getVar('currency_symbol');
	
	$va_display_list = array(
		'order_number' => _t('Order #'), 'created_on' => _t('Created'), 'client' => _t('Client'), 'summary' => _t('Summary'), 'order_status' => _t('Status'), 'total' => _t('Total'), 'shipping' => _t('Shipping')
	);
?>
<style type="text/css">
<!--
/* commentaire dans un css */
table, td { border: 1px solid #000000; color: #000000; text-wrap: normal; width: 135px; height: 120px; padding: 5px; font-size: 11px;}
td.odd   { color: #00AA00; }
.displayHeader { background-color: #EEEEEE; padding: 5px; border: 1px solid #999999; font-size: 12px; }
#pageHeader { background-color: #<?php print $this->request->config->get('report_color'); ?>; margin: 0px 5px 10px 5px; padding: 3px 5px 2px 10px; width: 100%; height: 45px; }
.headerText { color: #<?php print ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF"; ?>; margin: -5px 0px 10px 35px; }
.pagingText { color: #<?php print ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF"; ?>; margin: -5px 0px 10px 35px; text-align: right; }
-->
</style>

<?php
	$vn_start = 0;
	while($vn_start < (sizeof($va_display_list) ))   {
?>
	<page backtop="50px">
<?php
	if($this->request->config->get('report_header_enabled')) {
?>
	<page_header>
		<div id='pageHeader'>
<?php
			if(file_exists($this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img'))){
				print '<img src="'.$this->request->getThemeDirectoryPath().'/graphics/logos/'.$this->request->config->get('report_img').'"/>';
 			}
			if($this->request->config->get('report_show_timestamp')) {
				print "<span class='headerText'>".caGetLocalizedDate(null, array('dateFormat' => 'delimited'))."</span>";
			}
			if($this->request->config->get('report_show_number_results')) {
				print "<span class='headerText'>".(($vn_num_orders == 1) ? _t('%1 loan', $vn_num_orders) : _t('%1 loans', $vn_num_orders))."</span>";
			}
			
			// Convert filter options to printable string
			$va_filter_options_proc = array();
			$va_filter_options_print = array();
			foreach($va_filter_options as $vs_opt => $vs_opt_val) {
				if (!($vs_opt_val = trim($vs_opt_val))) { continue; }
				$vs_key = null;
				switch($vs_opt) {
					case 'order_status':
						$va_filter_options_proc[$vs_key = _t('Status')]  = $t_order->getChoiceListValue('order_status', $vs_opt_val);
						break;
					case 'created_on':
						$va_filter_options_proc[$vs_key = _t('Created')] = $vs_opt_val;
						break;
					case 'shipped_on_date':
						$va_filter_options_proc[$vs_key = _t('Scheduled ship date')] = $vs_opt_val;
						break;
					case 'shipping_date':
						$va_filter_options_proc[$vs_key = _t('Ship date')] = $vs_opt_val;
						break;
					case 'shipping_method':
						$va_filter_options_proc[$vs_key = _t('Ship method')] = $vs_opt_val;
						break;
					case 'search':
						$va_filter_options_proc[$vs_key = _t('Search')] = $vs_opt_val;
						break;
					case 'user_id':						
						$t_filter_user = new ca_users($vs_opt_val);
						$va_filter_options_proc[$vs_key = _t('Client')] = $t_filter_user->getUserNameFormattedForLookup();
						break;
				}
				
				if ($vs_key) {
					$va_filter_options_print[] = "{$vs_key}: ".$va_filter_options_proc[$vs_key];
				}
			}
			
			if (sizeof($va_filter_options_print)) {
				print "<span class='headerText'>"._t('Orders with %1', join("; ", $va_filter_options_print))."</span>";
			}
			print "<span class='pagingText'>"._t("Page [%1]/[%2]", "[page_cu]", "[page_nb]")."</span>";
?>
		</div>
	</page_header>
<?php
	}
?>
	<table class="listtable" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
<?php
		// output headers
		$vn_count = 0;
		foreach($va_display_list as $vs_column_code => $vs_column_name) {
			
			// Skip first few columns as needed
			if ($vn_count < $vn_start) { 
				$vn_count++;
				continue;
			}
			
			print "<th class='displayHeader'>".((mb_strlen($vs_column_name) > 30) ? strip_tags(mb_substr($vs_column_name, 0, 27))."..." : $vs_column_name)."</th>";
			$vn_count++;
			
			if ($vn_count >= ($vn_start + 8)) {
				break;
			}
		}
		
?>
		</tr>
<?php
		$i = 0;
		//print_R($va_order_list); die;
		foreach($va_order_list as $vn_i => $va_order) {
			
			($i == 2) ? $i = 0 : "";
?>
			<tr <?php print ($i ==1) ? "class='odd'" : ""; ?>>
<?php
				
				$vn_count = 0;
				foreach($va_display_list as $vs_column_code => $vs_column_name) {
					// Skip first few columns as needed
					if ($vn_count < $vn_start) { 
						$vn_count++;
						continue;
					}
					
					$vs_size_attr = '';
					switch($vs_column_code) {
						case 'client':
							$vs_display_value = $va_order['billing_fname'].' '.$va_order['billing_lname']."<br/>\n";
							$vs_display_value .= ($va_order['billing_organization'] ? $va_order['billing_organization']."<br/>\n" : "");
							$vs_display_value .= ($va_order['billing_address1'] ? $va_order['billing_address1']."<br/>\n" : "");
							$vs_display_value .= ($va_order['billing_address2'] ? $va_order['billing_address2']."<br/>\n" : "");
							
							$va_city_state_postal = array();
							if ($va_order['billing_city']) { $va_city_state_postal[] = $va_order['billing_city']; }
							if ($va_order['billing_zone']) { $va_city_state_postal[] = $va_order['billing_zone']; }
							if ($va_order['billing_postalcode']) { $va_city_state_postal[] = $va_order['billing_postalcode']; }
							
							$vs_display_value .= join(" ", $va_city_state_postal)."<br/>\n";
							
							$vs_display_value .= ($va_order['billing_country'] ? $va_order['billing_country']."<br/>\n" : "");
							
							$vs_display_value .= ($va_order['billing_phone'] ? _t('Phone: %1', $va_order['billing_phone'])."<br/>\n" : "");
							$vs_display_value .= ($va_order['billing_fax'] ? _t('Fax: %1', $va_order['billing_fax'])."<br/>\n" : "");
							$vs_display_value .= ($va_order['billing_email'] ? $va_order['billing_email']."<br/>\n" : "");
							break;
						case 'created_on':
							$vs_display_value = caGetLocalizedDate($va_order[$vs_column_code], array('timeOmit' => true, 'dateFormat' => 'delimited'));
							$vs_size_attr = ' width="60"';
							break;
						case 'summary':
							$vs_display_value = "<strong>".(($va_order['num_items'] == 1) ? _t('%1 item', $va_order['num_items']) : _t('%1 items', $va_order['num_items']))."</strong>\n"; 
							
							$va_items = $t_order->getItems(array('order_id' => $va_order['order_id']));
							if(is_array($va_items) && sizeof($va_items)) {
								$va_item_titles = array();
								foreach($va_items as $vn_i => $va_item) {
									if ($vs_item_title = "[".trim($va_item['idno'])."] ".caTruncateStringWithEllipsis($va_item['name'], 20)." (".$t_item->getChoiceListValue('service', $va_item['service']).")") {
										$va_item_titles[] = $vs_item_title;
									}
								}
								$vs_display_value .= ":<br/>\n".join(",<br/>\n", $va_item_titles);
							}
						
							$vs_size_attr = ' width="300"';
							break;		
						case 'order_status':
							$vs_display_value = $t_order->getChoiceListValue('order_status', $va_order[$vs_column_code]);
							$vs_size_attr = ' width="70"';
							break;		
						case 'total':
							$vs_display_value = $vs_currency_symbol.sprintf("%4.2f", $va_order['order_total']);
							$vs_size_attr = ' width="70"';
							break;	
						case 'shipping':
							$vs_display_value = _t('Method: %1', $va_order['shipping_method'])."<br/><br/>\n";
							
							if ($va_order['shipping_date']) {
								$vs_display_value .= _t('Ship date: %1', caGetLocalizedDate($va_order['shipping_date'], array('timeOmit' => true, 'dateFormat' => 'delimited')))."<br/><br/>\n";
							}
							if ($va_order['shipped_on_date']) {
								$vs_display_value .= _t('Shipped on: %1', caGetLocalizedDate($va_order['shipped_on_date'], array('timeOmit' => true, 'dateFormat' => 'delimited')))."<br/><br/>\n";
							}
							$vs_size_attr = ' width="90"';
							break;	
						default:
							$vs_display_value = $va_order[$vs_column_code];
							$vs_size_attr = ' width="70"';
							break;
					}
					
					print "<td{$vs_size_attr}>".(strlen($vs_display_value) > 1200 ? strip_tags(substr($vs_display_value, 0, 1197))."..." : $vs_display_value)."</td>";
					
					$vn_count++;
			
					if ($vn_count >= ($vn_start + 8)) {
						break;
					}
				}
?>	
			</tr>
<?php
			$i++;
		}
		
		
		$vn_start = $vn_start + 8;
?>

	</table>
	</page>
<?php
	}
?>