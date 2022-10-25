<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/config_check_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2020 Whirl-i-Gig
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

$va_search_config_settings = $this->getVar('search_config_settings');

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caConfigSettingList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Version information'); ?></div>
	</div><div class="clear"></div>
	<table id="caSearchConfigSettingList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Component'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Version'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?= _t('Application version'); ?></td>
				<td><?= __CollectiveAccess__; ?></td>
			</tr>
			<tr>
				<td><?= _t('Schema revision'); ?></td>
				<td><?= __CollectiveAccess_Schema_Rev__; ?></td>
			</tr>
			<tr>
				<td><?= _t('Release type'); ?></td>
				<td><?= __CollectiveAccess_Release_Type__; ?></td>
			</tr>
			<tr>
				<td><?= _t('System GUID'); ?></td>
				<td><?= __CA_SYSTEM_GUID__; ?></td>
			</tr>
			<tr>
				<td><?= _t('Last change log ID'); ?></td>
				<td><?= $this->getVar('last_change_log_id'); ?></td>
			</tr>
			<tr>
				<td><?= _t('PHP version'); ?></td>
				<td><?= caGetPHPVersion()['version']; ?></td>
			</tr>
			<tr>
				<td><?= _t('Operating system'); ?></td>
				<td><?= php_uname(); ?></td>
			</tr>
		</tbody>
	</table>
	
	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Search Engine'); ?>: <?= $this->getVar('search_config_engine_name'); ?></div>
	</div><div class="clear"></div>
	<table id="caSearchConfigSettingList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Setting'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Description'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort"><?= _t('Status'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
while($va_search_config_settings->nextSetting()){
?>
			<tr>
				<td><?= $va_search_config_settings->getCurrentName(); ?></td>
				<td>
				<?= $va_search_config_settings->getCurrentDescription(); ?>
			<?php
			if($va_search_config_settings->getCurrentStatus()!=__CA_SEARCH_CONFIG_OK__){
				print "<br />";
				print "<div class=\"hint\">".$va_search_config_settings->getCurrentHint()."</div>";
			}
			?>
				</td>
				<td>
<?php		if($va_search_config_settings->getCurrentStatus() == __CA_SEARCH_CONFIG_OK__){
				print "<span style=\"color:green\">"._t("ok")."</span>";
			} else if($va_search_config_settings->getCurrentStatus() == __CA_SEARCH_CONFIG_WARNING__) {
				print "<span style=\"color:GoldenRod;\">"._t("Warning")."</span>";
			} else {
				print "<span style=\"color:red;text-decoration:underline;\">"._t("NOT OK!")."</span>";
			}
?>
				</td>
			</tr>
<?php
}
?>
		</tbody>
	</table>
<?php
	$va_general_config_errors = $this->getVar("configuration_check_errors");
	if(is_array($va_general_config_errors) && sizeof($va_general_config_errors)>0) {
?>
	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t("General configuration issues"); ?></div>
	</div><div class="clear"></div>
	<table id="caGeneralConfigIssueList" class="listtable">
		<thead>
			<tr>
				<th class="{sorter: false} list-header-nosort">
				</th>
				<th class="{sorter: false} list-header-nosort">
					<?= _t('Issue'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
		foreach($va_general_config_errors as $vs_error){
?>
			<tr>
				<td><?= caNavIcon(__CA_NAV_ICON_ALERT__, 2); ?></td>
				<td><?= $vs_error; ?></td>
			</tr>
<?php
		}
?>
		</tbody>
	</table>
<?php
	}
?>
	
	
	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Media Processing Plugins'); ?></div>
	</div><div class="clear"></div>
	
	<table id="caMediaConfigPluginList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Plugin'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Info'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort"><?= _t('Status'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
$va_plugins = $this->getVar('media_config_plugin_list');
foreach($va_plugins as $vs_plugin_name => $va_plugin_info){
?>
			<tr>
				<td><?= $vs_plugin_name; ?></td>
				<td><?php 
					print $va_plugin_info['description']; 
					if (is_array($va_plugin_info['errors']) && sizeof($va_plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $va_plugin_info['errors']).'</div>';
					}
					if (is_array($va_plugin_info['warnings']) && sizeof($va_plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $va_plugin_info['warnings']).'</div>';
					}
					if (is_array($va_plugin_info['notices']) && sizeof($va_plugin_info['notices'])) {
						print '<div style="color:green;">'.join('<br/>', $va_plugin_info['notices']).'</div>';
					}
				?></td>
				<td>
<?php
	if((boolean)$va_plugin_info['available']){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((boolean)$va_plugin_info['unused']){
			print "<span style=\"color:GoldenRod;\">"._t("Not used")."</span>";
		} else {
			print "<span style=\"color:red;text-decoration:underline;\">"._t("Not available")."</span>";
		}
	}
?>
				</td>
			</tr>
<?php
}
?>
		</tbody>
	</table>

	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('PDF Rendering Plugins'); ?></div>
	</div><div class="clear"></div>
	
	<table id="caMediaConfigPluginList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Plugin'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Info'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort"><?= _t('Status'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
$va_plugins = $this->getVar('pdf_renderer_config_plugin_list');
foreach($va_plugins as $vs_plugin_name => $va_plugin_info){
?>
			<tr>
				<td><?= $vs_plugin_name; ?></td>
				<td><?php 
					print $va_plugin_info['description']; 
					if (is_array($va_plugin_info['errors']) && sizeof($va_plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $va_plugin_info['errors']).'</div>';
					}
					if (is_array($va_plugin_info['warnings']) && sizeof($va_plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $va_plugin_info['warnings']).'</div>';
					}
					if (is_array($va_plugin_info['notices']) && sizeof($va_plugin_info['notices'])) {
						print '<div style="color:green;">'.join('<br/>', $va_plugin_info['notices']).'</div>';
					}
				?></td>
				<td>
<?php
	if((boolean)$va_plugin_info['available']){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((boolean)$va_plugin_info['unused']){
			print "<span style=\"color:GoldenRod;\">"._t("Not used")."</span>";
		} else {
			print "<span style=\"color:red;text-decoration:underline;\">"._t("Not available")."</span>";
		}
	}
?>
				</td>
			</tr>
<?php
}
?>
		</tbody>
	</table>

	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Barcode generation'); ?></div>
	</div><div class="clear"></div>
	
	<table id="caMediaConfigPluginList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Component'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Info'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Status'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
$va_barcode_components = $this->getVar('barcode_config_component_list');
foreach($va_barcode_components as $vs_component_name => $va_component_info){
?>
			<tr>
				<td><?= $va_component_info['name']; ?></td>
				<td><?php 
					print $va_component_info['description']; 
					if (is_array($va_component_info['errors']) && sizeof($va_component_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $va_component_info['errors']).'</div>';
					}
					if (is_array($va_component_info['warnings']) && sizeof($va_component_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $va_component_info['warnings']).'</div>';
					}
				?></td>
				<td>
<?php
	if((boolean)$va_component_info['available']){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((boolean)$va_component_info['unused']){
			print "<span style=\"color:GoldenRod;\">"._t("Not used")."</span>";
		} else {
			print "<span style=\"color:red;text-decoration:underline;\">"._t("Not available")."</span>";
		}
	}
?>
				</td>
			</tr>
<?php
}
?>
		</tbody>
	</table>
	
	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Application Plugins'); ?></div>
	</div><div class="clear"></div>
	
	<table id="caMediaConfigPluginList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Plugin'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Info'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort"><?= _t('Status'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
$va_plugins = $this->getVar('application_config_plugin_list');
foreach($va_plugins as $vs_plugin_name => $va_plugin_info){
?>
			<tr>
				<td><?= $vs_plugin_name; ?></td>
				<td><?php 
					print $va_plugin_info['description']; 
					if (is_array($va_plugin_info['errors']) && sizeof($va_plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $va_plugin_info['errors']).'</div>';
					}
					if (is_array($va_plugin_info['warnings']) && sizeof($va_plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $va_plugin_info['warnings']).'</div>';
					}
				?></td>
				<td>
<?php
	if((boolean)$va_plugin_info['available']){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((boolean)$va_plugin_info['unused']){
			print "<span style=\"color:GoldenRod;\">"._t("Not used")."</span>";
		} else {
			print "<span style=\"color:red;text-decoration:underline;\">"._t("Not available")."</span>";
		}
	}
?>
				</td>
			</tr>
<?php
}
?>
		</tbody>
	</table>
	
		<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Metadata Extraction Tools'); ?></div>
	</div><div class="clear"></div>
	
	<table id="caMediaConfigPluginList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Tool'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Info'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort"><?= _t('Status'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php
$va_plugins = $this->getVar('metadata_extraction_config_component_list');
foreach($va_plugins as $vs_plugin_name => $va_plugin_info){
?>
			<tr>
				<td><?= $vs_plugin_name; ?></td>
				<td><?php 
					print $va_plugin_info['description']; 
					if (is_array($va_plugin_info['errors']) && sizeof($va_plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $va_plugin_info['errors']).'</div>';
					}
					if (is_array($va_plugin_info['warnings']) && sizeof($va_plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $va_plugin_info['warnings']).'</div>';
					}
				?></td>
				<td>
<?php
	if((boolean)$va_plugin_info['available']){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((boolean)$va_plugin_info['unused']){
			print "<span style=\"color:GoldenRod;\">"._t("Not used")."</span>";
		} else {
			print "<span style=\"color:red;text-decoration:underline;\">"._t("Not available")."</span>";
		}
	}
?>
				</td>
			</tr>
<?php
}
?>
		</tbody>
	</table>
	
</div>

<div class="editorBottomPadding"><!-- empty --></div>
