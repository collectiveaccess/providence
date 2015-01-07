<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/avery_8164.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name Avery 8164
 * @type label
 * @pageSize letter
 * @pageOrientation portrait
 * @tables ca_objects
 * @marginLeft 0.125in
 * @marginRight 0.125in
 * @marginTop 0.25in
 * @marginBottom 0.25in
 * @horizontalGutter 0in
 * @verticalGutter 0.25in
 * @labelWidth 4in
 * @labelHeight 3.375in
 * 
 * ----------------------------------------------------------------------
 */
 
 	$vo_result = $this->getVar('result');	
 ?>
 <div class="titleText" style="position: absolute; left: 0.125in; top: 0.125in; width: 2in; height: 3.125in; overflow: hidden; font-size:8px; font-weight:normal;">
<?php
	print '<div style="font-size:10px;"><b>';
	if(strlen($vo_result->get("ca_objects.preferred_labels.name")) > 75){
		print substr($vo_result->get("ca_objects.preferred_labels.name"), 0, 75)."...";
	}else{
		print $vo_result->get("ca_objects.preferred_labels.name");
	}
	print ' ('.$vo_result->get("ca_objects.idno").')</b></div>';
	if($vo_result->get("ca_objects.appraisals.appraised_value_low")){
		print "<div><b>Appraisal:</b> ".$vo_result->get("ca_objects.appraisals.appraised_value_low")."</div>";
	}
	if($vs_entities = $vo_result->get("ca_entities", array("delimiter" => ", ", "restrictToRelationshipTypes" => array("creator"), "checkAccess" => $va_access_values))){
		print "<div><b>Creator:</b> ".$vs_entities."</div>";
	}
	if($vo_result->get("ca_objects.creation_date")){
		print "<div><b>Creation date:</b> ".$vo_result->get("ca_objects.creation_date")."</div>";
	}
	if($vo_result->get("ca_objects.dimensions.display_dimensions")){
		print "<div><b>Dimensions:</b> ".$vo_result->get("ca_objects.dimensions.display_dimensions")."</div>";
	}else{
		if($vo_result->get("ca_objects.dimensions.dimensions_height") || $vo_result->get("ca_objects.dimensions.dimensions_width") || $vo_result->get("ca_objects.dimensions.dimensions_depth") || $vo_result->get("ca_objects.dimensions.dimensions_length")){
			print "<div><b>Dimensions:</b> ";
			$va_dimension_pieces = array();
			if($vo_result->get("ca_objects.dimensions.dimensions_height")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions.dimensions_height");
			}
			if($vo_result->get("ca_objects.dimensions.dimensions_width")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions.dimensions_width");
			}
			if($vo_result->get("ca_objects.dimensions.dimensions_depth")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions.dimensions_depth");
			}
			if($vo_result->get("ca_objects.dimensions.dimensions_length")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions.dimensions_length");
			}
			if(sizeof($va_dimension_pieces)){
				print join(" x ", $va_dimension_pieces);
			}
			print "</div>";
		}
	}
	if($vo_result->get("ca_objects.dimensions_frame.display_dimensions_frame")){
		print "<div><b>Dimensions with frame:</b> ".$vo_result->get("ca_objects.dimensions_frame.display_dimensions_frame")."</div>";
	}else{
		if($vo_result->get("ca_objects.dimensions_frame.dimensions_frame_height") || $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_width") || $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_depth") || $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_length")){
			print "<div><b>Dimensions with frame:</b> ";
			$va_dimension_pieces = array();
			if($vo_result->get("ca_objects.dimensions_frame.dimensions_frame_height")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_height");
			}
			if($vo_result->get("ca_objects.dimensions_frame.dimensions_frame_width")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_width");
			}
			if($vo_result->get("ca_objects.dimensions_frame.dimensions_frame_depth")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_depth");
			}
			if($vo_result->get("ca_objects.dimensions_frame.dimensions_frame_length")){
				$va_dimension_pieces[] = $vo_result->get("ca_objects.dimensions_frame.dimensions_frame_length");
			}
			if(sizeof($va_dimension_pieces)){
				print join(" x ", $va_dimension_pieces);
			}
			print "</div>";
		}
	}
	if(trim($vo_result->get("ca_objects.condition.condition_list", array("convertCodesToDisplayText" => true))) || $vo_result->get("ca_objects.condition.condition_notes")){
		print "<b>Condition:</b> ".$vo_result->get("ca_objects.condition.condition_list", array("convertCodesToDisplayText" => true));
		if(trim($vo_result->get("ca_objects.condition.condition_list", array("convertCodesToDisplayText" => true))) && $vo_result->get("ca_objects.condition.condition_notes")){
			print ", ";
		}
		print $vo_result->get("ca_objects.condition.condition_notes");
	}
 ?>
 </div>
 <div class="thumbnail" style="position: absolute; right: 0.125in; top: 0.125in;">
 	<?php print "<img src='".$vo_result->getMediaPath('ca_object_representations.media', 'thumbnail')."'/>"; ?>
 </div>