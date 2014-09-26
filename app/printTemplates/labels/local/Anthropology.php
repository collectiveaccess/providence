<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/local/Anthropology.php
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
 * @name Cultural Heritage Large
 * @type label
 * @pageSize a4
 * @pageOrientation landscape
 * @tables ca_objects
 * @marginLeft 10mm
 * @marginRight 10mm
 * @marginTop 10mm
 * @marginBottom 10mm
 * @horizontalGutter 0in
 * @verticalGutter 0.25in
 * @labelWidth 110mm
 * @labelHeight 65mm
 * 
 * ----------------------------------------------------------------------
 */

 	$vo_result = $this->getVar('result');	
 ?>



 <div class="labelContainer labelAntropLarge" >
 	<div class="labelAnthropImage">
 		<?php print $vo_result->get('ca_object_representations.media.thumbnail'); ?>
 	</div>
 	<div class="labelAnthropDetails">
 				
 		<ul>
		 	<li>Reg No:<strong>{{{<ifdef code="ca_objects.idno">(^ca_objects.idno)</ifdef>}}}</strong></li>
		 	<li>Category: {{{<ifdef code="ca_objects.dcSubject">^ca_objects.dcSubject</ifdef>}}}</li>
		 	<li>Item: TITLE</li>
		 	<li>Donor:{{{ ^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=donor%delimiter=_ }}}</li>
		 	<li class="left">Date: {{{<ifdef code="ca_occurrences.eventDate"> ^ca_occurrences.eventDate</ifdef>}}}</li>
	 		<li class="right">Date Registered:{{{<ifdef code="ca_occurrences.dateIdentified">^ca_occurrences.dateIdentified</ifdef>}}}</li>
 		</ul>
 	</div>
 </div>
 