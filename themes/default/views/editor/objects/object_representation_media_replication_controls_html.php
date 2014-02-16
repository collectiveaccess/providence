<?php
/* ----------------------------------------------------------------------
 * themes/defailt/views/editor/objects/object_representation_media_replication_controls_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
	$pn_representation_id = $this->getVar('representation_id');
	$t_rep = $this->getVar('t_representation');
	$vs_target_list = $this->getVar('target_list');
	$vs_selected_target = $this->getVar('selected_target');
	
	if ($pn_representation_id > 0) {
?>
	<div class="formLabelError"><?php print join("; ", $t_rep->getErrors()); ?></div>

	<div id="caRepresentationMediaReplicationControls<?php print $pn_representation_id; ?>" class="caRepresentationMediaReplicationControls">
<?php	
		if(is_array($va_available_targets = $t_rep->getAvailableMediaReplicationTargets('media', 'original')) && sizeof($va_available_targets)) {
		
			print "<div class='caRepresentationMediaReplicationTargetList'>\n";
			print _t('Replicate media to %1', $vs_target_list);
			print "<a href='#' onclick='jQuery(\"#caRepresentationMediaReplicationLoadIcon{$pn_representation_id}\").css(\"display\", \"inline\"); jQuery(\"#caRepresentationMediaReplicationStartControl{$pn_representation_id}\").hide(); jQuery(\"#caRepresentationMediaReplicationControls{$pn_representation_id}\").load(\"".caNavUrl($this->request, '*', '*', 'StartMediaReplication', array('representation_id' => $pn_representation_id))."/target/\" + jQuery(\"#caRepresentationMediaReplicationControls{$pn_representation_id} select[name=target]\").val()); return false;' class='button' id='caRepresentationMediaReplicationStartControl{$pn_representation_id}'>"._t('Start &rsaquo;')."</a>";
		
			print "<span id='caRepresentationMediaReplicationLoadIcon{$pn_representation_id}' class='caRepresentationMediaReplicationLoadIcon'>".caBusyIndicatorIcon($this->request)."</span>";
			print "</div>\n";
		}
	
		if (is_array($va_replications = $t_rep->getUsedMediaReplicationTargets('media', 'original')) && sizeof($va_replications)) {
			print "<table class='caRepresentationMediaReplicationStatusTable'>\n<thead><tr><th>"._t('Replication target')."</th><th>"._t('Status')."</th></tr></thead>\n";
			print "<tbody>\n";
		
			$vb_incomplete_replications = 0;
			foreach($va_replications as $vs_target => $va_target_info) {
				$va_status = $t_rep->getMediaReplicationStatus('media', $vs_target);
				print "<tr><td>".(($vs_url = $t_rep->getReplicatedMediaUrl('media', $vs_target)) ? "<a href='{$vs_url}' target='_ext'>{$va_target_info['name']}</a>" : $va_target_info['name'])." (<em>{$va_target_info['type']}</em>)</td><td>{$va_status['status']}</td><td>";
			
				if ($va_status['code'] === __CA_MEDIA_REPLICATION_STATE_COMPLETE__) { // Delete only allows when replication is complete
					print "<a href='#' onclick='jQuery(\"#caRepresentationMediaReplicationLoadIcon{$pn_representation_id}\").css(\"display\", \"inline\"); jQuery(\"#caRepresentationMediaReplicationDeleteControl{$pn_representation_id}\").hide(); jQuery(\"#caRepresentationMediaReplicationControls{$pn_representation_id}\").load(\"".caNavUrl($this->request, '*', '*', 'RemoveMediaReplication', array('representation_id' => $pn_representation_id, 'target' => $vs_target, 'key' => urlencode($va_status['key'])))."\"); return false;' class='button' id='caRepresentationMediaReplicationDeleteControl{$pn_representation_id}'>"._t('Delete &rsaquo;')."</a>";
				}
				print "</td></tr>\n";
			
				if (!in_array($va_status['code'], array(__CA_MEDIA_REPLICATION_STATE_COMPLETE__, __CA_MEDIA_REPLICATION_STATE_ERROR__))) {
					$vb_incomplete_replications++;
				}
			}
			print "</tbody>\n</table>\n";
			
			if ($vb_incomplete_replications > 0) {
?>
	<script type="text/javascript">
		var caRepresentationMediaReplicationStatusRefresh<?php print $pn_representation_id; ?> = setInterval(function() {
			jQuery('#caRepresentationMediaReplicationControls<?php print $pn_representation_id; ?>').parent().load("<?php print caNavUrl($this->request, '*', '*', 'MediaReplicationControls', array('representation_id' => $pn_representation_id)); ?>"); 
			clearInterval(caRepresentationMediaReplicationStatusRefresh<?php print $pn_representation_id; ?>);
		}, 8000);	// every 8 seconds
	</script>
<?php
			}
		} 
?>
	</div>
<?php
	}
?>