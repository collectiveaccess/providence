<?php
/* ----------------------------------------------------------------------
 * app/plugins/vimeo/views/auth_html.php : 
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
	$va_token = $this->getVar('token');
 
 	if($this->getVar('had_stored_token')){
 		switch($va_token['type']){
 			case 'access':
?>
				<h2><?php print _t('Vimeo integration is set up and ready to go!'); ?></h2>
<?php
 				break;
 			case 'request':
 			default:
?>
				<div><?php print _t("If you authorized our app on the Vimeo page, please enter the verification code here."); ?></div>
<?php
				print caFormTag($this->request, 'verify', 'vimeo_verify_form', 'vimeo/Auth');
 				print caHTMLTextInput('verify_code');
 				print caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t("Submit"), 'vimeo_verify_form');
 				
?>
				</form>
				<div><?php print _t('Here is the link to the Vimeo authorization page again: %1','<a href="'.$this->getVar('authorize_link').'" target="_blank">Vimeo</a>'); ?></div>
<?php

 				break;

 		}
 	} else {
?>
		<div><?php print _t('You need to go to the Vimeo page and authorize our app to use your account.'); ?></div>
		<div><?php print _t('Please click here: %1','<a href="'.$this->getVar('authorize_link').'" target="_blank">Vimeo</a>'); ?></div>
		<div><?php print caNavButton($this->request, __CA_NAV_BUTTON_GO__, _t("I'm done"), 'vimeo', 'Auth', 'Index'); ?></div>
<?php
 	}
	
?>
