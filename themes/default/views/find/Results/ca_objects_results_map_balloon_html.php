<?php
/* ----------------------------------------------------------------------
 * themes/default/views/Results/ca_objects_results_map_balloon_html.php :
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
 
$va_ids 				= $this->getVar('ids');		// this is a search result row
$va_access_values 		= $this->getVar('access_values');

foreach($va_ids as $vn_id) {
	$t_object = new ca_objects($vn_id);
?>
<div id="mapBalloon">
<?php
	$va_rep = $t_object->getPrimaryRepresentation(array('thumbnail'), null,  array('checkAccess' => $va_access_values));
	if($vs_media_tag = $va_rep['tags']['thumbnail']){
		print caNavLink($this->request, $vs_media_tag, '', 'editor/objects', 'ObjectEditor', 'Edit', array('object_id' => $t_object->get("ca_objects.object_id")));
	}
?>
	<div id="mapBalloonText">
	<?php print caNavLink($this->request, '<b>'.$t_object->get("ca_objects.idno").'</b>: '.$t_object->get("ca_objects.preferred_labels"), '', 'editor/objects', 'ObjectEditor', 'Edit', array('object_id' => $t_object->get("ca_objects.object_id"))); ?>
	</div><!-- end mapBalloonText -->
</div><!-- end mapBallon -->
<br/>
<?php
}
?>