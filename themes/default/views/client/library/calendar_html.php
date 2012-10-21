<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/list_loans_html.php : 
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
 
	$va_order_list = $this->getVar('order_list');
 	$t_order = $this->getVar('t_order');
 	$t_item = $this->getVar('t_order_item');
 	$va_filter_options = $this->getVar('filter_options');
 	
 	$vs_currency_symbol = $this->getVar('currency_symbol');
 	
 	// create order list for calendar
 	$va_event_list = array();
 	foreach($va_order_list as $vn_i => $va_order) {
 		if (!$va_order['loan_checkout_date_start'] || !$va_order['loan_due_date_end']) { continue; }
 		
 		$va_event_list[] = array(
 			'title' => '['.$va_order['order_number'].'] '.$va_order['billing_fname'].' '.$va_order['billing_lname'].' ('.(($va_order['num_items'] == 1) ? _t('%1 item', $va_order['num_items']) : _t('%1 items', $va_order['num_items'])).')',
 			'start' => $va_order['loan_checkout_date_start'],
 			'end' => $va_order['loan_due_date_end'],
 			'url' => caNavUrl($this->request, 'client/library', 'OrderEditor', 'Edit', array('order_id' => $va_order['order_id']))
 		);
 	}
?>
<div class="sectionBox">
	
	<a href='#' id='showTools'><?php print _t("Show tools"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>
 	<a href='#' id='hideTools' style='display: none;'><?php print _t("Hide tools"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>

	<br style="clear: both;"/>

	<div id="searchToolsBox">
		<div class="bg">
<?php
			print caFormTag($this->request, 'Calendar', 'caViewOptions', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
?>
	<table width="100%">
		<tr valign="top">
			<td valign="top">
				<div class="formLabel"><?php print _t('Search')."<br/>".caHTMLTextInput('search', array('value' => $va_filter_options['search']), array('width' => '350px')); ?></div>
			</td>
			<td valign="top">
<?php
			print $t_order->htmlFormElement('order_status', null, array('nullOption' => '-'));
?>
			</td>
			<td align="left" valign="top">
<?php
				print caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t('Filter'), 'caViewOptions', array()).'<br/>';
				print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Reset'), 'caViewOptions', array('onclick' => 'jQuery("#searchToolsBox input").val("");'));

?>
			</td>
			<td align="right" valign="top">
<?php
	print caNavHeaderButton($this->request, __CA_NAV_BUTTON_ADD_LARGE__, _t("New loan"), 'client/library', 'CheckOut', 'Index', array('order_id' => 0))
?>
			</td>
		</tr>
	</table>
			</form>
		</div>
	</div>
 	
 	
 	<div id='calendar' style='margin:3em 0;font-size:13px'></div>
 	
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
		jQuery('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			editable: true,
			events: <?php print json_encode($va_event_list); ?>
		});
		
	});
</script>