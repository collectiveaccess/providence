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
	<div id="caRepresentationMediaReplicationControls<?php print $pn_representation_id; ?>" class="caRepresentationMediaReplicationControls">
<?php	
	if(is_array($va_available_targets = $t_rep->getAvailableMediaReplicationTargets('media', 'original')) && sizeof($va_available_targets)) {
		
		print "<div class='caRepresentationMediaReplicationTargetList'>\n";
		print _t('Replicate media on %1', $vs_target_list);
		print "<a href='#' onclick='jQuery(\"#caRepresentationMediaReplicationLoadIcon{$pn_representation_id}\").css(\"display\", \"inline\"); jQuery(\"#caRepresentationMediaReplicationControls{$pn_representation_id}\").load(\"".caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'StartMediaReplication')."/representation_id/{$pn_representation_id}/target/\" + jQuery(\"#caRepresentationMediaReplicationControls{$pn_representation_id} select[name=target]\").val()); return false;' class='button'>"._t('Start &rsaquo;')."</a>";
		
		print "<span id='caRepresentationMediaReplicationLoadIcon{$pn_representation_id}' class='caRepresentationMediaReplicationLoadIcon'>".caBusyIndicatorIcon($this->request)."</span>";
		print "</div>\n";
	}
	
	if (is_array($va_replications = $t_rep->getUsedMediaReplicationTargets('media', 'original')) && sizeof($va_replications)) {
		print "<table class='caRepresentationMediaReplicationStatusTable'>\n<tr><th>"._t('Replication target')."</th><th>"._t('Status')."</th></tr>\n";
		foreach($va_replications as $vs_target => $va_target_info) {
			$va_status = $t_rep->getReplicationStatus('media', $vs_target);
			print "<tr><td>{$va_target_info['name']} (<em>{$va_target_info['type']}</em>)</td><td>{$va_status['status']}</td></tr>\n";
		}
		print "</table>\n";
	}
?>
	</div>
<?php
	}
?>