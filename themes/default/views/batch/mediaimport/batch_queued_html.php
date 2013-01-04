<?php
/* ----------------------------------------------------------------------
 * batch/batch_queued_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
	JavascriptLoadManager::register("sortableUI");
?>
<h1><?php print _t('Media import queued for background processing'); ?></h1>

<div class="batchProcessingHelpText">
<?php 
	print _t('Your media import has been queued and will be run shortly. You may continue to work while the import is processed.'); 
	
	if ((bool)$this->request->getParameter('send_email_when_done', pInteger) && (bool)$this->request->getParameter('send_sms_when_done', pInteger)) {
		print ' '._t('You will receive email and SMS text messages when processing is complete.');
	} else {
		if ((bool)$this->request->getParameter('send_email_when_done', pInteger)) {
			print ' '._t('You will receive an email when processing is complete.');
		}
		if ((bool)$this->request->getParameter('send_sms_when_done', pInteger)) {
			print ' '._t('You will receive an SMS text message when processing is complete.');
		}
	}
?>
	
</div>