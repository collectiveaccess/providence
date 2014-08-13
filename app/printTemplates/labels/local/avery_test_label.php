<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/local/avery_test_label.php
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
 * @name Mollusc Test Label
 * @type label
 * @pageSize A4
 * @pageOrientation portrait
 * @tables ca_objects,ca_occurrences,ca_entities
 * @marginLeft 10mm
 * @marginRight 10mm
 * @marginTop 10mm
 * @marginBottom 10mm
 * @horizontalGutter 0in
 * @verticalGutter 0.25in
 * @labelWidth 35mm
 * @labelHeight 12mm
 * 
 * ----------------------------------------------------------------------
 */
 
 	$vo_result = $this->getVar('result');	
 ?>


 <div class="titleText" style="position: absolute; left: 3mm; top: 3mm; ">
 	WESTERN AUSTRALIAN MUSEUM<br />
 	{{{<ifdef code="ca_objects.scientificNameAuthorship">(^ca_objects.scientificNameAuthorship)<br /></ifdef>}}}
 	<strong>WAM</strong> {{{<ifdef code="ca_objects.idno">(^ca_objects.idno)<br /></ifdef>}}}	
 	{{{<ifdef code="ca_occurrences.locality"><div class="smallText">^ca_occurrences.locality</div><br /></ifdef>}}}
 	<div class="smallText">GPS - need to get GPS from related occurrence</div><br />
 	<div class="smallText">STATION - find station</div><br />
 	{{{<ifdef code="ca_entities.ClientName"><div class="smallText">Collector: ^ca_entities.ClientName</div><br /></ifdef>}}}
 	{{{<ifdef code="ca_objects.eventDate"><div class="smallText">Date: ^ca_objects.eventDate</div></ifdef>}}} {{{<ifdef code="ca_occurrences.minimumDepthInMeters"><div class="smallText">^ca_occurrences.minimumDepthInMeters</div></ifdef>}}}{{{<ifdef code="ca_occurrences.maximumDepthInMeters"><div class="smallText"> to ^ca_occurrences.maximumDepthInMeters</div><br ></ifdef>}}}
 	{{{<ifdef code="ca_objects.identifiedBy"><div class="smallText">Det. by ^ca_objects.identifiedBy</div></ifdef>}}} {{{<ifdef code="ca_objects.dateIdentified"><div class="smallText">Date Det. ^ca_objects.dateIdentified</div></ifdef>}}}
 	
 </div>
 
 <!--<div class="barcode" style="position: absolute; left: 0.125in; top: 1.5in; width: 1.5in; height: 0.75in;">
 	{{{barcode:qrcode:5:^ca_objects.idno}}}
 </div>
 
 <div class="bodyText" style="position: absolute; left: 0.125in; top: 3in; width: 3.5in; height: 0.375in;">
 	{{{^ca_objects.preferred_labels.name <ifdef code="ca_objects.idno">(^ca_objects.idno)</ifdef>}}}
 </div>
 
 <div class="thumbnail" style="position: absolute; right: 0.125in; top: 0.125in; overflow: hidden;">-->
 	<?php //print $vo_result->get('ca_object_representations.media.thumbnail'); ?>
  <!--</div>-->