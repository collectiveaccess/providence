<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/local/ArachnidsReg.php
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
 * @name TZ Inverts Reg Only
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
 * @labelWidth 50mm
 * @labelHeight 20mm
 * 
 * ----------------------------------------------------------------------
 */

 	$vo_result = $this->getVar('result');	
 ?>

 <div class="labelContainer labelArachnids" >
 	<div class="labelRegNo">
 		<p>
 			WA MUSEUM ARACHNOLOGY<br />
 			<span class="regNo">{{{<ifdef code="ca_objects.idno">(^ca_objects.idno)</ifdef>}}}</span>
 		</p>	
 	</div>
 </div>
 