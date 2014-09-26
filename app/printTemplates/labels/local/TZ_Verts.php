<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/local/TZ_Verts.php
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
 * @name TZ Verts Label
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
 * @labelWidth 70mm
 * @labelHeight 40mm
 * 
 * ----------------------------------------------------------------------
 */

 	$vo_result = $this->getVar('result');	
 ?>



 <div class="labelContainer labelAZlarge" >
 	<div class="labelHeading">
 		<p>
 			Western Australian Museum<br />			
 			<strong>Family<br />
 			{{{ ^ca_list_items.preferred_labels%restrictToRelationshipTypes=identification }}}</strong>
 		</p>
 	</div>
 	<div class="labelRegNo">
 		<p>
 			<strong>WAM</strong> {{{<ifdef code="ca_objects.idno">(^ca_objects.idno)</ifdef>}}}
 		</p>	
 	</div>
 	<div class="labelDetails">
 		<ul>
		 	<li>Locality: {{{<ifdef code="ca_objects.locality">^ca_objects.locality</ifdef>}}}</li>
		 	<li>GPS {{{ ^ca_objects.verbatimLatitude }}} {{{ ^ca_objects.verbatimLongitude }}}</li>
		 	<li>&nbsp;</li>
		 	<li>Collector:{{{ ^ca_entities.preferred_labels.displayname%restrictToRelationshipTypes=discover%delimiter=_ }}}</li>
		 	<li>Date: {{{<ifdef code="ca_objects.eventDate"> ^ca_objects.eventDate</ifdef>}}}</li>
	 		<li class="right">Sex {{{<ifdef code="ca_objects.sex"> ^ca_objects.sex</ifdef>}}}</li> 
	 		<li>Det. by {{{<ifdef code="ca_objects.identifiedBy"> ^ca_objects.identifiedBy</ifdef>}}}</li>
 		</ul>
 	</div>
 </div>
 