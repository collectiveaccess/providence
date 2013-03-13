<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/list_orders_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 
	$va_order_list = $this->getVar('order_list');
 	$t_order = $this->getVar('t_order');
 	$va_filter_options = $this->getVar('filter_options');
 	
 	$vs_currency_symbol = $this->getVar('currency_symbol');

 	$t_filter_user = new ca_users($va_filter_options['user_id']);
	$vs_filter_user_id_name = $t_filter_user->getUserNameFormattedForLookup();
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caClientOrdersList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	
	<a href='#' id='showTools'><?php print _t("Show tools"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>
 	<a href='#' id='hideTools' style='display: none;'><?php print _t("Hide tools"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>

	<br style="clear: both;"/>

	<div id="searchToolsBox">
		<div class="bg">
<?php
			print caFormTag($this->request, 'Index', 'caViewOptions', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
?>
	<table width="100%">
		<tr valign="top">
			<td>
<?php
			print $t_order->htmlFormElement('order_status', null, array('nullOption' => '-'));
?>
			</td><td>
<?php		
			print $t_order->htmlFormElement('created_on', null, array('FIELD_TYPE' => 'FT_DATETIME', 'DISPLAY_TYPE' => DT_FIELD, 'value' => $va_filter_options['created_on'], 'classname'=> 'dateBg', 'width' => 15));			
?>
			</td><td>
<?php		
			print $t_order->htmlFormElement('shipping_date', null, array('FIELD_TYPE' => 'FT_DATETIME', 'DISPLAY_TYPE' => DT_FIELD, 'value' => $va_filter_options['shipping_date'], 'classname'=> 'dateBg', 'width' => 15));			
?>
			</td><td>
<?php		
			print $t_order->htmlFormElement('shipped_on_date', null, array('FIELD_TYPE' => 'FT_DATETIME', 'DISPLAY_TYPE' => DT_FIELD, 'value' => $va_filter_options['shipped_on_date'], 'classname'=> 'dateBg', 'width' => 15));			
?>
			</td>
			<td align="right" valign="top" rowspan="2">
<?php
	print caNavHeaderButton($this->request, __CA_NAV_BUTTON_ADD_LARGE__, _t("New order"), 'client/orders', 'OrderEditor', 'CustomerInfo', array('order_id' => 0))
?>
			</td>
		</tr>
		<tr>
			<td>
				<div class="formLabel"><?php print _t('Search')."<br/>".caHTMLTextInput('search', array('value' => $va_filter_options['search']), array('width' => '15')); ?></div>
			</td><td colspan="2">
				<div class="formLabel">
					<?php print _t('Client')."<br/>".caHTMLTextInput('user_id_autocomplete', array('value' => $vs_filter_user_id_name, 'class'=> 'lookupBg', 'id' => 'user_id_autocomplete'), array('width' => '40')); ?>
					<input type="hidden" name="user_id" id="user_id" value="<?php print $va_filter_options['user_id']; ?>"/>
				</div>
			</td><td>
<?php		
			print $t_order->htmlFormElement('shipping_method', null, array('nullOption' => '-'));		
?>
			</td>
		</tr>
		<tr>
			<td colspan="4">
<?php
				print caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t('Filter'), 'caViewOptions', array())."\n";
				
				if (sizeof($va_order_list)) {
					print caNavButton($this->request, __CA_NAV_BUTTON_DOWNLOAD__, _t('Get PDF'), 'client/orders', 'Orders', 'Export');
				}
?>
			</td>
			<td align="right">
<?php
				print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Reset'), 'caViewOptions', array('onclick' => 'jQuery("#searchToolsBox input").val("");'))."\n";
?>
			</td>
		</tr>
	</table>
			</form>
		</div>
	</div>
 	
	<table id="caClientOrdersList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
		<tr>
			<th>
				<?php _p('Order #'); ?>
			</th>
			<th>
				<?php _p('Date/time'); ?>
			</th>
			<th>
				<?php _p('Client'); ?>
			</th>
			<th>
				<?php _p('Summary'); ?>
			</th>
			<th>
				<?php _p('Total'); ?>
			</th>
			<th>
				<?php _p('Status'); ?>
			</th>
			<th class="{sorter: false} list-header-nosort">&nbsp;</th>
		</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_order_list)) {
		foreach($va_order_list as $va_order) {
?>
			<tr>
				<td>
					<?php print $va_order['order_number']; ?>
				</td>
				<td>
					<?php print caGetLocalizedDate($va_order['created_on'], array('dateFormat' => 'delimited')); ?>
				</td>
				<td>
					<?php print $va_order['billing_fname'].' '.$va_order['billing_lname']." ".($va_order['billing_organization'] ? "(".$va_order['billing_organization'].")" : ""); ?>
				</td>
				<td>
<?php 
					print ($va_order['num_items'] == 1) ? _t('%1 item', $va_order['num_items']) : _t('%1 items', $va_order['num_items']); 
					
					if ($va_order['shipped_on_date']) {
						print "\n<br/>"._t('Shipped on %1', caGetLocalizedDate($va_order['shipped_on_date'], array('dateFormat' => 'delimited', 'timeOmit' => true)));
					} else {
						if ($va_order['shipping_date']) {
							print "\n<br/>"._t('Ships on %1', caGetLocalizedDate($va_order['shipping_date'], array('dateFormat' => 'delimited', 'timeOmit' => true)));
						}
					}
?>
				</td>
				<td>
					<?php print $vs_currency_symbol.sprintf("%4.2f", $va_order['order_total_item_fees'] + $va_order['order_total_item_tax'] + $va_order['shipping_cost'] + $va_order['handling_cost'])." ("._t('total').")"; ?>
				</td>
				<td>
					<?php print $t_order->getChoiceListValue('order_status', $va_order['order_status']); ?>
				</td>
				<td>
					<?php print caNavButton($this->request, __CA_NAV_BUTTON_EDIT__, _t("Edit"), 'client/orders', 'OrderEditor', 'Edit', array('order_id' => $va_order['order_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
	<tr>
		<td colspan="7" align="center"><?php print _t('No orders match your criteria'); ?></td>
	</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>
<div class="editorBottomPadding"><!-- empty --></div>

<script type='text/javascript'>
	var viewOptioncookieJar = jQuery.cookieJar('caCookieJar');
	
	if (viewOptioncookieJar.get('caClientOrdersViewOptionsIsOpen') == undefined) {		// default is to have options open
		viewOptioncookieJar.set('caClientOrdersViewOptionsIsOpen', 1);
	}
	if (viewOptioncookieJar.get('caClientOrdersViewOptionsIsOpen') == 1) {
		jQuery('#searchToolsBox').toggle(0);
		jQuery('#showTools').hide();
		jQuery('#hideTools').show();
	}

	jQuery('#showTools').click(function() {
		jQuery('#searchToolsBox').slideDown(350, function() { 
			viewOptioncookieJar.set('caClientOrdersViewOptionsIsOpen', 1); 
			jQuery("#showTools").hide(); jQuery("#hideTools").show();
		}); 
		return false;
	});
	
	jQuery('#hideTools').click(function() {
		jQuery('#searchToolsBox').slideUp(350, function() { 
			viewOptioncookieJar.set('caClientOrdersViewOptionsIsOpen', 0); 
			jQuery("#showTools").show(); jQuery("#hideTools").hide();
		}); 
		return false;
	});
	
	jQuery(document).ready(function() {
		jQuery('#user_id_autocomplete').autocomplete( 
			{ 
				minLength: 3, delay: 800,
				source: '<?php print caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100, 'inlineCreate' => 1)); ?>',
				select: function(event, ui) {
					var item_id = ui.item.id;
					if (parseInt(item_id)) {
						jQuery('#user_id').val(item_id);
					}
				}
			}
		);
	});
</script>