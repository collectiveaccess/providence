<?php
/* ----------------------------------------------------------------------
 * install/inc/page1.php : Page 1 of CollectiveAccess application installer
 *
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
	if (!constant('__CollectiveAccess_Installer__')) { die("Cannot run"); }
	
	require_once(__CA_APP_DIR__.'/lib/ca/ConfigurationCheck.php');
	
	$o_config = Configuration::load();
?>

<div id='box'>
	<div id="logo"><img src="<?php print $vs_url_path; ?>/graphics/ca_logo.png"/></div><!-- end logo -->
	<div id="content">
	<H1>
		<?php _p('Version %1 installer (XML)', constant('__CollectiveAccess__')); ?>
	</H1>
<?php
	// Check for configuration issues
	ConfigurationCheck::performInstall();
	
	if (ConfigurationCheck::foundErrors()) {
		ConfigurationCheck::renderInstallErrorsAsHTMLOutput();
	} else {
?>
	
	<p>
		<?php _p("This installer will have your installation of CollectiveAccess ready to use in just a few minutes. 
		Before you run the installer make sure the settings in your <i>setup.php</i> file are correct. 
		The installer will test your database connection, install the database schema and load default values into the newly established database. 
		It will also establish an administrator's login for you to use to access your new system."); ?>
	</p>
	<div id="installForm">
		<form action='index.php' name='page1form' id='page1form'>
			<div class='formItem'>
<?php	
						if(sizeof($va_errors)) { 
							print "<div class='contentError'><img src='{$vs_url_path}/graphics/warning.gif'/> "._t('Please enter a valid email address')."</div>\n"; 
						}
?>
				<?php _p("Administrator's e-mail address"); ?>:<br/>
				<input type='text' name='email' value='<?php print htmlspecialchars($ps_email, ENT_QUOTES, 'UTF-8'); ?>' size='40' maxlength='100'/>
			</div><!-- end formItem -->
			<div class='formItem'><?php _p("Installation profile"); ?>:<br/>
<?php
				print caHTMLSelect('profile', caGetAvailableXMLProfiles(), array(), array('value' => $ps_profile));
?>
				<div class='profileNotes'>
<?php
					_p('More information about standard installation profiles is available on the CollectiveAccess <a href="http://docs.collectiveaccess.org/wiki/Installation_profile" target="_blank">project wiki</a>.');
?>
					<br/><br/>
<?php
					_p('Don\'t see a suitable profile? Browse our <a href="http://www.CollectiveAccess.org/configuration" target="_blank">installation profile library</a> for additional configurations developed by the CollectiveAccess user community. To install a new profile simply copy the file into the <i>install/profiles/xml</i> directory on your server and reload the installer in your web browser.');
?>
				</div>
			</div><!-- end formItem -->
<?php
			if (defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__') && __CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__) {
?>
				<div class='formItem'>
<?php
							print caHTMLCheckboxInput('overwrite', array('value' => 1))." "._p("Overwrite existing installation?");
?>
				</div><!-- end formItem -->
				<div class='formItem'>
<?php
							//print caHTMLCheckboxInput('debug', array('value' => 1))." "._p("Output debugging information?");
?>
				</div><!-- end formItem -->
<?php
			}
?>
				
				<div class="loginSubmitButton"><a href='#' onclick='jQuery("#page1form").submit();' class='form-button'><span class='form-button'><img src='<?php print $vs_url_path; ?>/graphics/login.gif' border='0' alt='<?php _p('Begin installation'); ?>' class='form-button-left' style='padding-right: 10px;'/> <?php _p('Begin installation'); ?></span></a></div>
				<input type='hidden' name='page' value='2'/>
			</form>
		</div><!-- end installForm -->
<?php
	}
?>
</div><!-- end content --></div><!-- end box -->
