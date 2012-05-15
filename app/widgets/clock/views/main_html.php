<?php
/* ----------------------------------------------------------------------
 * app/widgets/clock/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
 	$po_request 			= $this->getVar('request');
	$va_instances			= $this->getVar('instances');
	$va_settings				= $this->getVar('settings');
	$vs_widget_id 			= $this->getVar('widget_id');
	
?>
<link media="screen" rel="stylesheet" type="text/css" href="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/stylesheet/jquery.epiclock.css"/>
<link media="screen" rel="stylesheet" type="text/css" href="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/renderers/retro/epiclock.retro.css"/>
<link media="screen" rel="stylesheet" type="text/css" href="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/renderers/retro-countdown/epiclock.retro-countdown.css"/>

<script type="text/javascript" src="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/javascript/jquery.dateformat.js"></script>
<script type="text/javascript" src="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/javascript/jquery.epiclock.js"></script>
<script type="text/javascript" src="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/renderers/retro/epiclock.retro.js"></script>
<script type="text/javascript" src="<?php print __CA_URL_ROOT__; ?>/app/widgets/clock/epiclock/renderers/retro-countdown/epiclock.retro-countdown.js"></script>

<div class="dashboardWidgetContentContainer">
	<div id="caClock<?php print $vs_widget_id; ?>" class="dashboardWidgetClock">
	
	</div>
</div>
<script type="text/javascript">
	 jQuery('#caClock<?php print $vs_widget_id; ?>').epiclock({format: '<?php print $va_settings['display_format']; ?>', renderer: '<?php print $va_settings['display_mode']; ?>'});
</script>
