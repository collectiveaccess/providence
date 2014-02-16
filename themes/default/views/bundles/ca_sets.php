<?php
/* ----------------------------------------------------------------------
 * bundles/ca_sets.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 
	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	$t_set 						= $this->getVar('t_set');
	$va_set_ids 				= $this->getVar('set_ids');
	$va_available_set_ids 		= $this->getVar('available_set_ids');
	$va_sets 					= $this->getVar('sets');
	
	$va_set_options 			= array_flip($this->getVar('set_options'));
	natcasesort($va_set_options);
	
	$va_settings 				= $this->getVar('settings');
	$va_initial_values 			= $this->getVar('initial_values');
	
	$vb_read_only				= (isset($va_settings['readonly']) && $va_settings['readonly']);
	$vb_batch					= $this->getVar('batch');

	$va_errors = array();
	
	if ($vb_batch) {
		print caBatchEditorSetsModeControl($vn_table_num, $vs_id_prefix);
	} else {
		print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$vn_table_num.'_sets');
	}
?>
<div id="<?php print $vs_id_prefix.$vn_table_num.'_sets'; ?>" <?php print $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
			<table class="objectRepresentationListItem" width="90%"><?php 
					$vn_c = 0;
					if (sizeof($va_set_options)) {
						foreach($va_set_options as $vn_set_id => $vs_set_name) {
							if ($vn_c == 0) { print "<tr>"; }
							print "<td width='33%'><div class='formLabel'>\n";
							print caHTMLCheckboxInput('{fieldNamePrefix}'.$vn_set_id.'_0_{n}', array('value' => $vn_set_id, 'id' => '{fieldNamePrefix}'.$vn_set_id.'_0_{n}'), array())." {$vs_set_name}";
							print "</div></td>";
							$vn_c++;
							
							if ($vn_c == 3) {
								print "</tr>\n"; 
								$vn_c = 0;
							}
						}
						
						if ($vn_c > 0) {
							print "</tr>\n";
						}
					} else {
						print "<tr><td>"._t('No sets are available')."</td></tr>\n";
					}
				?>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
	</div>
</div>
			
<script type="text/javascript">
	caUI.initChecklistBundle('#<?php print $vs_id_prefix.$vn_table_num.'_sets'; ?>', {
		fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
		templateValues: ['set_id'],
		initialValues: <?php print json_encode($va_initial_values); ?>,
		initialValueOrder: <?php print json_encode(array_keys($va_initial_values)); ?>,
		errors: <?php print json_encode($va_errors); ?>,
		itemID: '<?php print $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		minRepeats: 0,
		maxRepeats: <?php print sizeof($va_sets); ?>,
		readonly: <?php print $vb_read_only ? "true" : "false"; ?>
	});
</script>