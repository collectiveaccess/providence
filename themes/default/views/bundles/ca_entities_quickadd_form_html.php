<?php
	$t_label = new ca_entity_labels();
?>
			<table class="objectRepresentationListItem">
				<tr valign="middle">
					<td>
						<table>
							<tr>
								<td>
									<?php print $t_label->htmlFormElement('prefix', null, array('name' => "{fieldNamePrefix}prefix_{n}", 'id' => "{fieldNamePrefix}prefix_{n}", "value" => "{{prefix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('forename', null, array('name' => "{fieldNamePrefix}forename_{n}", 'id' => "{fieldNamePrefix}forename_{n}", "value" => "{{forename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('middlename', null, array('name' => "{fieldNamePrefix}middlename_{n}", 'id' => "{fieldNamePrefix}middlename_{n}", "value" => "{{middlename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('surname', null, array('name' => "{fieldNamePrefix}surname_{n}", 'id' => "{fieldNamePrefix}surname_{n}", "value" => "{{surname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('suffix', null, array('name' => "{fieldNamePrefix}suffix_{n}", 'id' => "{fieldNamePrefix}suffix_{n}", "value" => "{{suffix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?php print '<div class="formLabel">'.$t_label->htmlFormElement('locale_id', "^LABEL ^ELEMENT", array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}locale_id_{n}", 'name' => "{fieldNamePrefix}locale_id_{n}", "value" => "{locale_id}", 'no_tooltips' => true, 'dont_show_null_value' => true, 'hide_select_if_only_one_option' => true, 'WHERE' => array('(dont_use_for_cataloguing = 0)'))); ?>
									<?php print $t_label->htmlFormElement('type_id', "^LABEL ^ELEMENT", array('classname' => 'labelType', 'id' => "{fieldNamePrefix}type_id_{n}", 'name' => "{fieldNamePrefix}type_id_{n}", "value" => "{type_id}", 'no_tooltips' => true, 'list_code' => $this->request->config->get('ca_entities_preferred_label_type_list'), 'dont_show_null_value' => true, 'hide_select_if_no_options' => true)).'</div>'; ?>
								</td>
								<td>
									<?php print $t_label->htmlFormElement('other_forenames', null, array('name' => "{fieldNamePrefix}other_forenames_{n}", 'id' => "{fieldNamePrefix}other_forenames_{n}", "value" => "{{other_forenames}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td colspan="3"><?php print $t_label->htmlFormElement('displayname', null, array('name' => "{fieldNamePrefix}displayname_{n}", 'id' => "{fieldNamePrefix}displayname_{n}", "value" => "{{displayname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred', 'textAreaTagName' => 'textentry', 'readonly' => $vb_read_only)); ?><td>
							<tr>
							<tr>
							
							</tr>
						</table>
					</td>
				</tr>
			</table>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_entity_labels_preferred');
?>