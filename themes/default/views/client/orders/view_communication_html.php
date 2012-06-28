<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/view_communication_html.php : 
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
 
 	$t_message = $this->getVar('message');
 	$va_snapshot = $t_message->get('set_snapshot');
 ?>
 	<div id="caClientCommunicationsMessage">
 <?php
	print caClientServicesFormatMessage($this->request, $va_data = $t_message->getFieldValuesArray(), array('replyButton' => "<a href='#' class='caClientCommunicationsMessageReplyButton' onclick='jQuery(\"#caClientCommunicationsMessageDisplay\").load(\"".caNavUrl($this->request, 'client/orders', 'Communications', 'Reply', array('transaction_id' => $va_data['transaction_id'], 'communication_id' => $va_data['communication_id']))."\");'>"._t("Reply")."</a>"));
	
	if (is_array($va_snapshot) && is_array($va_snapshot['items']) && sizeof($va_snapshot['items'])) {
		$va_ids = array();
		foreach($va_snapshot['items'] as $vn_id => $vn_dummy) { $va_ids[] = (int)$vn_id; }
		$t_object = new ca_objects();

		$qr_res = $t_object->makeSearchResult('ca_objects', $va_ids);
?>
	<div class="caClientCommunicationsAttachedMediaContainer">
<?php
	if ($vn_communication_id = $t_message->get('communication_id')) {
		print "<div>".caNavLink($this->request, _t('Create new order with attached media'), 'caClientCommunicationsNewOrderButton', 'client/orders', 'OrderEditor', 'CreateNewOrderFromCommunication', array('communication_id' => $vn_communication_id))."</div>";
	}
?>
		<a href="#" onclick="showHideCommunicationAttachedMedia(); return false;" id="caClientCommunicationsAttachedMediaControl" class="caClientCommunicationsAttachedMediaControl"><?php print _t('Show attached media'); ?> &rsaquo;</a>
		<br style="clear: both"/>
		
		<div id="caClientCommunicationsAttachedMedia" class="caClientCommunicationsAttachedMediaItems caClientCommunicationsAttachedMedia">
			<ul class="caClientCommunicationsAttachedMediaList">
<?php		
				while($qr_res->nextHit()) {
					$vs_representation_tag = $qr_res->getMediaTag('ca_object_representations.media', 'thumbnail');
					$vs_title = $qr_res->get('ca_objects.preferred_labels.name')."<br/>";
					$vs_idno = $qr_res->get('ca_objects.idno')."<br/>";
					$vn_object_id = $qr_res->get('ca_objects.object_id');
					$vn_representation_id = $qr_res->get('ca_object_representations.representation_id');
					
					$va_title = array();
?>
					<li class='caClientCommunicationsAttachedMediaItem'>
						<div class='imagecontainer'>
							<div class='caClientCommunicationsAttachedMediaItemThumbnail'>
<?php
							if ($vs_representation_tag) {
								print caNavLink($this->request, $vs_representation_tag, '', 'editor/objects', 'ObjectEditor', 'Edit', array('object_id' => $vn_object_id));
							}
							
							if ($vs_title) {
								if (mb_strlen($vs_title) > 70) {
									$va_title[] = '<em>'.mb_substr($vs_title, 0, 67).'...</em>';
								} else {
									$va_title[] = '<em>'.$vs_title.'</em>';
								}
							}
							
							if ($vs_idno) {
								$va_title[] = '<strong>'._t('Id:').'</strong> '.$vs_idno;
							}
							$vs_title = join('<br/>', $va_title);
?>
							</div>
							<div class='caClientCommunicationsAttachedMediaItemCaption'><?php print caNavLink($this->request, $vs_title, '', 'Detail', 'Object', 'Show', array('object_id' => $vn_object_id)); ?></div>
						</div>
					</li>
<?php
				}
?>
			</ul>
		</div>
	</div>
<?php
	}
?> 	
 	</div>


<script type="text/javascript">
	function showHideCommunicationAttachedMedia() {
		jQuery('#caClientCommunicationsAttachedMedia').slideToggle(250, function() {
			if(jQuery('#caClientCommunicationsAttachedMedia').css("display") == 'none') {
				jQuery('#caClientCommunicationsAttachedMediaControl').html("<?php print _t("Show attached media"); ?> &rsaquo;");
			} else {
				jQuery('#caClientCommunicationsAttachedMediaControl').html("<?php print _t("Hide attached media"); ?> &rsaquo;");
			}
		});
	}
</script>