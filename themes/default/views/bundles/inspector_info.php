<?php
	$t_item = $this->getVar('t_item');
?>		
	<style>
	.recordTitle.ca_object_lots, .recordTitle.ca_objects {
		display:none;
	}
	</style>
	<div id='inspectorExtraInfo'>

<?php
	/**
	 * UMMA additions to lot inspector
	 */
	if ($t_item->tableName() === 'ca_object_lots') {
		if ($t_item->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => array('donor')))) {
			print $t_item->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => array('donor')))."<br/>";
		}
		if ($t_item->get('ca_object_lots.idno_stub')) {
			print $t_item->get('ca_object_lots.idno_stub')."<br/>";
		}
		if ($t_item->get('ca_object_lots.onsite_date')) {
			print $t_item->get('ca_object_lots.onsite_date')."<br/>";
		}
		if ($t_item->get('ca_object_lots.lot_status_id')) {
			print $t_item->get('ca_object_lots.lot_status_id', array('convertCodesToDisplayText' => true));
		}
	}
	/**
	 * UMMA additions to object inspector
	 */
	if ($t_item->tableName() === 'ca_objects') {
		if ($t_item->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => array('artist')))) {
			print $t_item->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => array('artist')))."<br/>";
		}
		print "<em>".$t_item->get('ca_objects.preferred_labels')."</em> (".$t_item->get('ca_objects.idno').")<br/><br/>";

		$vn_movement_type_id = caGetListItemID('movement_types', 'movement');
		$vn_condition_type_id = caGetListItemID('movement_types', 'condition');

		$vs_movement_for_display = caGetListItemForDisplay('movement_types', 'movement');
		$vs_condition_for_display = caGetListItemForDisplay('movement_types', 'condition');

		print "<strong>Launch new</strong>:<br/>";

		print caEditorLink($this->request, "&nbsp;&nbsp;{$vs_movement_for_display}", '', 'ca_movements', null, array(
			'type_id' => $vn_movement_type_id,
			'rel' => 1,
			'rel_table' => 'ca_objects',
			'rel_type_id' => 'part',
			'rel_id' => $t_item->getPrimaryKey()
		));
		print "</br >";
		print caEditorLink($this->request, "&nbsp;&nbsp;{$vs_condition_for_display}", '', 'ca_movements', null, array(
			'type_id' => $vn_condition_type_id,
			'rel' => 1,
			'rel_table' => 'ca_objects',
			'rel_type_id' => 'part',
			'rel_id' => $t_item->getPrimaryKey()
		));
	}
	/**
	 * UMMA additions to exhibit inspector
	 */
	if (($t_item->tableName() === 'ca_occurrences') && ($t_item->get('type_id') == caGetListItemID('occurrence_types', 'exhibition'))) {
		$vn_loan_object_type_id = caGetListItemID('loan_types', 'loan_object');
		$vs_loan_object_for_display = caGetListItemForDisplay('loan_types', 'loan_object');
		print caEditorLink($this->request, "Launch new {$vs_loan_object_for_display}", '', 'ca_loans', null, array(
			'type_id' => $vn_loan_object_type_id,
			'rel' => 1,
			'rel_table' => 'ca_occurrences',
			'rel_type_id' => 'related',
			'rel_id' => $t_item->getPrimaryKey()
		));
	}
	/**
	 * UMMA additions to loan inspector
	 */
	if (($t_item->tableName() === 'ca_loans') && ($t_item->get('type_id') == caGetListItemID('loan_types', 'loan_in'))) {
		$vn_loan_object_type_id = caGetListItemID('loan_types', 'loan_object');
		$vs_loan_object_for_display = caGetListItemForDisplay('loan_types', 'loan_object');
		print caEditorLink($this->request, "Launch new {$vs_loan_object_for_display}", '', 'ca_loans', null, array(
			'type_id' => $vn_loan_object_type_id,
			'rel' => 1,
			'rel_table' => 'ca_loans',
			'rel_type_id' => 'related',
			'rel_id' => $t_item->getPrimaryKey()
		));
	}
?>
	</div>