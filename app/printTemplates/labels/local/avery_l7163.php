<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/local/avery_l7163.php
 * ----------------------------------------------------------------------
  * Template configuration:
 *
 * @name Avery L7163 14 to a page
 * @type label
 * @pageSize a4
 * @pageOrientation portrait
 * @tables ca_objects
 * @marginLeft 4.7mm
 * @marginRight 4.7mm
 * @marginTop 15.2mm
 * @marginBottom 6mm
 * @horizontalGutter 0.1mm
 * @verticalGutter 2.5mm
 * @labelWidth 98mm
 * @labelHeight 38mm
 * 
 * ----------------------------------------------------------------------
 */

$vo_result = $this->getVar('result');
?>

{{{
<div class="titleText">^ca_objects.preferred_labels
	<ifdef code="ca_objects.idno">^ca_objects.idno</ifdef>
</div>

<ifdef code="ca_objects.Description">
	<div class="description bodyText margin">^ca_objects.Description</div>
</ifdef>
<ifdef code="ca_entities" restrictToRelationshipTypes="donor">
	<div class="bodyText margin">Source/Donor: ^ca_entities</div>
</ifdef>
<ifdef code="ca_list_items" restrictToRelationshipTypes="described">
	<div class="bodyText left">^ca_list_item_labels.hierarchy%maxLevelsFromTop=2</div>
</ifdef>
}}}
<div class="right">
	{{{barcode:code128:12:^ca_objects.idno}}}
	<div style="text-align: center" class="smallText">{{{^ca_objects.idno}}}</div>
</div>
<div class="clear"></div>


