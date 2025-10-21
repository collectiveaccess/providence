<?php
/* ----------------------------------------------------------------------
 * install/inc/page2.php : Page 2 of CollectiveAccess application installer
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

$t_total = new Timer();
$profile_info = \Installer\Installer::getProfileInfo("./profiles", $profile)
?>
<div id='box'>
	<div id="logo"><?= caGetLoginLogo(); ?></div><!-- end logo -->
	<div id="content">
	<H1>
		<?= _t('Installing CollectiveAccess %1', constant('__CollectiveAccess_Version__')); ?>...
	</H1>
	<H2>
		<?= _t('Loading %1', $profile_info['display']); ?>
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
	
	$progress = 0;
	
	// parameters: profile dir, profile name, admin email, overwrite y/n, profile debug mode y/n
	$vo_installer = new \Installer\Installer("profiles/", $profile, $email, $pb_overwrite, $pb_debug);
	
	// if profile validation against XSD failed, we already have an error here
	if($vo_installer->numErrors()){
		caSetMessage("There were errors parsing the profile(s): ".join("; ", array_map(function($v) { return $v['message'] ?? ''; }, $vo_installer->getErrors())));
	} else {
		
		try {
			
			if($vo_installer->isAlreadyInstalled() && (!defined('__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__') || !__CA_ALLOW_INSTALLER_TO_OVERWRITE_EXISTING_INSTALLS__ || !$pb_overwrite)) {
				throw new ApplicationException(_t('Cannot install. Existing CollectiveAccess installation has been detected.'));
			}
			
			caIncrementProgress($progress, _t("Performing preinstall tasks"));
			$vo_installer->performPreInstallTasks();
		
			caIncrementProgress($progress, _t("Loading schema"));
			$vo_installer->loadSchema('caGetTableToBeLoaded');
		
			$progress += 7;
			caIncrementProgress($progress, _t("Processing locales"));
			$vo_installer->processLocales();

			caIncrementProgress($progress, _t("Processing lists"));
			$vo_installer->processLists('caGetListToBeLoaded');

			$progress += 7;
			caIncrementProgress($progress, _t("Processing relationship types"));
			$vo_installer->processRelationshipTypes();

			$progress += 5;
			caIncrementProgress($progress, _t("Processing metadata elements"));
			$vo_installer->processMetadataElements('caGetMetadataElementToBeLoaded');

			$progress += 2;
			caIncrementProgress($progress, _t("Processing metadata dictionary"));
			$vo_installer->processMetadataDictionary();

			$progress += 7;
			caIncrementProgress($progress, _t("Processing access roles"));
			$vo_installer->processRoles();

			$progress += 7;
			caIncrementProgress($progress, _t("Processing user groups"));
			$vo_installer->processGroups();

			$progress += 2;
			caIncrementProgress($progress, _t("Creating logins"));
			$login_info = $vo_installer->processLogins();

			$progress += 7;
			caIncrementProgress($progress, _t("Processing user interfaces"));
			$vo_installer->processUserInterfaces();

			$progress += 7;
			caIncrementProgress($progress, _t("Processing displays"));
			$vo_installer->processDisplays();

			$progress += 7;
			caIncrementProgress($progress, _t("Processing search forms"));
			$vo_installer->processSearchForms();

			$progress += 7;
			caIncrementProgress($progress, _t("Setting up hierarchies"));
			$vo_installer->processMiscHierarchicalSetup();
			
			$progress += 2;
			caIncrementProgress($progress, _t("Processing metadata alerts"));
			$vo_installer->processMetadataAlerts();

			caIncrementProgress($progress, _t("Performing post install tasks"));
			$vo_installer->performPostInstallTasks();

			caIncrementProgress(100, _t("Installation complete"));

			$message = '';
			$time =  _t("(Installation took %1 seconds)", $t_total->getTime(0));
			if($vo_installer->numErrors() || $vo_installer->numWarnings()){
				if (sizeof($errors = array_map(function($e) { return "<li>{$e['stage']}: {$e['message']}</li>"; }, $vo_installer->getErrors()))) {
					$message .= "<div class='contentFailure'><div class='contentFailureHead'>"._t('There were errors during installation:')."</div><ul>".join("", $errors)."</ul></div>";
				}
				if (sizeof($warnings = array_map(function($e) { return "<li>{$e['stage']}: {$e['message']}</li>"; }, $vo_installer->getWarnings()))) {
					$message .= "<div class='contentFailure'><div class='contentFailureHead'>"._t('There were warnings during installation:')."</div><ul>".join("", $warnings)."</ul></div>";
				}
				$message .= "<div class='contentSuccess'>"._t("You can now try to <a href='../index.php?action=login'>login</a> with ");
		
			} else {
				$message .= "<div class='contentSuccess'><span style='font-size:18px;'><b>"._t('Installation was successful!')."</b></span><br/>"._t("You can now <a href='../index.php?action=login'>login</a> with ");
			}
			
			if(!is_array($login_info)) {
				$message .= "<strong>["._t('could not create logins')."]</strong>";
			} elseif (sizeof($login_info) == 1) {
				foreach($login_info as $user_name => $password) {
					$message .= "username <span class='contentHighlight'>{$user_name}</span> and password <span class='contentHighlight'>{$password}</span>.<br/><b>"._t('Make a note of this password!')."</b><br/>";
				}
			} else {
				$message .= " the following logins:<br/>";
				foreach($login_info as $user_name => $password) {
					$message .= _t("Username <span class='contentHighlight'>%1</span> and password <span class='contentHighlight'>%2</span>", $user_name, $password)."<br/>";
				}
				$message .= "<br/><b>"._t('Make note of these passwords!')."</b><br/>";
			}

			$message .= "<span style='font-size:11px;'>{$time}</span></div>";
			caSetMessage($message, true);

		} catch(Exception $e) {
			caSetMessage("<div class='contentFailure'><div class='contentFailureHead'>"._t('There were errors during installation:')."</div><ul><li>".$e->getMessage()."</li></ul></div>");
		}
	}
?>
</div><!-- end content --></div><!-- end box -->
<?php
	function caIncrementProgress($percentage, $message) {
		print "<script type='text/javascript'>jQuery('#progressbar').progressbar('value',{$percentage}); jQuery('#installerLog').html('{$message}');</script>";
		caFlushOutput();
	}
	
	function caSetMessage($message, $pb_resize=false) {
		$message = addslashes($message);
		print "<script type='text/javascript'>jQuery('#installerLog').html('{$message}');";
		if ($pb_resize) {
			print "jQuery('#installerLog').css('overflow', 'visible').css('height', '100%');";
		}
		print "</script>";
		caFlushOutput();
	}
	function caGetTableToBeLoaded($statement, $ltable, $table, $num_tables) {
		global $progress;
		
		$progress += (21/$num_tables);
		
		print "<script type='text/javascript'>jQuery('#progressbar').progressbar('value',{$progress}); jQuery('#installerLog').html(".json_encode(_t('Installing database table for <i>%1</i>', $ltable)).");</script>";
		caFlushOutput();
	}
	function caGetListToBeLoaded($list_code, $list, $num_lists) {
		global $progress;
		
		$progress += (21/$num_lists);
		
		print "<script type='text/javascript'>jQuery('#progressbar').progressbar('value',{$progress}); jQuery('#installerLog').html(".json_encode(_t('Installing list <i>%1</i>', $list_code)).");</script>";
		caFlushOutput();
	}
