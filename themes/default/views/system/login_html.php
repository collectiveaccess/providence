<?php
/* ----------------------------------------------------------------------
 * app/views/system/login_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2026 Whirl-i-Gig
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
	$notifications = $this->getVar('notifications');
?>
<div class="row">
	<div class="col-md-12 col-lg-8 offset-lg-2 pb-2">
		<H2><?= $this->request->config->get("app_display_name"); ?></H2>
	</div>
</div>
<div class="row">
	<div class="col-md-12 col-lg-8 offset-lg-2">
<?php
		if ($notifications = $this->getVar('notifications')) {  
?>
			<p class="notificationContent"><?php foreach($notifications as $notification) { print $notification['message']."<br/>\n"; }; ?></p>
<?php
		}
?>
		<form id="LoginForm" action="<?= caNavUrl('*', '*', 'DoLogin'); ?>" class="form-horizontal needs-validation" method="POST" novalidate>
			<div class="row">
				<div class="col-md-12 col-lg-12">			
					<div class="bg-light px-4 pt-4 pb-2 mb-4">
						<div class="row">
							<div class="col mb-4">
								<label for="username" class="form-label"><?= _t("Username"); ?></label>
								<input type="text" class="form-control" id="username" name="username" autocomplete="off" required/>
								<div class="invalid-feedback"><?= _t('Please enter your username'); ?></div>
							</div>
						</div>
						<div class="row">
							<div class="col mb-4">
								<label for="password" class="form-label"><?= _t("Password"); ?></label>
								<input type="password" name="password" class="form-control" id="password" autocomplete="off" required/>
								<div class="invalid-feedback"><?_t('Please enter your password'); ?></div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col mb-4">
							<button type="submit" class="btn btn-primary"><?= _t("Login"); ?></button>
						</div>
					</div>
				</div>
			</div>
			
			<input type="hidden" name="csrfToken" value="<?= caGenerateCSRFToken($this->request); ?>"/>
		</form>
	</div>
</div>
<script>
(() => {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  const forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }

      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
