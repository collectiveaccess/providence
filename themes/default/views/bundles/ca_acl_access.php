<?php
/* ----------------------------------------------------------------------
 * bundles/ca_acl_access.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2026 Whirl-i-Gig
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
$t_instance 	= $this->getVar('t_instance');
$tablename		= $t_instance->tableName();
$config			= $t_instance->getAppConfig();
	
$can_edit	 	= $t_instance->isSaveable($this->request);
$can_delete		= $t_instance->isDeletable($this->request);

$stats			= $this->getVar('statistics');
$typename		= mb_strtolower($t_instance->getTypeName(null, ['useSingular' => true]));

$acl_enabled 					= caACLIsEnabled($t_instance);
$pawtucket_only_acl_enabled 	= caACLIsEnabled($t_instance, ['forPawtucket' => true]);
$pawtucket_only_acl_separate_inheritance_controls = ($config->get('pawtucket_only_acl_separate_inheritance_controls') || $config->get("{tablename}_pawtucket_only_acl_separate_inheritance_controls"));
$show_public_access_controls 	= ($config->get('acl_show_public_access_controls') || $config->get("{$tablename}_acl_show_public_access_controls"));

$allow_rep_access_inheritance 	= $config->get('ca_object_representations_allow_access_inheritance');
?>
<div class="sectionBox">
<?php
	if ($can_edit) {
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'caAccessControlList').' '.
			caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', $this->request->getModulePath(), $this->request->getController(), 'Access/'.$this->request->getActionExtra(), [$t_instance->primaryKey() => $t_instance->getPrimaryKey()]),
			'',
			''
		);
	}
	print caFormTag($this->request, 'SetAccess', 'caAccessControlList');
	print caHTMLHiddenInput($t_instance->primaryKey(), ['value' => $t_instance->getPrimaryKey()]);
	
	if($t_instance->hasField('access') && $show_public_access_controls) {
?>	
	<div class='globalAccess'>
		<div class='title globalAccessHeader'>
			<?= _t('Public access'); ?>
		</div>
		<p>
			<?=  $t_instance->htmlFormElement('access', '^LABEL ^ELEMENT', ['readonly' => (bool)$t_instance->get('access_inherit_from_parent')]).($t_instance->get('access_inherit_from_parent') ? " <em>("._t('inherited').")</em>" : ''); ?>
		</p>
		
		<hr/>
		<div class='subtitle itemAccessInheritance'><?= _t('Inheritance'); ?></div>
<?php
		if (
			(bool)$t_instance->getAppConfig()->get("{$tablename}_allow_access_inheritance") 
			&& 
			$t_instance->hasField('access_inherit_from_parent')
			&&
			(($t_instance->get('parent_id') > 0) || in_array($tablename, ['ca_objects', 'ca_object_representations'], true))
		) {
			print $t_instance->htmlFormElement('access_inherit_from_parent', '^LABEL ^ELEMENT', ['label' => _t('Inherit access from parent?')]);
		}
		
		if((bool)$t_instance->hasField('access_inherit_from_parent') && (($stats['subRecordCount'] ?? 0) > 0)) {
?>
		<p>
			<?= ($stats['inheritingSubRecordCount'] === 1) ? 
				_t('%1 of %4 %2 are inheriting public access settings from this %3', $stats['inheritingAccessSubRecordCount'], Datamodel::getTableProperty($tablename, 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['subRecordCount']) 
				: 
				_t(' %1 of %4 %2 are inheriting public access settings from this %3', $stats['inheritingAccessSubRecordCount'], Datamodel::getTableProperty($tablename, 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['subRecordCount'])  
			?>
<?php
			if(
					($stats['subRecordCount'] !== $stats['inheritingSubRecordCount'])
					||
					($stats['inheritingSubRecordCount'] > 0)
			) {
?>
				<div style="margin-left: 10px;">
<?php
				if($stats['subRecordCount'] !== $stats['inheritingAccessSubRecordCount']) {
?>
					<?= caHTMLCheckboxInput('set_all_access_inherit_from_parent', ['id' => 'setAllAccessInheritFromParent', 'value' => '1']); ?> <?= _t('Set all to inherit'); ?><span style='margin-left: 10px;'></span>
<?php
				}
				
				if($stats['inheritingAccessSubRecordCount'] > 0) {
?>
					<?= caHTMLCheckboxInput('set_none_access_inherit_from_parent', ['id' => 'setNoneAccessInheritFromParent', 'value' => '1']); ?> <?= _t('Set all to not inherit'); ?><span style='margin-left: 10px;'></span>
<?php
				}
?>
					<?= caHTMLCheckboxInput('set_representation_access_inherit_from_parent', ['id' => 'setRepresentationsAccessInheritFromParent', 'value' => '1'], ['disabled' => true]); ?> <?= _t('Set access inheritance for representations?'); ?>
				</div>
<?php
			}
?>
		</p>
<?php
		}
		if(($tablename === 'ca_collections') && (bool)$t_instance->getAppConfig()->get('ca_objects_allow_access_inheritance')) {
			if($allow_rep_access_inheritance && (($stats['objectRepresentationCount'] ?? 0) > 0)) {
?>
				<p>
					<?= ($stats['inheritingAccessRelatedObjectCount'] === 1) ? 
						_t('%1 of %4 %2 and %5 of %8 %6 are inheriting public access settings', $stats['inheritingAccessRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingAccessRelatedObjectCount'], $stats['inheritingAccessObjectRepresentationCount'], Datamodel::getTableProperty('ca_object_representations', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['objectRepresentationCount']) 
						: 
						_t('%1 of %4 %2 and %5 of %8 %6 are inheriting public access settings', $stats['inheritingAccessRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingAccessRelatedObjectCount'], $stats['inheritingAccessObjectRepresentationCount'], Datamodel::getTableProperty('ca_object_representations', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['objectRepresentationCount'])  
					?>
				</p>
<?php
			} else {
?>
				<p>
					<?= ($stats['inheritingAccessRelatedObjectCount'] === 1) ? 
						_t('%1 of %4 %2 are inheriting public access settings from this %3', $stats['inheritingAccessRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingAccessRelatedObjectCount']) 
						: 
						_t('%1 of %4 %2 are inheriting public access settings from this %3', $stats['inheritingAccessRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingAccessRelatedObjectCount'])  
					?>
				</p>
<?php		
			}
			if(
					($stats['potentialInheritingAccessRelatedObjectCount'] !== $stats['inheritingAccessRelatedObjectCount'])
					||
					($stats['inheritingAccessRelatedObjectCount'] > 0)
			) {
?>
				<div style="margin-left: 10px;">
<?php
				if($stats['potentialInheritingAccessRelatedObjectCount'] !== $stats['inheritingAccessRelatedObjectCount']) {
?>
					<?= caHTMLCheckboxInput('set_all_objects_access_inherit_from_parent', ['id' => 'setAllObjectsAccessInheritFromParent', 'value' => '1']); ?> <?= _t('Set all to inherit'); ?><span style='margin-left: 10px;'></span>
<?php
				}
				
				if($stats['inheritingAccessRelatedObjectCount'] > 0) {
?>
					<?= caHTMLCheckboxInput('set_none_objects_access_inherit_from_parent', ['id' => 'setNoneObjectsAccessInheritFromParent', 'value' => '1']); ?> <?= _t('Set all to not inherit'); ?><span style='margin-left: 10px;'></span>
<?php
				}
?>
					<?= caHTMLCheckboxInput('set_representation_access_inherit_from_parent', ['id' => 'setRepresentationsObjectsAccessInheritFromParent', 'value' => '1'], ['disabled' => true]); ?> <?= _t('Set access inheritance for representations?'); ?>
				</div>
<?php
			}
		}
?>
		</p>
	</div>
<?php
	}

if($acl_enabled || $pawtucket_only_acl_enabled) {
?>
		<div class='globalAccess'>
<?php
		if(!$pawtucket_only_acl_enabled || !$show_public_access_controls) {
?>
			<div class='title itemAccessHeader'><?= _t('Access to this %1', $typename); ?></div>
<?php 	
			$global_access = $t_instance->getACLWorldAccess(['returnAsInitialValuesForBundle' => true]);
			$global_access_status = $global_access['access_display'];
			print "<div class='control'>"._t('All groups and users %1 this %2, unless an exception is created', $t_instance->getACLWorldHTMLFormBundle($this->request, 'caAccessControlList'), $typename)."</div>"; 
?>	
			<hr/>	
<?php
		}
?>
		<div class='title itemAccessExceptions'>
			<?= _t('Access exceptions'); ?>
		</div>
		<div class='control'>
			<?= $t_instance->getACLGroupHTMLFormBundle($this->request, 'caAccessControlList'); ?>	
			<?= $t_instance->getACLUserHTMLFormBundle($this->request, 'caAccessControlList'); ?>
		</div>
<?php
	if(!$pawtucket_only_acl_enabled || $pawtucket_only_acl_separate_inheritance_controls) { 
		// ACL settings are set from Pawtucket (aka "Public") settings  - with access inheritance set the same as acl inheritance
?>
		<hr/>
		<div class='subtitle itemAccessInheritance'><?= _t('Inheritance'); ?></div>
<?php
		if(
			($t_instance->hasField('acl_inherit_from_parent') && (($stats['subRecordCount'] ?? null) > 0))
			||
			($t_instance->hasField('acl_inherit_from_ca_collections'))
			||
			(($tablename === 'ca_collections') && (($stats['relatedObjectCount'] ?? null) > 0))
			||
			($tablename === 'ca_object_representations')
		) {
			
			if ($t_instance->hasField('acl_inherit_from_ca_collections')) {
?>
				<div class='control'><?= $t_instance->htmlFormElement('acl_inherit_from_ca_collections', '^LABEL ^ELEMENT',  ['label' => _t('Inherit access exceptions from collection(s)?')]); ?></div>
<?php
			}
			if ($t_instance->hasField('acl_inherit_from_parent')) {
?>
				<div class='control'><?= $t_instance->htmlFormElement('acl_inherit_from_parent', '^LABEL ^ELEMENT', ['label' => _t('Inherit access exceptions from parent?')]); ?></div>
<?php
			}

			if(
				($t_instance->hasField('acl_inherit_from_parent') && (($stats['subRecordCount'] ?? null) > 0))
			) {
?>
				<p>
				<?= ($stats['inheritingSubRecordCount'] === 1) ? 
					_t('%1 of %4 %2 are inheriting access exceptions from this %3', $stats['inheritingSubRecordCount'], $t_instance->getProperty('NAME_SINGULAR'), $typename, $stats['subRecordCount']) 
					: 
					_t('%1 of %4 %2 are inheriting access exceptions from this %3', $stats['inheritingSubRecordCount'], $t_instance->getProperty('NAME_PLURAL'), $typename, $stats['subRecordCount'])  
				?>
				</p>
<?php
				if($allow_rep_access_inheritance && (($stats['objectRepresentationCount'] ?? 0) > 0)) {
?>
				<p>
					<?= ($stats['inheritingObjectRepresentationCount'] === 1) ? 
						_t('%1 of %4 %2 are inheriting public access settings', $stats['inheritingObjectRepresentationCount'], Datamodel::getTableProperty('ca_object_representations', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['objectRepresentationCount']) 
						: 
						_t('%1 of %4 %2 are inheriting public access settings', $stats['inheritingObjectRepresentationCount'], Datamodel::getTableProperty('ca_object_representations', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['objectRepresentationCount'])  
					?>
				</p>
<?php
				}
				if(
					($stats['subRecordCount'] !== $stats['inheritingSubRecordCount'])
					||
					($stats['inheritingSubRecordCount'] > 0)
				) {
?>
						<div class='inheritanceControl'>
<?php
						if($stats['subRecordCount'] !== $stats['inheritingSubRecordCount']) {
?>
							<?= caHTMLCheckboxInput('set_all_acl_inherit_from_parent', ['id' => 'setAllACLInheritFromParent', 'value' => '1', ]); ?> <?= _t('Set all to inherit'); ?><span style='margin-left: 10px;'></span>
<?php
						}
						if($stats['inheritingSubRecordCount'] > 0) {
?>
							<?= caHTMLCheckboxInput('set_none_acl_inherit_from_parent', ['id' => 'setNoneACLInheritFromParent', 'value' => '1']); ?> <?= _t('Set all to not inherit'); ?>
<?php
						}
?>
							<?= caHTMLCheckboxInput('set_representation_acl_inherit_from_parent', ['id' => 'setRepresentationsACLInheritFromParent', 'value' => '1'], ['disabled' => true]); ?> <?= _t('Set access exception inheritance for representations?'); ?>
						</div>
<?php
					}
?>			
				</p>
<?php
			}

			if(($tablename === 'ca_collections') && (($stats['relatedObjectCount'] ?? null) > 0)) {
				if($allow_rep_access_inheritance && (($stats['objectRepresentationCount'] ?? 0) > 0)) {
?>
					<p>
						<?= ($stats['inheritingRelatedObjectCount'] === 1) ? 
							_t('%1 of %4 %2 and %5 of %8 %6 are inheriting access exceptions', $stats['inheritingRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingRelatedObjectCount'], $stats['inheritingObjectRepresentationCount'], Datamodel::getTableProperty('ca_object_representations', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['objectRepresentationCount']) 
							: 
							_t('%1 of %4 %2 and %5 of %8 %6 are inheriting access exceptions', $stats['inheritingRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingRelatedObjectCount'], $stats['inheritingObjectRepresentationCount'], Datamodel::getTableProperty('ca_object_representations', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['objectRepresentationCount'])  
						?>
					</p>
<?php
				} else {
?>
					<p>
					<?= ($stats['inheritingRelatedObjectCount'] === 1) ? 
						_t('%1 of %4 %2 are inheriting access exceptions from this %3', $stats['inheritingRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingRelatedObjectCount']) 
						: 
						_t(' %1 of %4 %2 are inheriting access exceptions from this %3', $stats['inheritingRelatedObjectCount'], Datamodel::getTableProperty('ca_objects', 'NAME_PLURAL'), $t_instance->getProperty('NAME_SINGULAR'), $stats['potentialInheritingRelatedObjectCount'])  
					?>
					</p>
<?php
				}
				if(
					($stats['potentialInheritingRelatedObjectCount'] !== $stats['inheritingRelatedObjectCount'])
					||
					($stats['inheritingRelatedObjectCount'] > 0)
				) {
?>
						<div class='inheritanceControl'>
<?php
						if($stats['potentialInheritingRelatedObjectCount'] !== $stats['inheritingRelatedObjectCount']) {
?>
							<?= caHTMLCheckboxInput('set_all_acl_inherit_from_ca_collections', ['id' => 'setAllACLInheritFromCollections', 'value' => '1']); ?> <?= _t('Set all to inherit'); ?><span style='margin-left: 10px;'></span>
<?php
						}
						if($stats['inheritingRelatedObjectCount'] > 0) {
?>
							<?= caHTMLCheckboxInput('set_none_acl_inherit_from_ca_collections', ['id' => 'setNoneACLInheritFromCollections', 'value' => '1']); ?> <?= _t('Set all to not inherit'); ?>
<?php
						}
?>
						<?= caHTMLCheckboxInput('set_representation_acl_inherit_from_parent', ['id' => 'setRepresentationsACLInheritFromCollections', 'value' => '1'], ['disabled' => true]); ?> <?= _t('Set access exception inheritance for representations?'); ?>
						
						</div>
<?php
				}
?>			
				</p>
<?php
			}
		}
	}
?>
	</div>
<?php
}
?>
	</form>	
	<div class="editorBottomPadding"><!-- empty --></div>
</div>

<script>
	function _manageInheritControlButtonGroup(e, allID, noneID, repID) {
		if(jQuery(e.target).prop('id') == allID) {
				jQuery('#' + noneID).prop('checked', false);
		} else {
			jQuery('#' + allID).prop('checked', false);
		}
		if(repID) {
			const disableRepControl = !(jQuery('#' + noneID).prop('checked') || jQuery('#' + allID).prop('checked'));
			jQuery('#' + repID).prop('disabled', disableRepControl);
			if(disableRepControl) { 
				jQuery('#' + repID).prop('checked', false);
			}
		}
	}
	jQuery(document).ready(function() {
		jQuery('#setAllACLInheritFromCollections, #setNoneACLInheritFromCollections').on('change', function(e) {
			_manageInheritControlButtonGroup(e, 'setAllACLInheritFromCollections', 'setNoneACLInheritFromCollections', 'setRepresentationsACLInheritFromCollections');
		});
		jQuery('#setAllACLInheritFromParent, #setNoneACLInheritFromParent').on('change', function(e) {
			_manageInheritControlButtonGroup(e, 'setAllACLInheritFromParent', 'setNoneACLInheritFromParent', 'setRepresentationsACLInheritFromParent');
		});
		jQuery('#setAllAccessInheritFromParent, #setNoneAccessInheritFromParent').on('change', function(e) {
			_manageInheritControlButtonGroup(e, 'setAllAccessInheritFromParent', 'setNoneAccessInheritFromParent', 'setRepresentationsAccessInheritFromParent');
		});
		jQuery('#setAllObjectsAccessInheritFromParent, #setNoneObjectsAccessInheritFromParent').on('change', function(e) {
			_manageInheritControlButtonGroup(e, 'setAllObjectsAccessInheritFromParent', 'setNoneObjectsAccessInheritFromParent', 'setRepresentationsObjectsAccessInheritFromParent');
		});
	});
</script>

<?php
TooltipManager::add('.globalAccessHeader', _t($config->get('acl_public_access_tooltip'), $typename));
TooltipManager::add('.itemAccessHeader', _t($config->get('acl_item_access_tooltip'), $typename, $t_instance->getProperty('NAME_PLURAL')));
TooltipManager::add('.itemAccessExceptions', _t($config->get('acl_exceptions_tooltip')));
TooltipManager::add('.itemAccessInheritance', _t($config->get('acl_inheritance_tooltip'), $typename, $t_instance->getProperty('NAME_PLURAL')));
