<?php
/** ---------------------------------------------------------------------
 * views/mailTemplates/mfa_email_code.tpl : login code sent when user doesn't have their authenticator app
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
?>
<p><?= _t("Your login code is:"); ?></p>
<p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;"><?= htmlspecialchars((string)$this->getVar('code')); ?></p>
<p><?= _t("It can be used once and expires in %1 minutes.", (int)$this->getVar('minutes')); ?></p>
<p><?= _t("This code was requested after your password was entered correctly (IP address %1). If this wasn't you, change your password and contact your %3 administrator at %2.", htmlspecialchars((string)$this->getVar('ip')), __CA_ADMIN_EMAIL__, __CA_APP_DISPLAY_NAME__); ?></p>
