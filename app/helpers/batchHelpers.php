<?php
/** ---------------------------------------------------------------------
 * app/helpers/batchHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   

	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor relationship bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorRelationshipModeControl($t_item, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('add to each item') => '_add_', 
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.$t_item->tableNum().'_rel_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix.$t_item->tableNum()."_rel_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix.$t_item->tableNum()."_rel').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix.$t_item->tableNum()."_rel').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor ca_sets bundle
	 *
	 * @param int $pn_table_num 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorSetsModeControl($pn_table_num, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('add to each item') => '_add_', 
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.$pn_table_num.'_sets_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix.$pn_table_num."_sets_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix.$pn_table_num."_sets').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix.$pn_table_num."_sets').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor preferred label bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorPreferredLabelsModeControl($t_item, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.'Labels_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix."Labels_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix."Labels').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix."Labels').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor non-preferred label bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorNonPreferredLabelsModeControl($t_item, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('add to each item') => '_add_', 
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.'Labels_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix."Labels_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix."NPLabels').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix."NPLabels').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor intrinsic field bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_bundle_name
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorIntrinsicModeControl($t_item, $ps_bundle_name) {
		$vs_buf = "<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect("{$ps_bundle_name}_batch_mode", array(
				_t("do not use") => "_disabled_", 
				_t('set for each item') => '_replace_'
		), array("id" => "{$ps_bundle_name}_batch_mode_select"))."</div>\n
	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#{$ps_bundle_name}_batch_mode_select').change(function() {
				if (jQuery(this).val() == '_disabled_') {
					jQuery('#intrinsic_{$ps_bundle_name}').slideUp(250);
				} else {
					jQuery('#intrinsic_{$ps_bundle_name}').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for metadata attribute bundles
	 *
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorAttributeModeControl($ps_id_prefix) {
		$vs_buf = "<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect("{$ps_id_prefix}_batch_mode", array(
				_t("do not use") => "_disabled_", 
				_t('add to each item') => '_add_', 
				_t('replace values') => '_replace_',
				_t('remove all values') => '_delete_'
			), array('id' => "{$ps_id_prefix}_batch_mode_select"))."</div>\n
	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#{$ps_id_prefix}_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#{$ps_id_prefix}').slideUp(250);
				} else {
					jQuery('#{$ps_id_prefix}').slideDown(250);
				}
			});
		});
	</script>";
	
		return $vs_buf;
	}
	# ---------------------------------------
?>