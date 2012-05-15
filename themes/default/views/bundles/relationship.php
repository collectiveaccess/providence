<?php
/* ----------------------------------------------------------------------
 * bundles/relationship.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
    $vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
    $t_item 			= $this->getVar('t_item');
    $t_item_rel 		= $this->getVar('t_item_rel');
    $t_subject 			= $this->getVar('t_subject');            // object
    $display_field 		= $this->getVar('label_display_field');
    $pa_options 		= $this->getVar('pa_options');
    $rel_types 			= $this->getVar('relationship_types_by_sub_type');
    
    $RefOnly = null;
    if(array_key_exists('RefOnly',$pa_options)) {
		if($pa_options['RefOnly']>0) $RefOnly = $pa_options['RefOnly'];
    }
    if($t_item) {
    	$item_table_name = $t_item->tableName();
		$item_table_num = $t_item->tableNum();
    	$item_primary_key = "{".$t_item->primaryKey()."}";
    }
  
    if(count($rel_types)>0) {
	    //define the rel_type_id
	    foreach($rel_types as $rel_type) {
	        if($rel_type['type_code'] == $pa_options['RelType']) {
	                $rel_type_id = $rel_type['type_id'];
	                $sub_type_right_id = $rel_type['sub_type_right_id'];
	                break;
	        }
	    }
    }
    //to get the appropriate screen type in the editor url
    $type_id_url_append = '/type_id/'.$sub_type_right_id;

    // limit initial values to rel_type_id
    $initialValues = array();
    foreach($this->getVar('initialValues') as $initialValue) {
        if($initialValue['relationship_type_id'] == $rel_type_id) {
                $initialValues[] = $initialValue;
        }
    }
?>
<div id="<?php print $vs_id_prefix.$item_table_num.'_rel'; ?>">
<?php
        //
        // The bundle template - used to generate each bundle in the form
        //
?>
        <textarea class='caItemTemplate' style='display: none;'>
                <div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
                        <table class="caListItem">
                                <tr>
                                        <td>
<input type="text" size="40" value="Type the first few characters here"
        <?php if($RefOnly) {
		print " readonly='readonly' ";
		print " name='".$vs_id_prefix."_readonly{n}' "; 
		print " value='{{".$display_field."}}' "; 
		print " id='".$vs_id_prefix."_readonly{n}'/>";
	} else {
		print " name='".$vs_id_prefix."_autocomplete_{n}' ";
        	print " value='{{".$display_field."}}' ";
        	print " id='".$vs_id_prefix."_autocomplete{n}'/>";
	} ?>
</td>
                                        <td>
                                        <input type="hidden" name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}"  value="<?php print $rel_type_id; ?>" />
                                        <input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
                                        </td>
                                        <td>
<?php if(!$RefOnly) { ?>
                                                <a href="#" class="caDeleteItemButton"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
<?php } ?>
                                                <a href="<?php print urldecode(caEditorUrl($this->request, $item_table_name, $item_primary_key)).$type_id_url_append?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_GO__); ?></a>

                                        </td>
                                </tr>

                        </table>
                </div>
        </textarea>

        <div class="bundleContainer">
                <div class="caItemList">

                </div>
<?php if(!$RefOnly) { ?>
                <div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add " ); ?></a></div>
<?php } ?>
        </div>
</div>
			
<script type="text/javascript">
	jQuery(document).ready(function() {
        caUI.initRelationBundle('#<?php print $vs_id_prefix.$item_table_num.'_rel'; ?>', {
                fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
                templateValues: ['<?php print $display_field?>', 'type_id', 'id'],
                initialValues: <?php print json_encode($initialValues); ?>,
                itemID: '<?php print $vs_id_prefix; ?>Item_',
                templateClassName: 'caItemTemplate',
                itemListClassName: 'caItemList',
                addButtonClassName: 'caAddItemButton',
                deleteButtonClassName: 'caDeleteItemButton',
                showEmptyFormsOnLoad: 1,
                relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
                autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Relation', 'Get', array('element'=>$pa_options['element_id'],'item'=>$item_table_name)); ?>'
        });
    });
</script>



