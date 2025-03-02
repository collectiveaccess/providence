<?php
/** ---------------------------------------------------------------------
 * views/mailTemplates/forgot_password.tpl
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
 * @package CollectiveAccess
 * @subpackage Auth
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

	$vs_token = $this->getVar('password_reset_token');
	$vs_username = $this->getVar('user_name');
	$vs_site_host = $this->getVar('site_host');

	// this *should* work for all setups, right?
	$vs_nav_url = $vs_site_host.caNavUrl($this->request, 'system', 'auth', 'initreset', array('username' => $vs_username, 'token' => $vs_token ));
?>
<p><?php print _t("We've received a request to reset the password for user name %1.", $vs_username); ?></p>
<p><?php print _t("Please click here or copy the link to the address bar of your web browser to set a new password: "); print $vs_nav_url; ?></p>
<p><?php print _t("If you did not attempt this action, please change your password immediately and/or contact your CollectiveAccess system administrator at %1", __CA_ADMIN_EMAIL__); ?></p>
