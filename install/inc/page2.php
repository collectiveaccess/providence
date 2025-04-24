<?php
/* ----------------------------------------------------------------------
 * install/inc/page2.php : Page 2 of CollectiveAccess application installer
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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

$t_total = new Timer();
$va_profile_info = \Installer\Installer::getProfileInfo("./profiles", $ps_profile)
?>
<div id='box'>
	<div id="logo"><?= caGetLoginLogo(); ?></div><!-- end logo -->
	<div id="content">
	<H1>
		Installing CollectiveAccess <?= constant('__CollectiveAccess__'); ?>...
	</H1>
	<H2>
		Loading <?= $va_profile_info['display']; ?>
	</H2>
	<div id="progressbar" ></div>
	
	<script type="text/javascript">
		jQuery('#progressbar').progressbar({
			value: 0
		});
	</script>
	
	<div id="installerLog" class="installStatus">
		
	</div>
<?php
	
	$vn_progress = 0;
	
	// parameters: profile dir, profile name, admin email, overwrite y/n, profile debug mode y/n
	$vo_installer = new \Installer\Installer("profiles/", $ps_profile, $ps_email, $pb_overwrite, $pb_debug);
	
	// if profile validation against XSD failed, we already have an error here
	if($vo_installer->numErrors()){
		caSetMessage("There were errors parsing the profile(s): ".join("; ", $vo_installer->getErrors()));
	} else {
		
		try {
			
			if($vo_installer->isAlreadyInstalled() && (!defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__') || !__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__ || !$pb_overwrite)) {
				throw new ApplicationException(_t('Cannot install. Existing CollectiveAccess installation has been detected.'));
			}
			
			caIncrementProgress($vn_progress, "Performing preinstall tasks");
			$vo_installer->performPreInstallTasks();
		
			caIncrementProgress($vn_progress, "Loading schema");
			$vo_installer->loadSchema('caGetTableToBeLoaded');
		
			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing locales");
			$vo_installer->processLocales();

			caIncrementProgress($vn_progress, "Processing lists");
			$vo_installer->processLists('caGetListToBeLoaded');

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing relationship types");
			$vo_installer->processRelationshipTypes();

			$vn_progress += 5;
			caIncrementProgress($vn_progress, "Processing metadata elements");
			$vo_installer->processMetadataElements('caGetMetadataElementToBeLoaded');

			$vn_progress += 2;
			caIncrementProgress($vn_progress, "Processing metadata dictionary");
			$vo_installer->processMetadataDictionary();

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing access roles");
			$vo_installer->processRoles();

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing user groups");
			$vo_installer->processGroups();

			$vn_progress += 2;
			caIncrementProgress($vn_progress, "Creating logins");
			$va_login_info = $vo_installer->processLogins();

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing user interfaces");
			$vo_installer->processUserInterfaces();

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing displays");
			$vo_installer->processDisplays();

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Processing search forms");
			$vo_installer->processSearchForms();

			$vn_progress += 7;
			caIncrementProgress($vn_progress, "Setting up hierarchies");
			$vo_installer->processMiscHierarchicalSetup();
			
			$vn_progress += 2;
			caIncrementProgress($vn_progress, "Processing metadata alerts");
			$vo_installer->processMetadataAlerts();

			caIncrementProgress($vn_progress, "Performing post install tasks");
			$vo_installer->performPostInstallTasks();

			caIncrementProgress(100, "Installation complete");

			$vs_message = '';
			$vs_time =  "(Installation took ".$t_total->getTime(0)." seconds)";
			if($vo_installer->numErrors() || $vo_installer->numWarnings()){
				if (sizeof($va_errors = array_map(function($e) { return "<li>{$e['stage']}: {$e['message']}</li>"; }, $vo_installer->getErrors()))) {
					$vs_message .= "<div class='contentFailure'><div class='contentFailureHead'>There were errors during installation:</div><ul>".join("", $va_errors)."</ul></div>";
				}
				if (sizeof($va_warnings = array_map(function($e) { return "<li>{$e['stage']}: {$e['message']}</li>"; }, $vo_installer->getWarnings()))) {
					$vs_message .= "<div class='contentFailure'><div class='contentFailureHead'>There were warnings during installation:</div><ul>".join("", $va_warnings)."</ul></div>";
				}
				$vs_message .= "<div class='contentSuccess'>You can now try to <a href='../index.php?action=login'>login</a> with ";
		
			} else {
				$vs_message .= "<div class='contentSuccess'><span style='font-size:18px;'><b>Installation was successful!</b></span><br/>You can now <a href='../index.php?action=login'>login</a> with ";
			}
			if (sizeof($va_login_info) == 1) {
				foreach($va_login_info as $vs_user_name => $vs_password) {
					$vs_message .= "username <span class='contentHighlight'>{$vs_user_name}</span> and password <span class='contentHighlight'>{$vs_password}</span>.<br/><b>Make a note of this password!</b><br/>";
				}
			} else {
				$vs_message .= " the following logins:<br/>";
				foreach($va_login_info as $vs_user_name => $vs_password) {
					$vs_message .= "Username <span class='contentHighlight'>{$vs_user_name}</span> and password <span class='contentHighlight'>{$vs_password}</span><br/>";
				}
				$vs_message .= "<br/><b>Make note of these passwords!</b><br/>";
			}

			$vs_message .= "<span style='font-size:11px;'>{$vs_time}</span></div>";
			caSetMessage($vs_message, true);

		} catch(Exception $e) {
			caSetMessage("<div class='contentFailure'><div class='contentFailureHead'>There were errors during installation:</div><ul><li>".$e->getMessage()."</li></ul></div>");
		}
	}
?>
</div><!-- end content --></div><!-- end box -->
<?php
	function caIncrementProgress($pn_percentage, $ps_message) {
		print "<script type='text/javascript'>jQuery('#progressbar').progressbar('value',{$pn_percentage}); jQuery('#installerLog').html('{$ps_message}');</script>";
		caFlushOutput();
	}
	
	function caSetMessage($ps_message, $pb_resize=false) {
		$ps_message = addslashes($ps_message);
		print "<script type='text/javascript'>jQuery('#installerLog').html('{$ps_message}');";
		if ($pb_resize) {
			print "jQuery('#installerLog').css('overflow', 'visible').css('height', '100%');";
		}
		print "</script>";
		caFlushOutput();
	}
	function caGetTableToBeLoaded($ps_statement, $ps_table, $pn_table, $pn_num_tables) {
		global $vn_progress;
		
		$vn_progress += (21/$pn_num_tables);
		
		print "<script type='text/javascript'>jQuery('#progressbar').progressbar('value',{$vn_progress}); jQuery('#installerLog').html('Installing database table for <i>{$ps_table}</i>');</script>";
		caFlushOutput();
	}
	function caGetListToBeLoaded($ps_list_code, $pn_list, $pn_num_lists) {
		global $vn_progress;
		
		$vn_progress += (21/$pn_num_lists);
		
		print "<script type='text/javascript'>jQuery('#progressbar').progressbar('value',{$vn_progress}); jQuery('#installerLog').html('Installing list <i>{$ps_list_code}</i>');</script>";
		caFlushOutput();
	}
?>
