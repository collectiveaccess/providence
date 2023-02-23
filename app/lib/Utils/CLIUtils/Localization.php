<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Localization.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
trait CLIUtilsLocalization { 
	# -------------------------------------------------------
	/**
	 * Extract strings from theme for translation
	 */
	public static function extract_strings_for_translation($opts=null) {	
		$theme = $opts->getOption('theme');
		if(!$theme) { $theme = __CA_THEME__; }
		if(!file_exists(__CA_THEMES_DIR__."/{$theme}")) { 
			CLIUtils::addError(_t('Theme %1 does not exist', $theme));
			return null;
		}
		$locale = $opts->getOption('locale');
		if(strlen($locale) && !preg_match("!^[a-z]{2}_[A-Z]{2,3}$!", $locale)) {
			CLIUtils::addError(_t('Locale %1 is not valid', $locale));
			return null;
		}
		$file = $opts->getOption('file');
		if(!is_writeable(pathinfo($file, PATHINFO_DIRNAME))) { 
			CLIUtils::addError(_t('Cannot write to %1', $file));
			return null;
		}
		$team = $opts->getOption('team');
		$extracted_strings = [];
		
		$directories = [__CA_THEMES_DIR__."/default", __CA_THEMES_DIR__."/{$theme}", __CA_BASE_DIR__."/app/models", __CA_BASE_DIR__."/app/lib", __CA_BASE_DIR__."/app/helpers", __CA_BASE_DIR__."/app/conf"];
		
		$file_count = 0;
		foreach($directories as $d) {
			$files = caGetDirectoryContentsAsList($d);
			print CLIProgressBar::start(sizeof($files), _t('Processing %1', pathinfo($d, PATHINFO_BASENAME)));
			
			foreach($files as $f) {
				CLIProgressBar::setMessage(_t("Processing %1: %2", pathinfo($d, PATHINFO_BASENAME), pathinfo($f, PATHINFO_BASENAME)));
				print CLIProgressBar::next();
				
				if(!file_exists($f)) { continue; }
				$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
				if(!in_array($ext, ['php', 'conf'])) { continue; }
				$is_conf = ($ext === 'conf');
				
				$file_count++;
				$r = fopen($f, "r");

				while($line = fgets($r)) {
					// _() construction used in config files
					if($is_conf) {
						$strings = preg_match_all("!_\([\"]{0,1}([^\"\)]+?)[\"]{0,1}\)!", $line, $m);
	
						$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
							return preg_match("![A-Za-z0-9]+!", $v);
						}));
					}
					
					// _t() construction used in code
					$strings = preg_match_all("!_t\([\"\']{1}([^\"\)]+?)[\"\']{1}\)!", $line, $m);
	
					$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
						return preg_match("![A-Za-z0-9]+!", $v);
					}));
	
					// <t>...</t> construction used in templates and view files
					$strings = preg_match_all("!<t>(.*?)</t>!", $line, $m);
	
					$extracted_strings = array_merge($extracted_strings, array_filter($m[1], function($v) {
						return preg_match("![A-Za-z0-9]+!", $v);
					}));
				}
			}
			print CLIProgressBar::finish();
		}
		$extracted_strings = array_unique($extracted_strings);


		$out = fopen($file, "w");
		
		$headers = [
			"Project-Id-Version: ".__CA_APP_DISPLAY_NAME__."\\n",
			"POT-Creation-Date: ".date('t')."\\n",
			"PO-Revision-Date: ".date('t')."\\n",
			"Last-Translator: ".__CA_ADMIN_EMAIL__."\\n",
			"MIME-Version: 1.0\\n",
			"Content-Type: text/plain; charset=UTF-8\\n",
			"Content-Transfer-Encoding: 8bit\\n",
			"Plural-Forms: nplurals=2; plural=(n != 1);\\n",
			"X-Generator: CollectiveAccess ".__CollectiveAccess__."\\n"
		];
		if($locale) {
			$headers[] = "Language: {$locale}\\n";
		}	
		if($team) {
			$headers[] = "Language-Team: {$team}\\n";
		}
		fputs($out, "msgid \"\"\nmsgstr \"\"\n");
		foreach($headers as $h) {
			fputs($out, "\"{$h}\"\n");
		}
		fputs($out, "\n");

		foreach($extracted_strings as $s) {
			$s = stripslashes($s);
			fputs($out, "msgid \"{$s}\"\n");
			fputs($out, "msgstr \"\"\n\n");
		}
		print "\n\n";
		
		CLIUtils::addMessage(_t('Extracted %1 strings from %2 files into %3', sizeof($extracted_strings), $file_count, realpath($file)));
	}
	# -------------------------------------------------------
	public static function extract_strings_for_translationParamList() {
		return [
			"theme|g=s" => _t('Theme to extract strings from. If omitted the currently configured theme is used.'),
			"locale|l=s" => _t('Locale of translation.'),
			"file|f=s" => _t('File to write strings to.'),
			"team|t=s" => _t('Language team name. If omitted the current application name'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function extract_strings_for_translationUtilityClass() {
		return _t('Localization');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function extract_strings_for_translationShortHelp() {
		return _t('Generate gettext PO file for translation.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function extract_strings_for_translationHelp() {
		return _t('Generate gettext PO file for translation.');
	}
	# -------------------------------------------------------
	/**
	 * Extract strings from theme for translation
	 */
	public static function translate_system($opts=null) {	
		// $locale = $opts->getOption('locale');
// 		if(strlen($locale) && !preg_match("!^[a-z]{2}_[A-Z]{2,3}$!", $locale)) {
// 			CLIUtils::addError(_t('Locale %1 is not valid', $locale));
// 			return null;
// 		}
		
		$lm = new \CA\LanguageTranslationManager();
		$overwrite = false;
		
		$available_locales = ca_locales::getCataloguingLocaleCodes();
		
		$default_locale_id = ca_locales::codeToID(__CA_DEFAULT_LOCALE__);
		foreach($available_locales as $locale) {
			if($locale === __CA_DEFAULT_LOCALE__) { continue; }
			CLIUtils::addMessage(_t('Translating to %1', $locale));	
			
			$locale_id = ca_locales::codeToID($locale);
			
			// Translate metadata elements
			CLIUtils::addMessage(_t('Translate metadata elements'));
			$elements = ca_metadata_elements::getElementsAsList();
			foreach($elements as $element_id => $element_info) {
				$t_element = ca_metadata_elements::getInstance($element_id);
				$element_code = $t_element->get('ca_metadata_elements.element_code');
				
				$labels = $t_element->getLabels();
				$labels = array_shift($labels);
				
				if($overwrite || !is_array($labels[$locale_id])) {
					$def_label = $labels[$default_locale_id] ?? [];
					$def_label = array_shift($def_label);
					if(!is_array($def_label)) { 
						CLIUtils::addError(_t('Could not translate element %1: no label in default locale', $element_code));
						continue;
					}
					if(strlen($def_label['name'])) {
						CLIUtils::addMessage(_t('Translate [%1]: %2', $element_code, $def_label['name']));
						$tname = $lm->translate($def_label['name'], $locale);
						$tdesc = strlen($def_label['description']) ? $lm->translate($def_label['description'], $locale) : '';
						$t_element->replaceLabel(['name' => $tname, 'description' => $tdesc], $locale, null, $element_info['is_preferred']);
					}
				}
			}
			
			// Translate list items
			CLIUtils::addMessage(_t('Translate lists'));
			$t_list = new ca_lists();
			$lists = $t_list->getListOfLists();
			foreach($lists as $list_id => $list) {
				$t_list->load($list_id);
				$list_code = $t_list->get('list_code');
				if(!is_array($list = ($list[$default_locale_id] ?? null))) {
					CLIUtils::addError(_t('Could not translate list %1: no label in default locale', $list_code));
					continue;
				}
				$list_name = $list['name'];
				
				$labels = $t_list->getLabels();
				$labels = array_shift($labels);
				if($overwrite || !is_array($labels[$locale_id])) {
					$def_label = $labels[$default_locale_id] ?? [];
					$def_label = array_shift($def_label);
					if(!is_array($def_label)) { 
						CLIUtils::addError(_t('Could not translate list %1: no label in default locale', $element_code));
						continue;
					}
					
					if(strlen($def_label['name'])) {
						CLIUtils::addMessage(_t('Translate [%1]: %2', $list_code, $def_label['name']));
						$tname = $lm->translate($def_label['name'], $locale);
						$tdesc = strlen($def_label['description']) ? $lm->translate($def_label['description'], $locale) : '';
						$t_list->replaceLabel(['name' => $tname, 'description' => $tdesc], $locale, null, true);
					}
				}
				
				// translate list items
				$items = $t_list->getItemsForList($list_id);
				foreach($items as $item_id => $item) {
					$t_item = new ca_list_items($item_id);
					$list_code = caGetListCode($t_item->get('list_id'));
					$item_code = $t_item->get('idno');
					
					$labels = $t_item->getLabels();
					$labels = array_shift($labels);
					if($overwrite || !is_array($labels[$locale_id])) {
						$def_label = $labels[$default_locale_id] ?? [];
						$def_label = array_shift($def_label);
						if(!is_array($def_label)) { 
							CLIUtils::addError(_t('Could not translate list item %1 in list %2: no label in default locale', $item_code, $list_code));
							continue;
						}
					
						if(strlen($def_label['name_singular'])) {
							CLIUtils::addMessage(_t('Translate [%1][%2]: %3', $list_code, $item_code, $def_label['name_singular']));
							$tnamesing = $lm->translate($def_label['name_singular'], $locale);
							$tnameplur = $lm->translate($def_label['name_plural'], $locale);
							$tdesc = strlen($def_label['description']) ? $lm->translate($def_label['description'], $locale) : '';
							$t_item->replaceLabel(['name_singular' => $tnamesing, 'name_plural' => $tnameplur, 'description' => $tdesc], $locale, null, true);
						}
					}
				}
			}
		}
		
		CLIUtils::addMessage(_t('Completed'));
	}
	# -------------------------------------------------------
	public static function translate_systemParamList() {
		return [
		// 	"theme|g=s" => _t('Theme to extract strings from. If omitted the currently configured theme is used.'),
// 			"locale|l=s" => _t('Locale of translation.'),
// 			"file|f=s" => _t('File to write strings to.'),
// 			"team|t=s" => _t('Language team name. If omitted the current application name'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function translate_systemUtilityClass() {
		return _t('Localization');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function translate_systemShortHelp() {
		return _t('Translate system into the specified language.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function translate_systemHelp() {
		return _t('Translate list items, metadata elements, relationship types and user interfaces into the specified language.');
	}
	# -------------------------------------------------------
}
