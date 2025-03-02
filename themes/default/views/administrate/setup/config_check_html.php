<?php
/* ----------------------------------------------------------------------
 * themes/default/views/administrate/setup/config_check_html.php :
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
$search_config_settings = $this->getVar('search_config_settings');

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
while($search_config_settings->nextSetting()){
?>
			<tr>
				<td><?= $search_config_settings->getCurrentName(); ?></td>
				<td>
				<?= $search_config_settings->getCurrentDescription(); ?>
			<?php
			if($search_config_settings->getCurrentStatus()!=__CA_SEARCH_CONFIG_OK__){
				print "<br />";
				print "<div class=\"hint\">".$search_config_settings->getCurrentHint()."</div>";
			}
			?>
				</td>
				<td>
<?php		if($search_config_settings->getCurrentStatus() == __CA_SEARCH_CONFIG_OK__){
				print "<span style=\"color:green\">"._t("ok")."</span>";
			} else if($search_config_settings->getCurrentStatus() == __CA_SEARCH_CONFIG_WARNING__) {
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
	if(caTaskQueueIsEnabled()) {
?>	
	<div class="control-box rounded">
		<div class="control-box-middle-content"><?= _t('Search Indexing Queue'); ?></div>
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

			<tr>
				<td><?= _t('Indexing queue size'); ?></td>
				<td>
				<?= $this->getVar('search_indexing_queue_count'); ?>
				</td>
				<td>
<?php		
					if($this->getVar('search_indexing_queue_is_running')) {
						print "<span style=\"color:red;text-decoration:underline;\">"._t("Running")."</span>";
					} else {
						print "<span style=\"color:green\">"._t("idle")."</span>";
					}
?>
				</td>
			</tr>
		</tbody>
	</table>
<?php
	}
	
	$general_config_errors = $this->getVar("configuration_check_errors");
	if(is_array($general_config_errors) && sizeof($general_config_errors)>0) {
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
		foreach($general_config_errors as $error){
?>
			<tr>
				<td><?= caNavIcon(__CA_NAV_ICON_ALERT__, 2); ?></td>
				<td><?= $error; ?></td>
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
$plugins = $this->getVar('media_config_plugin_list');
foreach($plugins as $plugin_name => $plugin_info){
?>
			<tr>
				<td><?= $plugin_name; ?></td>
				<td><?php 
					print $plugin_info['description'] ?? ''; 
					if (is_array($plugin_info['errors'] ?? null) && sizeof($plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $plugin_info['errors']).'</div>';
					}
					if (is_array($plugin_info['warnings'] ?? null) && sizeof($plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $plugin_info['warnings']).'</div>';
					}
					if (is_array($plugin_info['notices'] ?? null) && sizeof($plugin_info['notices'])) {
						print '<div style="color:green;">'.join('<br/>', $plugin_info['notices']).'</div>';
					}
				?></td>
				<td>
<?php
	if((bool)($plugin_info['available'] ?? false)){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((bool)($plugin_info['unused'] ?? false)){
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
$plugins = $this->getVar('pdf_renderer_config_plugin_list');
foreach($plugins as $plugin_name => $plugin_info){
?>
			<tr>
				<td><?= $plugin_name; ?></td>
				<td><?php 
					print $plugin_info['description']; 
					if (is_array($plugin_info['errors'] ?? null) && sizeof($plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $plugin_info['errors']).'</div>';
					}
					if (is_array($plugin_info['warnings'] ?? null) && sizeof($plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $plugin_info['warnings']).'</div>';
					}
					if (is_array($plugin_info['notices'] ?? null) && sizeof($plugin_info['notices'])) {
						print '<div style="color:green;">'.join('<br/>', $plugin_info['notices']).'</div>';
					}
				?></td>
				<td>
<?php
	if((bool)($plugin_info['available'] ?? false)){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((bool)($plugin_info['unused'] ?? false)){
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
$barcode_components = $this->getVar('barcode_config_component_list');
foreach($barcode_components as $component_name => $component_info){
?>
			<tr>
				<td><?= $component_info['name']; ?></td>
				<td><?php 
					print $component_info['description']; 
					if (is_array($component_info['errors'] ?? null) && sizeof($component_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $component_info['errors']).'</div>';
					}
					if (is_array($component_info['warnings'] ?? null) && sizeof($component_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $component_info['warnings']).'</div>';
					}
				?></td>
				<td>
<?php
	if((bool)($component_info['available'] ?? false)){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((bool)($component_info['unused'] ?? false)){
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
$plugins = $this->getVar('application_config_plugin_list');
foreach($plugins as $plugin_name => $plugin_info){
?>
			<tr>
				<td><?= $plugin_name; ?></td>
				<td><?php 
					print $plugin_info['description']; 
					if (is_array($plugin_info['errors'] ?? null) && sizeof($plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $plugin_info['errors']).'</div>';
					}
					if (is_array($plugin_info['warnings'] ?? null) && sizeof($plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $plugin_info['warnings']).'</div>';
					}
				?></td>
				<td>
<?php
	if((boolean)$plugin_info['available']){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((boolean)$plugin_info['unused']){
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
$plugins = $this->getVar('metadata_extraction_config_component_list');
foreach($plugins as $plugin_name => $plugin_info){
?>
			<tr>
				<td><?= $plugin_name; ?></td>
				<td><?php 
					print $plugin_info['description']; 
					if (is_array($plugin_info['errors'] ?? null) && sizeof($plugin_info['errors'])) {
						print '<div style="color:red;">'.join('<br/>', $plugin_info['errors']).'</div>';
					}
					if (is_array($plugin_info['warnings'] ?? null) && sizeof($plugin_info['warnings'])) {
						print '<div style="color:GoldenRod;">'.join('<br/>', $plugin_info['warnings']).'</div>';
					}
				?></td>
				<td>
<?php
	if((bool)($plugin_info['available'] ?? false)){
		print "<span style=\"color:green\">"._t("Available")."</span>";
	} else {
		if((bool)($plugin_info['unused'] ?? false)){
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
