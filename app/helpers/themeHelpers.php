<?php
/** ---------------------------------------------------------------------
 * app/helpers/themeHelpers.php : utility functions for setting database-stored configuration values
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2016 Whirl-i-Gig
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
	 * Generate HTML <img> tag for graphic in current theme; if graphic is not available the graphic in the default theme will be returned.
	 *
	 * @param RequestHTTP $po_request
	 * @param string $ps_file_path
	 * @param array $pa_attributes
	 * @param array $pa_options
	 * @return string
	 */
	function caGetThemeGraphic($po_request, $ps_file_path, $pa_attributes=null, $pa_options=null) {
		$vs_base_url_path = $po_request->getThemeUrlPath();
		$vs_base_path = $po_request->getThemeDirectoryPath();
		$vs_file_path = '/assets/pawtucket/graphics/'.$ps_file_path;

		if (!file_exists($vs_base_path.$vs_file_path)) {
			$vs_base_url_path = $po_request->getDefaultThemeUrlPath();
		}

		$vs_html = caHTMLImage($vs_base_url_path.$vs_file_path, $pa_attributes, $pa_options);

		return $vs_html;
	}
	# ---------------------------------------
	/**
	 * Generate URL tag for graphic in current theme; if graphic is not available the graphic in the default theme will be returned.
	 *
	 * @param RequestHTTP $po_request
	 * @param string $ps_file_path
	 * @param array $pa_options
	 * @return string
	 */
	function caGetThemeGraphicURL($po_request, $ps_file_path, $pa_options=null) {
		$vs_base_path = $po_request->getThemeUrlPath();
		$vs_file_path = '/assets/pawtucket/graphics/'.$ps_file_path;

		if (!file_exists($po_request->getThemeDirectoryPath().$vs_file_path)) {
			$vs_base_path = $po_request->getDefaultThemeUrlPath();
		}
		return $vs_base_path.$vs_file_path;
	}
	# ---------------------------------------
	/**
	 * Set CSS classes to add the "pageArea" page content <div>, overwriting any previous setting.
	 * Use to set classes specific to each page type and context.
	 *
	 * @param RequestHTTP $po_request
	 * @param mixed $pa_page_classes A class (string) or list of classes (array) to set
	 * @return bool Always returns true
	 */
	$g_theme_page_css_classes = array();
	function caSetPageCSSClasses($pa_page_classes) {
		global $g_theme_page_css_classes;
		if (!is_array($pa_page_classes) && $pa_page_classes) { $pa_page_classes = array($pa_page_classes); }
		if (!is_array($pa_page_classes)) { $pa_page_classes = array(); }

		$g_theme_page_css_classes = $pa_page_classes;

		return true;
	}
	# ---------------------------------------
	/**
	 * Adds CSS classes to the "pageArea" page content <div>. Use to set classes specific to each
	 * page type and context.
	 *
	 * @param RequestHTTP $po_request
	 * @param mixed $pa_page_classes A class (string) or list of classes (array) to add
	 * @return bool Returns true if classes were added, false if class list is empty
	 */
	function caAddPageCSSClasses($pa_page_classes) {
		global $g_theme_page_css_classes;
		if (!is_array($pa_page_classes) && $pa_page_classes) { $pa_page_classes = array($pa_page_classes); }

		if(!is_array($va_classes = $g_theme_page_css_classes)) {
			return false;
		}

		$g_theme_page_css_classes = array_unique(array_merge($pa_page_classes, $va_classes));

		return true;
	}
	# ---------------------------------------
	/**
	 * Get CSS class attribute ready for including in a <div> tag. Used to add classes to the "pageArea" page content <div>
	 *
	 * @param RequestHTTP $po_request
	 * @return string The "class" attribute with set classes or an empty string if no classes are set
	 */
	function caGetPageCSSClasses() {
		global $g_theme_page_css_classes;
		return (is_array($g_theme_page_css_classes) && sizeof($g_theme_page_css_classes)) ? "class='".join(' ', $g_theme_page_css_classes)."'" : '';
	}
	# ---------------------------------------
	/**
	 * Converts, and by default prints, a root-relative static view path to a DefaultController URL to load the appropriate view
	 * Eg. $ps_path of "/About/this/site" becomes "/index.php/About/this/site"
	 *
	 * @param string $ps_path
	 * @param array $pa_options Options include:
	 *		dontPrint = Don't print URL to output. Default is false.
	 *		request = The current request object (RequestHTTP). Default is to use globally set request object.
	 *
	 * @return string the URL
	 */
	function caStaticPageUrl($ps_path, $pa_options=null) {
		global $g_request;

		if (!($po_request = caGetOption('request', $pa_options, null))) { $po_request = $g_request; }
		$vs_url = $po_request->getBaseUrlPath().'/'.$po_request->getScriptName().$ps_path;

		if (!caGetOption('dontPrint', $pa_options, false)) {
			print $vs_url;
		}
		return $vs_url;
	}
	# ---------------------------------------
	/**
	 * Get theme-specific detail configuration
	 *
	 * @return Configuration
	 */
	function caGetDetailConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/detail.conf');
	}
	# ---------------------------------------
	/**
	 *
	 *
	 * @param string $ps_detail_type
	 * @return array
	 */
	function caGetDetailTypeConfig($ps_detail_type) {
		$o_config = Configuration::load(__CA_THEME_DIR__.'/conf/detail.conf');
		$va_config_values = $o_config->get('detailTypes');
		if (isset($va_config_values[$ps_detail_type])) {
			return $va_config_values[$ps_detail_type];
		}
		return null;
	}
	# ---------------------------------------
	/**
	 * Get theme-specific gallery section configuration
	 *
	 * @return Configuration
	 */
	function caGetGalleryConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/gallery.conf');
	}
	# ---------------------------------------
	/**
	 * Get theme-specific contact configuration
	 *
	 * @return Configuration
	 */
	function caGetContactConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/contact.conf');
	}
	# ---------------------------------------
	/**
	 * Get theme-specific front page configuration
	 *
	 * @return Configuration
	 */
	function caGetFrontConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/front.conf');
	}
	# ---------------------------------------
	/**
	 * Get theme-specific finding-aid section configuration
	 *
	 * @return Configuration
	 */
	function caGetCollectionsConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/collections.conf');
	}
	# ---------------------------------------
	/**
	 * Get theme-specific icon configuration
	 *
	 * @return Configuration
	 */
	function caGetIconsConfig() {
		if(file_exists(__CA_THEME_DIR__.'/conf/icons.conf')){
			return Configuration::load(__CA_THEME_DIR__.'/conf/icons.conf');
		}else{
			return Configuration::load(__CA_THEMES_DIR__.'/default/conf/icons.conf');
		}
	}
	# ---------------------------------------
	/**
	 * Get theme-specific sets/lightbox configuration
	 *
	 * @return Configuration
	 */
	function caGetLightboxConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/lightbox.conf');
	}
	# ---------------------------------------
	/**
	 * Get theme-specific sets/classroom configuration
	 *
	 * @return Configuration
	 */
	function caGetClassroomConfig() {
		return Configuration::load(__CA_THEME_DIR__.'/conf/classroom.conf');
	}
	# ---------------------------------------
	/**
	 * Returns associative array, keyed by primary key value with values being
	 * the preferred label of the row from a suitable locale, ready for display
	 *
	 * @param array $pa_ids indexed array of primary key values to fetch labels for
	 * @param array $pa_options
	 * @return array List of media
	 */
	function caGetPrimaryRepresentationsForIDs($pa_ids, $pa_options=null) {
		if (!is_array($pa_ids) && (is_numeric($pa_ids)) && ($pa_ids > 0)) { $pa_ids = array($pa_ids); }
		if (!is_array($pa_ids) || !sizeof($pa_ids)) { return array(); }

		$pa_access_values = caGetOption("checkAccess", $pa_options, array());
		$pa_versions = caGetOption("versions", $pa_options, array(), array('castTo' => 'array'));
		$ps_table = caGetOption("table", $pa_options, 'ca_objects');
		$pa_return = caGetOption("return", $pa_options, array(), array('castTo' => 'array'));

		$vs_access_where = '';
		if (isset($pa_access_values) && is_array($pa_access_values) && sizeof($pa_access_values)) {
			$vs_access_where = ' AND orep.access IN ('.join(',', $pa_access_values).')';
		}
		$o_db = new Db();
		$o_dm = Datamodel::load();

		if (!($vs_linking_table = RepresentableBaseModel::getRepresentationRelationshipTableName($ps_table))) { return null; }
		$vs_pk = $o_dm->getTablePrimaryKeyName($ps_table);

		$qr_res = $o_db->query("
			SELECT oxor.{$vs_pk}, orep.media, orep.representation_id
			FROM ca_object_representations orep
			INNER JOIN {$vs_linking_table} AS oxor ON oxor.representation_id = orep.representation_id
			WHERE
				(oxor.{$vs_pk} IN (".join(',', $pa_ids).")) AND oxor.is_primary = 1 AND orep.deleted = 0 {$vs_access_where}
		");

		$vb_return_tags = (sizeof($pa_return) == 0) || in_array('tags', $pa_return);
		$vb_return_info = (sizeof($pa_return) == 0) || in_array('info', $pa_return);
		$vb_return_urls = (sizeof($pa_return) == 0) || in_array('urls', $pa_return);

		$va_media = array();
		while($qr_res->nextRow()) {
			$va_media_tags = array();
			if ($pa_versions && is_array($pa_versions) && sizeof($pa_versions)) { $va_versions = $pa_versions; } else { $va_versions = $qr_res->getMediaVersions('media'); }

			$vb_media_set = false;
			foreach($va_versions as $vs_version) {
				if (!$vb_media_set && $qr_res->getMediaPath('ca_object_representations.media', $vs_version)) { $vb_media_set = true; }

				if ($vb_return_tags) {
					if (sizeof($va_versions) == 1) {
						$va_media_tags['tags'] = $qr_res->getMediaTag('ca_object_representations.media', $vs_version);
					} else {
						$va_media_tags['tags'][$vs_version] = $qr_res->getMediaTag('ca_object_representations.media', $vs_version);
					}
				}
				if ($vb_return_info) {
					if (sizeof($va_versions) == 1) {
						$va_media_tags['info'] = $qr_res->getMediaInfo('ca_object_representations.media', $vs_version);
					} else {
						$va_media_tags['info'][$vs_version] = $qr_res->getMediaInfo('ca_object_representations.media', $vs_version);
					}
				}
				if ($vb_return_urls) {
					if (sizeof($va_versions) == 1) {
						$va_media_tags['urls'] = $qr_res->getMediaUrl('ca_object_representations.media', $vs_version);
					} else {
						$va_media_tags['urls'][$vs_version] = $qr_res->getMediaUrl('ca_object_representations.media', $vs_version);
					}
				}
			}

			$va_media_tags['representation_id'] = $qr_res->get('ca_object_representations.representation_id');

			if (!$vb_media_set)  { continue; }

			if (sizeof($pa_return) == 1) {
				$va_media_tags = $va_media_tags[$pa_return[0]];
			}
			$va_media[$qr_res->get($vs_pk)] = $va_media_tags;
		}

		// Return empty array when there's no media
		if(!sizeof($va_media)) { return array(); }

		// Preserve order of input ids
		$va_media_sorted = array();
		foreach($pa_ids as $vn_id) {
			if(!isset($va_media[$vn_id]) || !$va_media[$vn_id]) { continue; }
			$va_media_sorted[$vn_id] = $va_media[$vn_id];
		}

		return $va_media_sorted;
	}
	# ---------------------------------------
	/**
	 * Returns associative array, keyed by primary key value with values being
	 * the preferred label of the row from a suitable locale, ready for display
	 *
	 * @param array $pa_ids indexed array of primary key values to fetch labels for
	 * @param array $pa_options
	 * @return array List of media
	 */
	function caGetPrimaryRepresentationTagsForIDs($pa_ids, $pa_options=null) {
		$pa_options['return'] = array('tags');

		return caGetPrimaryRepresentationsForIDs($pa_ids, $pa_options);
	}
	# ---------------------------------------
	/**
	 * Returns associative array, keyed by primary key value with values being
	 * the preferred label of the row from a suitable locale, ready for display
	 *
	 * @param array $pa_ids indexed array of primary key values to fetch labels for
	 * @param array $pa_options
	 * @return array List of media
	 */
	function caGetPrimaryRepresentationInfoForIDs($pa_ids, $pa_options=null) {
		$pa_options['return'] = array('info');

		return caGetPrimaryRepresentationsForIDs($pa_ids, $pa_options);
	}
	# ---------------------------------------
	/**
	 * Returns associative array, keyed by primary key value with values being
	 * the preferred label of the row from a suitable locale, ready for display
	 *
	 * @param array $pa_ids indexed array of primary key values to fetch labels for
	 * @param array $pa_options
	 * @return array List of media
	 */
	function caGetPrimaryRepresentationUrlsForIDs($pa_ids, $pa_options=null) {
		$pa_options['return'] = array('urls');

		return caGetPrimaryRepresentationsForIDs($pa_ids, $pa_options);
	}
	# ---------------------------------------
	/*
	 *
	 * @param RequestHTTP $po_request
	 * @param int $pn_representation_id
	 * @param ca_objects $pt_object
	 * @param array $pa_options Options include:
	 *		version = media version for thumbnail [Default = icon]
	 *		linkTo = viewer, detail, carousel. Carousel slides the media rep carousel on the default object detail page to the selected rep. [Default = carousel] 
	 *		returnAs = list, bsCols, array	[Default = list]
	 *		bsColClasses = pass the classes to assign to bs col [Default = col-sm-4 col-md-3 col-lg-3]
	 *		dontShowCurrentRep = true, false [Default = false]
	 *		currentRepClass = set to class name added to li and a tag for current rep [Default = active]
	 * @return string HTML output
	 */
	function caObjectRepresentationThumbnails($po_request, $pn_representation_id, $pt_object, $pa_options){
		if(!$pt_object || !$pt_object->get("object_id")){
			return false;
		}
		if(!is_array($pa_options)){
			$pa_options = array();
		}
		# --- set defaults
		$vs_version = caGetOption('version', $pa_options, 'icon');
		$vs_link_to = caGetOption('linkTo', $pa_options, 'carousel');
		$vs_return_as = caGetOption('returnAs', $pa_options, 'list');
		$vs_bs_col_classes = caGetOption('bsColClasses', $pa_options, 'col-sm-4 col-md-3 col-lg-3');
		$vs_current_rep_class = caGetOption('currentRepClass', $pa_options, 'active');
		
		if(!$pa_options["currentRepClass"]){
			$pa_options["currentRepClass"] = "active";
		}
		# --- get reps as thumbnails
		$va_reps = $pt_object->getRepresentations(array($vs_version), null, array("checkAccess" => caGetUserAccessValues($po_request)));
		if(sizeof($va_reps) < 2){
			return;
		}
		$va_links = array();
		$vn_primary_id = "";
		foreach($va_reps as $vn_rep_id => $va_rep){
			$vs_class = "";
			if($va_rep["is_primary"]){
				$vn_primary_id = $vn_rep_id;
			}
			if($vn_rep_id == $pn_representation_id){
				if($pa_options["dontShowCurrentRep"]){
					continue;
				}
				$vs_class = $vs_current_rep_class;
			}
			$vs_thumb = $va_rep["tags"][$vs_version];
			switch($vs_link_to){
				# -------------------------------
				case "viewer":
					$va_links[$vn_rep_id] = "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', 'Detail', 'GetRepresentationInfo', array('object_id' => $pt_object->get("object_id"), 'representation_id' => $vn_rep_id, 'overlay' => 1))."\"); return false;' ".(($vs_class) ? "class='".$vs_class."'" : "").">".$vs_thumb."</a>\n";
					break;
				# -------------------------------
				case "carousel":
					$va_links[$vn_rep_id] = "<a href='#' onclick='$(\".{$vs_current_rep_class}\").removeClass(\"{$vs_current_rep_class}\"); $(this).parent().addClass(\"{$vs_current_rep_class}\"); $(this).addClass(\"{$vs_current_rep_class}\"); $(\".jcarousel\").jcarousel(\"scroll\", $(\"#slide".$vn_rep_id."\"), false); return false;' ".(($vs_class) ? "class='".$vs_class."'" : "").">".$vs_thumb."</a>\n";
					break;
				# -------------------------------
				default:
				case "detail":
					$va_links[$vn_rep_id] = caDetailLink($po_request, $vs_thumb, $vs_class, 'ca_objects', $pt_object->get("object_id"), array("representation_id" => $vn_rep_id));
					break;
				# -------------------------------
			}
		}
		
		# --- make sure the primary rep shows up first
		//$va_primary_link = array($vn_primary_id => $va_links[$vn_primary_id]);
		//unset($va_links[$vn_primary_id]);
		//$va_links = $va_primary_link + $va_links;
		
		# --- formatting
		$vs_formatted_thumbs = "";
		switch($vs_return_as){
			# ---------------------------------
			case "list":
				$vs_formatted_thumbs = "<ul id='detailRepresentationThumbnails'>";
				foreach($va_links as $vn_rep_id => $vs_link){
					if($vs_link){ $vs_formatted_thumbs .= "<li id='detailRepresentationThumbnail{$vn_rep_id}'".(($vn_rep_id == $pn_representation_id) ? " class='{$vs_current_rep_class}'" : "").">{$vs_link}</li>\n"; }
				}
				$vs_formatted_thumbs .= "</ul>";
				return $vs_formatted_thumbs;
				break;
			# ---------------------------------
			case "bsCols":
				$vs_formatted_thumbs = "<div class='container'><div class='row' id='detailRepresentationThumbnails'>";
				foreach($va_links as $vn_rep_id => $vs_link){
					if($vs_link){ $vs_formatted_thumbs .= "<div id='detailRepresentationThumbnail{$vn_rep_id}' class='{$vs_bs_col_classes}".(($vn_rep_id == $pn_representation_id) ? " {$vs_current_rep_class}" : "")."'>{$vs_link}</div>\n"; }
				}
				$vs_formatted_thumbs .= "</div></div>\n";
				return $vs_formatted_thumbs;
				break;
			# ---------------------------------
			case "array":
				return $va_links;
				break;
			# ---------------------------------
		}
	}
	# ---------------------------------------
	/*
	 * list of comments and
	 * comment form for all detail pages
	 *
	 */
	function caDetailItemComments($po_request, $pn_item_id, $t_item, $va_comments, $va_tags){
		$vs_tmp = "";
		if(is_array($va_comments) && (sizeof($va_comments) > 0)){
			foreach($va_comments as $va_comment){
				$vs_tmp .= "<blockquote>";
				if($va_comment["media1"]){
					$vs_tmp .= '<div class="pull-right" id="commentMedia'.$va_comment["comment_id"].'">';
					$vs_tmp .= $va_comment["media1"]["tiny"]["TAG"];
					$vs_tmp .= "</div><!-- end pullright commentMedia -->\n";
					TooltipManager::add(
						"#commentMedia".$va_comment["comment_id"], $va_comment["media1"]["large_preview"]["TAG"]
					);
				}
				if($va_comment["comment"]){
					$vs_tmp .= $va_comment["comment"];
				}
				$vs_tmp .= "<small>".$va_comment["author"].", ".$va_comment["date"]."</small></blockquote>";
			}
		}
		if(is_array($va_tags) && sizeof($va_tags) > 0){
			$va_tag_links = array();
			foreach($va_tags as $vs_tag){
				$va_tag_links[] = caNavLink($po_request, $vs_tag, '', '', 'MultiSearch', 'Index', array('search' => $vs_tag));
			}
			$vs_tmp .= "<h2>"._t("Tags")."</h2>\n
				<div id='tags'>".implode($va_tag_links, ", ")."</div>";
		}
		if($po_request->isLoggedIn()){
			$vs_tmp .= "<button type='button' class='btn btn-default' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', 'Detail', 'CommentForm', array("tablename" => $t_item->tableName(), "item_id" => $t_item->getPrimaryKey()))."\"); return false;' >"._t("Add your tags and comment")."</button>";
		}else{
			$vs_tmp .= "<button type='button' class='btn btn-default' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', 'LoginReg', 'LoginForm', array())."\"); return false;' >"._t("Login/register to comment on this object")."</button>";
		}
		return $vs_tmp;
	}
	# ---------------------------------------
	/*
	 * Returns the info for each set
	 *
	 * options: "write_access" = false
	 *
	 */
	function caLightboxSetListItem($po_request, $t_set, $va_check_access = array(), $pa_options = array()) {
		if(!($vn_set_id = $t_set->get("set_id"))) {
			return false;
		}
		$vb_write_access = false;
		if($pa_options["write_access"]){
			$vb_write_access = true;
		}
		$va_set_items = caExtractValuesByUserLocale($t_set->getItems(array("user_id" => $po_request->user->get("user_id"), "thumbnailVersions" => array("iconlarge", "icon"), "checkAccess" => $va_check_access, "limit" => 5)));
		
		$vs_set_display = "<div class='lbSetContainer' id='lbSetContainer{$vn_set_id}'><div class='lbSet ".(($vb_write_access) ? "" : "readSet" )."'><div class='lbSetContent'>\n";
		if(!$vb_write_access){
			$vs_set_display .= "<div class='pull-right caption'>Read Only</div>";
		}
		$vs_set_display .= "<H5>".caNavLink($po_request, $t_set->getLabelForDisplay(), "", "", "Lightbox", "setDetail", array("set_id" => $vn_set_id), array('id' => "lbSetName{$vn_set_id}"))."</H5>";

        $va_lightboxDisplayName = caGetLightboxDisplayName();
		$vs_lightbox_displayname = $va_lightboxDisplayName["singular"];
		$vs_lightbox_displayname_plural = $va_lightboxDisplayName["plural"];

        if(sizeof($va_set_items)){
			$vs_primary_image_block = $vs_secondary_image_block = "";
			$vn_i = 1;
			$t_list_items = new ca_list_items();
			foreach($va_set_items as $va_set_item){
				$t_list_items->load($va_set_item["type_id"]);
				$vs_placeholder = caGetPlaceholder($t_list_items->get("idno"), "placeholder_media_icon");
				if($vn_i == 1){
					# --- is the iconlarge version available?
					$vs_large_icon = "icon";
					if($va_set_item["representation_url_iconlarge"]){
						$vs_large_icon = "iconlarge";
					}
					if($va_set_item["representation_tag_".$vs_large_icon]){
						$vs_primary_image_block .= "<div class='col-sm-6'><div class='lbSetImg'>".caNavLink($po_request, $va_set_item["representation_tag_".$vs_large_icon], "", "", "Lightbox", "setDetail", array("set_id" => $vn_set_id))."</div><!-- end lbSetImg --></div>\n";
					}else{
						$vs_primary_image_block .= "<div class='col-sm-6'><div class='lbSetImg'>".caNavLink($po_request, "<div class='lbSetImgPlaceholder'>".$vs_placeholder."</div><!-- end lbSetImgPlaceholder -->", "", "", "Lightbox", "setDetail", array("set_id" => $vn_set_id))."</div><!-- end lbSetImg --></div>\n";
					}
				}else{
					if($va_set_item["representation_tag_icon"]){
						$vs_secondary_image_block .= "<div class='col-xs-3 col-sm-6 lbSetThumbCols'><div class='lbSetThumb'>".caNavLink($po_request, $va_set_item["representation_tag_icon"], "", "", "Lightbox", "setDetail", array("set_id" => $vn_set_id))."</div><!-- end lbSetThumb --></div>\n";
					}else{
						$vs_secondary_image_block .= "<div class='col-xs-3 col-sm-6 lbSetThumbCols'>".caNavLink($po_request, "<div class='lbSetThumbPlaceholder'>".caGetThemeGraphic($po_request,'spacer.png').$vs_placeholder."</div><!-- end lbSetThumbPlaceholder -->", "", "", "Lightbox", "setDetail", array("set_id" => $vn_set_id))."</div>\n";
					}
				}
				$vn_i++;
			}
			while($vn_i < 6){
				$vs_secondary_image_block .= "<div class='col-xs-3 col-sm-6 lbSetThumbCols'>".caNavLink($po_request, "<div class='lbSetThumbPlaceholder'>".caGetThemeGraphic($po_request,'spacer.png')."</div><!-- end lbSetThumbPlaceholder -->", "", "", "Lightbox", "setDetail", array("set_id" => $vn_set_id))."</div>";
				$vn_i++;
			}
		}else{
			$vs_primary_image_block .= "<div class='col-sm-6'><div class='lbSetImg'><div class='lbSetImgPlaceholder'>"._t("this %1 contains no items", $vs_lightbox_displayname)."</div><!-- end lbSetImgPlaceholder --></div><!-- end lbSetImg --></div>\n";
			$i = 1;
			while($vn_i < 4){
				$vs_secondary_image_block .= "<div class='col-xs-3 col-sm-6 lbSetThumbCols'><div class='lbSetThumbPlaceholder'>".caGetThemeGraphic($po_request,'spacer.png')."</div><!-- end lbSetThumbPlaceholder --></div>";
				$vn_i++;
			}
		}
		$vs_set_display .= "<div class='row'>".$vs_primary_image_block."<div class='col-sm-6'><div id='comment{$vn_set_id}' class='lbSetComment'><!-- load comments here --></div>\n<div class='lbSetThumbRowContainer'><div class='row lbSetThumbRow' id='lbSetThumbRow{$vn_set_id}'>".$vs_secondary_image_block."</div><!-- end row --></div><!-- end lbSetThumbRowContainer --></div><!-- end col --></div><!-- end row -->";
		$vs_set_display .= "</div><!-- end lbSetContent -->\n";
		$vs_set_display .= "<div class='lbSetExpandedInfo' id='lbExpandedInfo{$vn_set_id}'>\n<hr><div>created by: ".trim($t_set->get("ca_users.fname")." ".$t_set->get("ca_users.lname"))."</div>\n";
		$vs_set_display .= "<div>"._t("Items: %1", $t_set->getItemCount(array("user_id" => $po_request->user->get("user_id"), "checkAccess" => $va_check_access)))."</div>\n";
		if($vb_write_access){
			$vs_set_display .= "<div class='pull-right'><a href='#' data-set_id=\"".(int)$t_set->get('set_id')."\" data-set_name=\"".addslashes($t_set->get('ca_sets.preferred_labels.name'))."\" data-toggle='modal' data-target='#confirm-delete'><span class='glyphicon glyphicon-trash'></span></a></div>\n";
		}
		$vs_set_display .= "<div><a href='#' onclick='jQuery(\"#comment{$vn_set_id}\").load(\"".caNavUrl($po_request, '', 'Lightbox', 'AjaxListComments', array('type' => 'ca_sets', 'set_id' => $vn_set_id))."\", function(){jQuery(\"#lbSetThumbRow{$vn_set_id}\").hide(); jQuery(\"#comment{$vn_set_id}\").show();}); return false;' title='"._t("Comments")."'><span class='glyphicon glyphicon-comment'></span> <small>".$t_set->getNumComments()."</small></a>";
		if($vb_write_access){
			$vs_set_display .= "&nbsp;&nbsp;&nbsp;<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', 'Lightbox', 'setForm', array("set_id" => $vn_set_id))."\"); return false;' title='"._t("Edit Name/Description")."'><span class='glyphicon glyphicon-edit'></span></a>";
		}
		$vs_set_display .= "</div>\n";
		$vs_set_display .= "</div><!-- end lbSetExpandedInfo --></div><!-- end lbSet --></div><!-- end lbSetContainer -->\n";

        return $vs_set_display;
	}
	# ---------------------------------------
	/*
	 * Returns the info for each set
	 *
	 * options: "write_access" = false
	 *
	 */
	function caClassroomSetListItem($po_request, $t_set, $va_check_access = array(), $pa_options = array()) {
		if(!($vn_set_id = $t_set->get("set_id"))) {
			return false;
		}
		$vb_write_access = false;
		if($pa_options["write_access"]){
			$vb_write_access = true;
		}
		$vs_set_display = "<div class='crSetContainer' id='crSetContainer{$vn_set_id}'><div class='crSet'>\n";
		$vs_set_display .= caNavLink($po_request, _t("View"), "btn btn-default pull-right", "", "Classroom", "setDetail", array("set_id" => $vn_set_id));
		$vs_set_display .= "<H5 id='crSetName".$t_set->get("set_id")."'>".caNavLink($po_request, $t_set->getLabelForDisplay(), "", "", "Classroom", "setDetail", array("set_id" => $vn_set_id), array('id' => "crSetName{$vn_set_id}"))."</H5>";

        $va_classroomDisplayName = caGetClassroomDisplayName();
		$vs_classroom_displayname = $va_classroomDisplayName["singular"];
		$vs_classroom_displayname_plural = $va_classroomDisplayName["plural"];

		$vs_set_display .= "<p id='crSetDescription".$t_set->get("set_id")."'>";
		if ($vs_description = $t_set->get("description")) {
			$vs_set_display .= $vs_description;
		}
		$vs_set_display .= "</p><hr/>";

		if(!$t_set->get("parent_id")){
			$va_set_items = caExtractValuesByUserLocale($t_set->getItems(array("user_id" => $po_request->user->get("user_id"), "thumbnailVersions" => array("iconlarge", "icon"), "checkAccess" => $va_check_access, "limit" => 6)));
			
			if(sizeof($va_set_items)){
				$vs_image_block = "";
				$t_list_items = new ca_list_items();
				foreach($va_set_items as $va_set_item){
					$t_list_items->load($va_set_item["type_id"]);
					$vs_placeholder = caGetPlaceholder($t_list_items->get("idno"), "placeholder_media_icon");
					# --- is the iconlarge version available?
					$vs_large_icon = "icon";
					if($va_set_item["representation_url_iconlarge"]){
						$vs_large_icon = "iconlarge";
					}
					if($va_set_item["representation_tag_".$vs_large_icon]){
						$vs_image_block .= "<div class='col-xs-4 col-sm-2 crSetImg'>".caNavLink($po_request, $va_set_item["representation_tag_".$vs_large_icon], "", "", "Classroom", "setDetail", array("set_id" => $vn_set_id))."</div>\n";
					}else{
						$vs_image_block .= "<div class='col-xs-4 col-sm-2 crSetImg'>".caNavLink($po_request, "<div class='crSetImgPlaceholder'>".$vs_placeholder."</div><!-- end lbSetImgPlaceholder -->", "", "", "Classroom", "setDetail", array("set_id" => $vn_set_id))."</div>\n";
					}
				}
				$vs_set_display .= "<div class='row'>".$vs_image_block."</div><!-- end row -->";
				$vs_set_display .= "\n<hr/>";		
			}
		}
		if($vb_write_access){
			$vs_set_display .= "<div class='pull-right'>";
			if(!$t_set->get("parent_id")){
				$vs_set_display .= "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', '*', 'shareSetForm', array("set_id" => $vn_set_id))."\"); return false;' title='"._t("Share %1", ucfirst($vs_classroom_displayname))."'><span class='glyphicon glyphicon-share'></span></a>&nbsp;&nbsp;\n";
				$vs_set_display .= "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', '*', 'setAccess', array("set_id" => $vn_set_id))."\"); return false;' title='"._t("Manage %1 Access", ucfirst($vs_classroom_displayname))."'><span class='glyphicon glyphicon-user'></span></a>&nbsp;&nbsp;\n";
			}
			$vs_set_display .= "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($po_request, '', 'Classroom', 'setForm', array("set_id" => $vn_set_id))."\"); return false;' title='"._t("Edit Name/Description")."'><span class='glyphicon glyphicon-edit'></span></a>&nbsp;&nbsp;\n";
			$vs_set_display .= "<a href='#' title='"._t("Delete")."' data-set_id=\"".(int)$t_set->get('set_id')."\" data-set_name=\"".addslashes($t_set->get('ca_sets.preferred_labels.name'))."\" data-toggle='modal' data-target='#confirm-delete'><span class='glyphicon glyphicon-trash'></span></a></div>\n";
		}

		$vs_set_display .= "<small>"._t("Items: %1", $t_set->getItemCount(array("user_id" => $po_request->user->get("user_id"), "checkAccess" => $va_check_access)))."&nbsp;&nbsp;&nbsp;"._t("Comments: %1", $t_set->getNumComments());
		if(!$t_set->get("parent_id")){
			$vs_set_display .= "&nbsp;&nbsp;&nbsp;"._t("Responses: %1", sizeof($t_set->getSetResponseIds()));
		}
		$vs_set_display .= "</small>\n";
		$vs_set_display .= "</div><!-- end crSet --></div><!-- end crSetContainer -->\n";

        return $vs_set_display;
	}
	# ---------------------------------------
	/*
	 * Returns the info for each reponse set in classroom interface
	 *
	 * options: "write_access" = false
	 *
	 */
	function caClassroomSetResponseItem($po_request, $t_set, $va_check_access = array(), $pa_options = array()) {
		if(!($vn_set_id = $t_set->get("set_id"))) {
			return false;
		}
		$vb_write_access = false;
		if($pa_options["write_access"]){
			$vb_write_access = true;
		}
		$vs_set_display = "<div class='crSetContainer' id='crSetContainer{$vn_set_id}'><div class='crSet'>\n";
		$vs_set_display .= caNavLink($po_request, _t("View"), "btn btn-default pull-right", "", "Classroom", "setDetail", array("set_id" => $vn_set_id));
		$vs_set_display .= "<H5>".caNavLink($po_request, $t_set->getLabelForDisplay(), "", "", "Classroom", "setDetail", array("set_id" => $vn_set_id), array('id' => "crSetName{$vn_set_id}"))."</H5>";

        $vs_set_display .= "<small>\n";
		$vs_set_display .= _t("Created by:").trim($t_set->get("ca_users.fname")." ".$t_set->get("ca_users.lname"))."<br/>\n";
		$vs_set_display .= _t("Items:").$t_set->getItemCount(array("user_id" => $po_request->user->get("user_id"), "checkAccess" => $va_check_access))."&nbsp;&nbsp;&nbsp;\n";
		$vs_set_display .= _t("Comments:").$t_set->getNumComments()."&nbsp;&nbsp;&nbsp;</small>\n";
		$vs_set_display .= "</div><!-- end crSet --></div><!-- end crSetContainer -->\n";

        return $vs_set_display;
	}
	# ---------------------------------------
	/**
	 *
	 *
	 */
	$g_theme_detail_for_type_cache = array();
	function caGetDetailForType($pm_table, $pm_type=null, $pa_options=null) {
		global $g_theme_detail_for_type_cache;
		$vs_current_action = ($po_request = caGetOption('request', $pa_options, null)) ? $po_request->getAction() : null;
		if (isset($g_theme_detail_for_type_cache[$pm_table.'/'.$pm_type])) { return $g_theme_detail_for_type_cache[$pm_table.'/'.$pm_type.'/'.$vs_current_action]; }
		$o_config = caGetDetailConfig();
		$o_dm = Datamodel::load();

		$vs_preferred_detail = caGetOption('preferredDetail', $pa_options, null);

		if (!($vs_table = $o_dm->getTableName($pm_table))) { return null; }

		if ($pm_type) {
			$t_instance = $o_dm->getInstanceByTableName($vs_table, true);
			$vs_type = is_numeric($pm_type) ? $t_instance->getTypeCode($pm_type) : $pm_type;
		} else {
			$vs_type = null;
		}

		$va_detail_types = $o_config->getAssoc('detailTypes');

		$vs_detail_type = null;
		foreach($va_detail_types as $vs_code => $va_info) {
			if ($va_info['table'] == $vs_table) {
				$va_detail_aliases = caGetOption('aliases', $va_info, array(), array('castTo' => 'array'));

				if (is_null($pm_type) || !is_array($va_info['restrictToTypes']) || (sizeof($va_info['restrictToTypes']) == 0) || in_array($vs_type, $va_info['restrictToTypes'])) {
					// If the code matches the current url action use that in preference to anything else

					// does it have an alias?
					if ($vs_preferred_detail && ($vs_code == $vs_preferred_detail)) { return $vs_preferred_detail; }
					if ($vs_preferred_detail && in_array($vs_preferred_detail, $va_detail_aliases)) { return $vs_preferred_detail; }
					if ($vs_current_action && ($vs_code == $vs_current_action)) { return $vs_code; }
					if ($vs_current_action && in_array($vs_current_action, $va_detail_aliases)) { return $vs_current_action; }

					$vs_detail_type = $g_theme_detail_for_type_cache[$pm_table.'/'.$pm_type.'/'.$vs_current_action] = $g_theme_detail_for_type_cache[$vs_table.'/'.$vs_type.'/'.$vs_current_action] = $vs_code;
				}
			}
		}

		if (!$vs_detail_type) $g_theme_detail_for_type_cache[$pm_table.'/'.$pm_type] = $g_theme_detail_for_type_cache[$vs_table.'/'.$vs_type.'/'.$vs_current_action] = null;
		return $vs_detail_type;
	}
	# ---------------------------------------
	/**
	 *
	 *
	 */
	function caGetDisplayImagesForAuthorityItems($pm_table, $pa_ids, $pa_options=null) {
		$o_dm = Datamodel::load();
		if (!($t_instance = $o_dm->getInstanceByTableName($pm_table, true))) { return null; }
		if (method_exists($t_instance, "isRelationship") && $t_instance->isRelationship()) { return array(); }
		
		if ((!caGetOption("useRelatedObjectRepresentations", $pa_options, array())) && method_exists($t_instance, "getPrimaryMediaForIDs")) {
			// Use directly related media if defined
			$va_media = $t_instance->getPrimaryMediaForIDs($pa_ids, array($vs_version = caGetOption('version', $pa_options, 'icon')), $pa_options);
			$va_media_by_id = array();
			foreach($va_media as $vn_id => $va_media_info) {
				if(!is_array($va_media_info)) { continue; }
				$va_media_by_id[$vn_id] = $va_media_info['tags'][$vs_version];
			}
			if(sizeof($va_media_by_id)){
				return $va_media_by_id;
			}
		}

		if(!is_array($pa_options)){
			$pa_options = array();
		}
		$pa_access_values = caGetOption("checkAccess", $pa_options, array());
		$vs_access_wheres = '';
		if($pa_options['checkAccess']){
			$vs_access_wheres = " AND ca_objects.access IN (".join(",", $pa_access_values).") AND ca_object_representations.access IN (".join(",", $pa_access_values).")";
		}
		$va_path = array_keys($o_dm->getPath($vs_table = $t_instance->tableName(), "ca_objects"));
		$vs_pk = $t_instance->primaryKey();

		$va_params = array();

		$vs_linking_table = $va_path[1];


		$vs_rel_type_where = '';
		if (is_array($va_rel_types = caGetOption('relationshipTypes', $pa_options, null)) && sizeof($va_rel_types)) {
			$va_rel_types = caMakeRelationshipTypeIDList($vs_linking_table, $va_rel_types);
			if (is_array($va_rel_types) && sizeof($va_rel_types)) {
				$vs_rel_type_where = " AND ({$vs_linking_table}.type_id IN (?))";
				$va_params[] = $va_rel_types;
			}
		}

		if(is_array($pa_ids) && sizeof($pa_ids)) {
			$vs_id_sql = "AND {$vs_table}.{$vs_pk} IN (?)";
			$va_params[] = $pa_ids;
		}

		$vs_sql = "SELECT DISTINCT ca_object_representations.media, {$vs_table}.{$vs_pk}
			FROM {$vs_table}
			INNER JOIN {$vs_linking_table} ON {$vs_linking_table}.{$vs_pk} = {$vs_table}.{$vs_pk}
			INNER JOIN ca_objects ON ca_objects.object_id = {$vs_linking_table}.object_id
			INNER JOIN ca_objects_x_object_representations ON ca_objects_x_object_representations.object_id = ca_objects.object_id
			INNER JOIN ca_object_representations ON ca_object_representations.representation_id = ca_objects_x_object_representations.representation_id
			WHERE
				ca_objects_x_object_representations.is_primary = 1 {$vs_rel_type_where} {$vs_id_sql}
		";

		$o_db = $t_instance->getDb();

		$qr_res = $o_db->query($vs_sql, $va_params);
		$va_res = array();
		while($qr_res->nextRow()) {
			$va_res[$qr_res->get($vs_pk)] = $qr_res->getMediaTag("media", caGetOption('version', $pa_options, 'icon'));
		}
		return $va_res;
	}
	# ---------------------------------------
	/**
	 * class -> class name of <ul>
	 * options -> limit -> limit number of sets returned, role -> role in <ul> tag
	 */
	function caGetGallerySetsAsList($po_request, $vs_class, $pa_options=null){
		$o_config = caGetGalleryConfig();
		$va_access_values = caGetUserAccessValues($po_request);
 		$t_list = new ca_lists();
 		$vn_gallery_set_type_id = $t_list->getItemIDFromList('set_types', $o_config->get('gallery_set_type'));
 		$vs_set_list = "";
		if($vn_gallery_set_type_id){
			$t_set = new ca_sets();
			$va_sets = caExtractValuesByUserLocale($t_set->getSets(array('table' => 'ca_objects', 'checkAccess' => $va_access_values, 'setType' => $vn_gallery_set_type_id)));

			$vn_limit = caGetOption('limit', $pa_options, 100);
			$vs_role = caGetOption('role', $pa_options, null);
			if(sizeof($va_sets)){
				$vs_set_list = "<ul".(($vs_class) ? " class='".$vs_class."'" : "").(($vs_role) ? " role='".$vs_role."'" : "").">\n";

				$vn_c = 0;
				foreach($va_sets as $vn_set_id => $va_set){
					$vs_set_list .= "<li>".caNavLink($po_request, $va_set["name"], "", "", "Gallery", $vn_set_id)."</li>\n";
					$vn_c++;

					if ($vn_c >= $vn_limit) { break; }
				}
				$vs_set_list .= "</ul>\n";
			}
		}
		return $vs_set_list;
	}
	# ---------------------------------------
	/**
	 *
	 */
	function caGetPlaceholder($vs_type_code, $vs_placeholder_type = "placeholder_media_icon"){
		$o_config = caGetIconsConfig();
		$va_placeholders_by_type = $o_config->getAssoc("placeholders");
		$vs_placeholder = $o_config->get($vs_placeholder_type);
		if(is_array($va_placeholders_by_type[$vs_type_code])){
			$vs_placeholder = $va_placeholders_by_type[$vs_type_code][$vs_placeholder_type];
		}
		if(!$vs_placeholder){
			if($vs_placeholder_type == "placeholder_media_icon"){
				$vs_placeholder = "<i class='fa fa-picture-o fa-2x'></i>";
			}else{
				$vs_placeholder = "<i class='fa fa-picture-o fa-5x'></i>";
			}
		}
		return $vs_placeholder;
	}
	# ---------------------------------------
	/**
	 *
	 */
	function caGetLightboxDisplayName($o_lightbox_config = null){
		if(!$o_lightbox_config){ $o_lightbox_config = caGetLightboxConfig(); }
		$vs_lightbox_displayname = $o_lightbox_config->get("lightboxDisplayName");
		if(!$vs_lightbox_displayname){
			$vs_lightbox_displayname = _t("lightbox");
		}
		$vs_lightbox_displayname_plural = $o_lightbox_config->get("lightboxDisplayNamePlural");
		if(!$vs_lightbox_displayname_plural){
			$vs_lightbox_displayname_plural = _t("lightboxes");
		}
		$vs_lightbox_section_heading = $o_lightbox_config->get("lightboxSectionHeading");
		if(!$vs_lightbox_section_heading){
			$vs_lightbox_section_heading = _t("lightboxes");
		}
		return array("singular" => $vs_lightbox_displayname, "plural" => $vs_lightbox_displayname_plural, "section_heading" => $vs_lightbox_section_heading);
	}
	# ---------------------------------------
	/**
	 *
	 */
	function caGetClassroomDisplayName($o_classroom_config = null){
		if(!$o_classroom_config){ $o_classroom_config = caGetClassroomConfig(); }
		$vs_classroom_displayname = $o_classroom_config->get("classroomDisplayName");
		if(!$vs_classroom_displayname){
			$vs_classroom_displayname = _t("assignment");
		}
		$vs_classroom_displayname_plural = $o_classroom_config->get("classroomDisplayNamePlural");
		if(!$vs_classroom_displayname_plural){
			$vs_classroom_displayname_plural = _t("assignment");
		}
		$vs_classroom_section_heading = $o_classroom_config->get("classroomSectionHeading");
		if(!$vs_classroom_section_heading){
			$vs_classroom_section_heading = _t("classroom");
		}
		return array("singular" => $vs_classroom_displayname, "plural" => $vs_classroom_displayname_plural, "section_heading" => $vs_classroom_section_heading);
	}
	# ---------------------------------------
	function caDisplayLightbox($po_request){
		if($po_request->isLoggedIn() && !$po_request->config->get("disable_lightbox") && ($po_request->config->get("disable_classroom") || !in_array($po_request->user->getPreference('user_profile_classroom_role'), array('STUDENT', 'EDUCATOR')))){
			return true;
		}else{
			return false;
		}
	}
	# ---------------------------------------
	function caDisplayClassroom($po_request){
		if($po_request->isLoggedIn() && !$po_request->config->get("disable_classroom") && in_array($po_request->user->getPreference('user_profile_classroom_role'), array('STUDENT', 'EDUCATOR'))){
			return true;
		}else{
			return false;
		}
	}
	# ---------------------------------------
	function caGetAddToSetInfo($po_request){
		$va_link_info = array();
		if(!$po_request->isLoggedIn() && !$po_request->config->get("disable_lightbox")){
			$o_lightbox_config = caGetLightboxConfig();
			$va_link_info["controller"] = "Lightbox";
			$va_link_info["icon"] = $o_lightbox_config->get("addToLightboxIcon");
			if(!$va_link_info["icon"]){
				$va_link_info["icon"] = "<i class='fa fa-suitcase'></i>";
			}
			$va_lightboxDisplayName = caGetLightboxDisplayName($o_lightbox_config);
			$va_link_info["name_singular"] = $va_lightboxDisplayName["singular"];
			$va_link_info["name_plural"] = $va_lightboxDisplayName["plural"];
			$va_link_info["section_heading"] = $va_lightboxDisplayName["section_heading"];
			$vs_classroom_name = "";
			if(!$po_request->config->get("disable_classroom")){
				$o_classroom_config = caGetClassroomConfig();
				$va_classroomDisplayName = caGetClassroomDisplayName($o_lightbox_config);			
				$vs_classroom_name = $va_classroomDisplayName["singular"];
			}
			$va_link_info["link_text"] = _t("Login to add to %1", $va_link_info["name_singular"].(($vs_classroom_name) ? "/".$vs_classroom_name : ""));
			return $va_link_info;
		}
		if(caDisplayLightbox($po_request)){
			$o_lightbox_config = caGetLightboxConfig();
			$va_link_info["controller"] = "Lightbox";
			$va_link_info["icon"] = $o_lightbox_config->get("addToLightboxIcon");
			if(!$va_link_info["icon"]){
				$va_link_info["icon"] = "<i class='fa fa-suitcase'></i>";
			}
			$va_lightboxDisplayName = caGetLightboxDisplayName($o_lightbox_config);
			$va_link_info["name_singular"] = $va_lightboxDisplayName["singular"];
			$va_link_info["name_plural"] = $va_lightboxDisplayName["plural"];
			$va_link_info["section_heading"] = $va_lightboxDisplayName["section_heading"];
			$va_link_info["link_text"] = _t("Add to %1", $va_link_info["name_singular"]);
			return $va_link_info;
		}
		if(caDisplayClassroom($po_request)){
			$o_classroom_config = caGetClassroomConfig();
			$va_link_info["controller"] = "Classroom";
			$va_link_info["icon"] = $o_classroom_config->get("addToClassroomIcon");
			if(!$va_link_info["icon"]){
				$va_link_info["icon"] = "<i class='fa fa-suitcase'></i>";
			}
			$va_classroomDisplayName = caGetClassroomDisplayName($o_classroom_config);
			$va_link_info["name_singular"] = $va_classroomDisplayName["singular"];
			$va_link_info["name_plural"] = $va_classroomDisplayName["plural"];
			$va_link_info["section_heading"] = $va_classroomDisplayName["section_heading"];
			$va_link_info["link_text"] = _t("Add to %1", $va_link_info["name_singular"]);	
			return $va_link_info;
		}
		return false;
	}
	
	# ---------------------------------------
	/**
	 *
	 */
	function caSetAdvancedSearchFormInView($po_view, $ps_function, $ps_view, $pa_options=null) {
		require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
		
		if (!($va_search_info = caGetInfoForAdvancedSearchType($ps_function))) { return null; }
		
		$o_dm = Datamodel::load();
 		if (!($pt_subject = $o_dm->getInstanceByTableName($va_search_info['table'], true))) { return null; }
 		
		$po_request = caGetOption('request', $pa_options, null);
		$ps_controller = caGetOption('controller', $pa_options, null);
		$ps_form_name = caGetOption('formName', $pa_options, 'caAdvancedSearch');
		
		$vs_script = null;
		
		$pa_tags = $po_view->getTagList($ps_view);
		if (!is_array($pa_tags) || !sizeof($pa_tags)) { return null; }
		
		$va_form_elements = array();
		
		$vb_submit_or_reset_set = false;
		foreach($pa_tags as $vs_tag) {
			$va_parse = caParseTagOptions($vs_tag);
			$vs_tag_proc = $va_parse['tag'];
			$va_opts = $va_parse['options'];
			$va_opts['checkAccess'] = $po_request ? caGetUserAccessValues($po_request) : null;
			
			if (($vs_default_value = caGetOption('default', $va_opts, null)) || ($vs_default_value = caGetOption($vs_tag_proc, $va_default_form_values, null))) { 
				$va_default_form_values[$vs_tag_proc] = $vs_default_value;
				unset($va_opts['default']);
			} 
		
			$vs_tag_val = null;
			switch(strtolower($vs_tag_proc)) {
				case 'submit':
					$po_view->setVar($vs_tag, "<a href='#' class='caAdvancedSearchFormSubmit'>".((isset($va_opts['label']) && $va_opts['label']) ? $va_opts['label'] : _t('Submit'))."</a>");
					$vb_submit_or_reset_set = true;
					break;
				case 'submittag':
					$po_view->setVar($vs_tag, "<a href='#' class='caAdvancedSearchFormSubmit'>");
					$vb_submit_or_reset_set = true;
					break;
				case 'reset':
					$po_view->setVar($vs_tag, "<a href='#' class='caAdvancedSearchFormReset'>".((isset($va_opts['label']) && $va_opts['label']) ? $va_opts['label'] : _t('Reset'))."</a>");
					$vb_submit_or_reset_set = true;
					break;
				case 'resettag':
					$po_view->setVar($vs_tag, "<a href='#' class='caAdvancedSearchFormReset'>");
					$vb_submit_or_reset_set = true;
					break;
				case '/resettag':
				case '/submittag':
					$po_view->setVar($vs_tag, "</a>");
					break;
				default:
					if (preg_match("!^(.*):label$!", $vs_tag_proc, $va_matches)) {
						$po_view->setVar($vs_tag, $vs_tag_val = $t_subject->getDisplayLabel($va_matches[1]));
					} elseif (preg_match("!^(.*):boolean$!", $vs_tag_proc, $va_matches)) {
						$po_view->setVar($vs_tag, caHTMLSelect($vs_tag_proc.'[]', array(_t('AND') => 'AND', _t('OR') => 'OR', 'AND NOT' => 'AND NOT'), array('class' => 'caAdvancedSearchBoolean')));
					} elseif (preg_match("!^(.*):relationshipTypes$!", $vs_tag_proc, $va_matches)) {
						$va_tmp = explode(".", $va_matches[1]);
						
						$vs_select = '';
						if ($t_rel = $pt_subject->getRelationshipInstance($va_tmp[0])) {
							$vs_select = $t_rel->getRelationshipTypesAsHTMLSelect($va_tmp[0], null, null, array_merge(array('class' => 'caAdvancedSearchRelationshipTypes'), $va_opts, array('name' => $vs_tag_proc.'[]')), $va_opts);
						}
						$po_view->setVar($vs_tag, $vs_select);
					} else {
						$va_opts['asArrayElement'] = true;
						if (isset($va_opts['restrictToTypes']) && $va_opts['restrictToTypes'] && !is_array($va_opts['restrictToTypes'])) { 
							$va_opts['restrictToTypes'] = preg_split("![,;]+!", $va_opts['restrictToTypes']);
						}
						
						// Relationship type restrictions
						if (isset($va_opts['restrictToRelationshipTypes']) && $va_opts['restrictToRelationshipTypes'] && !is_array($va_opts['restrictToRelationshipTypes'])) { 
							$va_opts['restrictToRelationshipTypes'] = preg_split("![,;]+!", $va_opts['restrictToRelationshipTypes']);
						}
						
						// Exclude values
						if (isset($va_opts['exclude']) && $va_opts['exclude'] && !is_array($va_opts['exclude'])) { 
							$va_opts['exclude'] = preg_split("![,;]+!", $va_opts['exclude']);
						}
						
						if ($vs_rel_types = join(";", caGetOption('restrictToRelationshipTypes', $va_opts, array()))) { $vs_rel_types = "/{$vs_rel_types}"; }
			
						if ($vs_tag_val = $pt_subject->htmlFormElementForSearch($po_request, $vs_tag_proc, $va_opts)) {
							switch(strtolower($vs_tag_proc)) {
								case '_fulltext':		// Set default label for _fulltext if needed
									if(!isset($va_opts['label'])) { $va_opts['label'] = _t('Keywords'); }
									break;
							}
							$vs_tag_val .= caHTMLHiddenInput("{$vs_tag_proc}{$vs_rel_types}_label", array('value' => isset($va_opts['label']) ? str_replace("_", " ", urldecode($va_opts['label'])) : $pt_subject->getDisplayLabel($vs_tag_proc)));	// set display labels for search criteria
							$po_view->setVar($vs_tag, $vs_tag_val);
						}
						
						$va_tmp = explode('.', $vs_tag_proc);
						if((($t_element = ca_metadata_elements::getInstance($va_tmp[1])) && ($t_element->get('datatype') == 0))) {
							if (is_array($va_elements = $t_element->getElementsInSet())) {
								foreach($va_elements as $va_element) {
									if ($va_element['datatype'] > 0) {
										$va_form_elements[] = $va_tmp[0].'.'.$va_tmp[1].'.'.$va_element['element_code'].$vs_rel_types;	// add relationship types to field name
									}
								}
							}
							break;
						}
					}
					if ($vs_tag_val) { $va_form_elements[] = $vs_tag_proc.$vs_rel_types; }		// add relationship types to field name
					break;
			}
		}
		
		if($vb_submit_or_reset_set) {
			$vs_script = "<script type='text/javascript'>
			jQuery('.caAdvancedSearchFormSubmit').bind('click', function() {
				jQuery('#caAdvancedSearch').submit();
				return false;
			});
			jQuery('.caAdvancedSearchFormReset').bind('click', function() {
				jQuery('#caAdvancedSearch').find('input[type!=\"hidden\"],textarea').val('');
				jQuery('#caAdvancedSearch').find('select.caAdvancedSearchBoolean').val('AND');
				jQuery('#caAdvancedSearch').find('select').prop('selectedIndex', 0);
				return false;
			});
			jQuery(document).ready(function() {
				var f, defaultValues = ".json_encode($va_default_form_values).", defaultBooleans = ".json_encode($va_default_form_booleans).";
				for (f in defaultValues) {
					var f_proc = f + '[]';
					jQuery('input[name=\"' + f_proc+ '\"], textarea[name=\"' + f_proc+ '\"], select[name=\"' + f_proc+ '\"]').each(function(k, v) {
						if (defaultValues[f][k]) { jQuery(v).val(defaultValues[f][k]); } 
					});
				}
				for (f in defaultBooleans) {
					var f_proc = f + '[]';
					jQuery('select[name=\"' + f_proc+ '\"].caAdvancedSearchBoolean').each(function(k, v) {
						if (defaultBooleans[f][k]) { jQuery(v).val(defaultBooleans[f][k]); }
					});
				}
			});
			</script>\n";
		}
		
		$po_view->setVar("form", caFormTag($po_request, "{$ps_function}", $ps_form_name, $ps_controller, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'submitOnReturn' => true)));
 		$po_view->setVar("/form", $vs_script.caHTMLHiddenInput("_advancedFormName", array("value" => $ps_function)).caHTMLHiddenInput("_formElements", array("value" => join('|', $va_form_elements))).caHTMLHiddenInput("_advanced", array("value" => 1))."</form>");
 			
		return $va_form_elements;
	}
	# ---------------------------------------
	/**
	 *
	 */
	function caCheckLightboxView($pa_options = null){
		if (!($ps_view = caGetOption('view', $pa_options, null)) && ($o_request = caGetOption('request', $pa_options, null))) { $ps_view = $o_request->getParameter('view', pString); }
		if(!in_array($ps_view, array('thumbnail', 'map', 'timeline', 'timelineData', 'pdf', 'list', 'xlsx', 'pptx'))) {
			$ps_view = caGetOption('default', $pa_options, 'thumbnail');
		}
		return $ps_view;
	}
	# ---------------------------------------
	/**
	 * Generate link to change current locale.
	 *
	 * @param RequestHTTP $po_request The current request.
	 * @param string $ps_locale ISO locale code (Ex. en_US) to change to.
	 * @param string $ps_classname CSS class name(s) to include in <a> tag.
	 * @param array $pa_attributes Optional attributes to include in <a> tag. [Default is null]
	 * @param array $pa_options Options to be passed to caNavLink(). [Default is null]
	 * @return string 
	 *
	 * @seealso caNavLink()
	 */
	function caChangeLocaleLink($po_request, $ps_locale, $ps_content, $ps_classname, $pa_attributes=null, $pa_options=null) {
		$va_params = $po_request->getParameters(['GET', 'REQUEST', 'PATH']);
		$va_params['lang'] = $ps_locale;
		return caNavLink($po_request, $ps_content, $ps_classname, '*', '*', '*', $va_params, $pa_attributes, $pa_options);
	}
	# ---------------------------------------
	/** 
	 * Returns number of global values defined in the current theme
	 *
	 * @return int
	 */
	function caGetGlobalValuesCount() {
		$o_config = Configuration::load();
		$va_template_values = $o_config->getAssoc('global_template_values');
		return is_array($va_template_values) ? sizeof($va_template_values) : 0;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * 
	 */
	function caGetComparisonList($po_request, $ps_table, $pa_options=null) {
		if (!is_array($va_comparison_list = $po_request->session->getVar("{$ps_table}_comparison_list"))) { $va_comparison_list = []; }
		
		// Get title template from config
		$va_compare_config = $po_request->config->get('compare_images');
		if (!is_array($va_compare_config = $va_compare_config[$ps_table])) { $va_compare_config = []; }
		$va_display_list = caProcessTemplateForIDs(caGetOption('title_template', $va_compare_config, "^{$ps_table}.preferred_labels"), $ps_table, $va_comparison_list, ['returnAsArray' => true]);
		
		$va_list = [];
		foreach($va_comparison_list as $vn_i => $vn_id) {
			$va_list[$vn_id] = $va_display_list[$vn_i];
		}
		
		return $va_list;
	}
	# ---------------------------------------