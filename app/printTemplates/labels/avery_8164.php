<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/labels/avery_8164.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2018 Whirl-i-Gig
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
 <div class="titleText" style="position: absolute; left: 0.125in; top: 0.125in; width: 1.75in; height: 1.5in; overflow: hidden;">
 	{{{<ifdef code="ca_objects.idno">(^ca_objects.idno)</ifdef>}}}
 	
 	{{{<ifdef code="ca_objects.description"><div class="smallText">^ca_objects.description</div></ifdef>}}}
 </div>
 
 <div class="barcode" style="position: absolute; left: 0.125in; top: 2.5in; width: 1.5in; height: 0.75in;">
 	{{{barcode:code128:5:^ca_objects.idno}}}
 </div>
 
 <div class="thumbnail" style="position: absolute; left: 2in; top: 0.125in; width: 1.875in; overflow: hidden;">
 	<?php print $vo_result->get('ca_object_representations.media.thumbnail', ['usePath' => true]); ?>
 </div>
 
 <div class="bodyText" style="position: absolute; left: 0.125in; top: 3in; width: 2.0in; height: 0.375in;">
 	{{{^ca_objects.preferred_labels.name <ifdef code="ca_objects.idno">(^ca_objects.idno)</ifdef>}}}
 </div>
