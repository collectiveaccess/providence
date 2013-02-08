<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/list_communications_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
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
 
 	$va_messages_by_transaction = $this->getVar('message_list');
 	$t_communication = $this->getVar('t_communication');
 	$va_filter_options = $this->getVar('filter_options');
 	
 ?>
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
				<div class="formLabel">
<?php
			print _t('Read status')."<br/>\n";
			print caHTMLSelect('read_status', array(
				'-' => '', _t('Unread only') => 'unread', _t('Read only') => 'read'
			), array(), array('value' => $va_filter_options['read_status']));
?>
				</div>
			</td><td>
<?php		
			print $t_communication->htmlFormElement('created_on', null, array('FIELD_TYPE' => 'FT_DATETIME', 'DISPLAY_TYPE' => DT_FIELD, 'value' => $va_filter_options['created_on']));			
?>
			</td><td>
				<div class="formLabel">
					<?php print _t('User account'); ?><br/>
					<input type="text" size="50" name="client_user_id_autocomplete" value="<?php print htmlspecialchars($va_filter_options['_user_id_display'], ENT_QUOTES, 'utf-8'); ?>" id="client_user_id_autocomplete" class="lookupBg"/>
					<input type="hidden" name="user_id" id="user_id" value="<?php print $va_filter_options['user_id']; ?>"/>
				</div>
	
<script type="text/javascript">
	jQuery('#client_user_id_autocomplete').autocomplete(
		{ 
			minLength: 3, delay: 800, scroll: true,
			source: '<?php print caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100)); ?>',
			select: function(event, ui) {
				var item_id = ui.item.id
				jQuery('#user_id').val(item_id);
			}
		}
	).click(function() { this.select(); });
</script>
			</td>
			<td rowspan="2" align="right" valign="bottom">
<?php
				print caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t('Filter'), 'caViewOptions', array());
				print caJSButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t('Reset'), 'caViewOptions', array('onclick' => 'jQuery("#searchToolsBox input").val("");'));
?>
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<div class="formLabel"><?php print _t('Search')."<br/>".caHTMLTextInput('search', array('value' => $va_filter_options['search']), array('width' => '595px')); ?></div>
			</td>
		</tr>
	</table>
			</form>
		</div>
	</div>
 	
	<div id="caClientCommunicationsMessages">
 		<div id="caClientCommunicationsMessageList">
 <?php
		foreach($va_messages_by_transaction as $vn_tranaction_id => $va_messages) {
			$va_message = array_pop($va_messages);
			$va_messages = array_reverse($va_messages);
			print caClientServicesFormatMessageSummary($this->request, $va_message, array('viewContentDivID' => 'caClientCommunicationsMessageDisplay', 'additionalMessages' => $va_messages));
		}
?> 	
		</div>
		
		<div id="caClientCommunicationsMessageDisplay"><div class="caClientCommunicationsMessageDisplayHelpText"><?php print _t("Click on a message at left to view the entire message and its associated media."); ?></div></div>
		<div style="clear:both;"><!-- empty --></div>
	</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.caClientCommunicationsAdditionalMessageSummary, .caClientCommunicationsMessageSummaryContainer').click(function() {
			jQuery('.caClientCommunicationsAdditionalMessageSummary, .caClientCommunicationsMessageSummaryContainer').css('background-color', '');
			var id = jQuery(this).attr('id');
			var bits = id.split(/_/);
			jQuery("#caClientCommunicationsMessageDisplay").load("<?php print caNavUrl($this->request, 'client/orders', 'Communications', 'ViewMessage'); ?>/communication_id/" + bits[1]);
			jQuery("#" + id).css('background-color', "#efefef");
		});
	});
	
	var viewOptioncookieJar = jQuery.cookieJar('caCookieJar');
	
	if (viewOptioncookieJar.get('caClientCommunicationsViewOptionsIsOpen') == undefined) {		// default is to have options open
		viewOptioncookieJar.set('caClientCommunicationsViewOptionsIsOpen', 1);
	}
	if (viewOptioncookieJar.get('caClientCommunicationsViewOptionsIsOpen') == 1) {
		jQuery('#searchToolsBox').toggle(0);
		jQuery('#showTools').hide();
		jQuery('#hideTools').show();
	}

	jQuery('#showTools').click(function() {
		jQuery('#searchToolsBox').slideDown(350, function() { 
			viewOptioncookieJar.set('caClientCommunicationsViewOptionsIsOpen', 1); 
			jQuery("#showTools").hide(); jQuery("#hideTools").show();
		}); 
		return false;
	});
	
	jQuery('#hideTools').click(function() {
		jQuery('#searchToolsBox').slideUp(350, function() { 
			viewOptioncookieJar.set('caClientCommunicationsViewOptionsIsOpen', 0); 
			jQuery("#showTools").show(); jQuery("#hideTools").hide();
		}); 
		return false;
	});
</script>