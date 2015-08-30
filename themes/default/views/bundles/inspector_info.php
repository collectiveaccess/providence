<?php
	$t_item = $this->getVar('t_item');
?>		
	<style>
	.inspectorCurrentLocation {
		display:none;
	}
	.inspectorCurrentLocationVHEC {
		color: #aaa;
    	font-style: italic;
	    font-size: 12px;
	    margin-bottom: 8px;
	}
	</style>
	<div id='inspectorExtraInfo'>

<?php
	/**
	 * VHEC additions to object inspector
	 */
	if ($t_item->tableName() === 'ca_objects') {
		if ($vs_current_location = $t_item->getLastLocationForDisplay("<ifdef code='ca_storage_locations.parent.preferred_labels'>^ca_storage_locations.parent.preferred_labels ➜ </ifdef>^ca_storage_locations.preferred_labels.name")) {
			print "<div class='inspectorCurrentLocationVHEC'>"._t('Location: %1', $vs_current_location)."</div>\n";
		}
	}
?>
	</div>