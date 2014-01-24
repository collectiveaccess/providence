<?php
/* ----------------------------------------------------------------------
 * app/widgets/links/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
 
 	$po_request			= $this->getVar('request');
?>

<div class="dashboardWidgetContentContainer">
	<ul>
		<li><a href="http://www.collectiveaccess.org"><?php print _t("Project website"); ?>: http://www.collectiveaccess.org</a></li>
		<li><a href="http://docs.collectiveaccess.org"><?php print _t("Documentation"); ?>: http://docs.collectiveaccess.org</a></li>
		<li><a href="http://www.collectiveaccess.org/forum/"><?php print _t("Forum"); ?>: http://www.collectiveaccess.org/support/forum/</a></li>
	</ul>
</div>
