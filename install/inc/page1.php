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
 * Copyright 2009-2025 Whirl-i-Gig
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

require_once(__CA_APP_DIR__.'/lib/ConfigurationCheck.php');

$o_config = Configuration::load();
?>

<div id='box'>
	<div id="logo"><?= caGetLoginLogo(); ?></div><!-- end logo -->
	<div id="content">
	<H1>
		<?= _t('Version %1 installer (XML)', constant('__CollectiveAccess__')); ?>
	</H1>
<?php
	// Check for configuration issues
	ConfigurationCheck::performInstall();
	
	if (ConfigurationCheck::foundErrors()) {
		ConfigurationCheck::renderInstallErrorsAsHTMLOutput();
	} elseif(($vo_installer = new \Installer\Installer("profiles/", '', '', false, false)) && $vo_installer->isAlreadyInstalled()
			&& (!__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__)
	) {
?>
	<div class="permissionError">
		<?= caNavIcon(__CA_NAV_ICON_ALERT__ , 2, ['class' => 'permissionErrorIcon']); ?>
		<?= _t("Cannot install: an existing installation has been detected (%1)", '<a href="../">'._t("login").'</a>'); ?>
		<div style='clear:both; height:1px;'><!-- empty --></div>
	</div>
<?php
	} else {
?>
	<p>
		<?= _t("This installer will have your installation of CollectiveAccess ready to use in just a few minutes. 
		Before you run the installer make sure the settings in your <i>setup.php</i> file are correct. 
		The installer will test your database connection, install the database schema and load default values into the newly established database. 
		It will also establish an administrator's login for you to use to access your new system."); ?>
	</p>
	<div id="installForm">
		<form action='index.php' name='page1form' id='page1form'>
			<div class='formItem'>
<?php	
				if(sizeof($va_errors)) { 
					print "<div class='contentError'> ".caNavIcon(__CA_NAV_ICON_ALERT__ , 1, ['class' => 'permissionErrorIcon'])._t('Please enter a valid email address')."</div>\n"; 
				}
?>
				<?=_t("Administrator's e-mail address"); ?>:<br/>
				<input type='text' name='email' value='<?= htmlspecialchars($ps_email, ENT_QUOTES, 'UTF-8'); ?>' size='40' maxlength='100'/>
			</div><!-- end formItem -->
			<div class='formItem'><?= _t("Installation profile"); ?>:<br/>
				<div id="profileChooser">
<?php
					print caHTMLSelect('profile', caGetAvailableProfiles(), array('id' => 'profileSelect'), array('value' => $ps_profile));

			if (defined('__CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__') && __CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__) {
?>
				<div id="batchProcessingTableProgressGroup" style="display: none;">
					<div class="batchProcessingStatus"><span id="batchProcessingTableStatus" > </span></div>
					<div id="progressbar"></div>
				</div>
				<div id="profileUpload" style="border: 2px dashed #999999; text-align: center; padding: 20px; margin-top: 10px;">
					<span style="font-size: 20px; color: #aaaaaa; font-weight: bold;"><?= _t("Drag profiles here to add or update"); ?></span>
				</div>
<?php
			}
?>
					<div class='profileNotes'>
<?php
						_p('More information about standard installation profiles is available in the CollectiveAccess <a href="https://docs.collectiveaccess.org" target="_blank">manual</a>.');
?>
					</div>
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
<?php
			}
?>
				
				<div class="loginSubmitButton"><a href='#' onclick='jQuery("#page1form").submit();' class='form-button'><span class='form-button'><i class="form-button-left fa fa-check-circle-o fa-2x" style='padding-right: 10px;'></i> <?= _t('Begin installation'); ?></span></a></div>
				<input type='hidden' name='page' value='2'/>
			</form>
		</div><!-- end installForm -->
<?php
	}
?>
</div><!-- end content --></div><!-- end box -->

<?php
if (defined('__CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__') && __CA_ALLOW_DRAG_AND_DROP_PROFILE_UPLOAD_IN_INSTALLER__) {
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#progressbar').progressbar({ value: 0 });
		
		jQuery('#profileUpload').fileupload({
			dataType: 'json',
			url: 'index.php?action=profileUpload',
			dropZone: jQuery('#profileUpload'),
			singleFileUploads: false,
			done: function (e, data) {
				if (data.result.error) {
					jQuery("#batchProcessingTableProgressGroup").show(250);
					jQuery("#batchProcessingTableStatus").html(data.result.error);
					setTimeout(function() {
						jQuery("#batchProcessingTableProgressGroup").hide(250);
					}, 3000);
				} else {
					var msg = [];
					
					if (data.result.uploadMessage) {
						msg.push(data.result.uploadMessage);
					}
					if (data.result.skippedMessage) {
						msg.push(data.result.skippedMessage);
					}
					jQuery("#batchProcessingTableStatus").html(msg.join('; '));
					setTimeout(function() {
							jQuery("#batchProcessingTableProgressGroup").hide(250);
							jQuery("#profileUpload").show(150);
						}, 3000);
				}
				// reload and select profile in profile drop-down here
				jQuery("#profileSelect").empty();
				jQuery.each(data.result.profiles, function(k, v) {
					if (typeof v !== 'string') { return; }
				  var s = (data.result.added && data.result.added.indexOf(v) >= 0) ? 'SELECTED="1"' : '';
				  jQuery("#profileSelect").append(jQuery("<option " + s + "></option>")
					 .attr("value", v).text(k));
				});
				
			},
			progressall: function (e, data) {
				jQuery("#profileUpload").hide(150);
				if (jQuery("#batchProcessingTableProgressGroup").css('display') == 'none') {
					jQuery("#batchProcessingTableProgressGroup").show(250);
				}
				var progress = parseInt(data.loaded / data.total * 100, 10);
				jQuery('#progressbar').progressbar("value", progress);
			
				var msg = "<?= _t("Progress: "); ?>%1";
				jQuery("#batchProcessingTableStatus").html(msg.replace("%1", "(" + progress + "%)"));
				
			}
		});
	});
</script>
<?php
}
