<?php
/** ---------------------------------------------------------------------
 * app/helpers/localizationHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2026 Whirl-i-Gig
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
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\FuncCall;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

# ----------------------------------------------------------------------
# String localization functions (getText)
# ----------------------------------------------------------------------
/**
 * Translates the string in $ps_key into the current locale
 * You interpolate values into the returned string by embedding numbered placeholders in $ps_key
 * in the format %n (where n is a number). Each parameter passed after $ps_key corresponds to a
 * placeholder (ex. the first parameter replaces %1, the second %2)
 */

MemoryCache::flush('translation');

$g_translations = Configuration::load('translations.conf');

$g_translation_strings = $g_translations->get('strings');
$g_translation_replacements = $g_translations->get('replacements');
$g_translation_cache = [];

# ----------------------------------------
/**
 *
 */
function _t($ps_key) {
	if(!$ps_key) { return ''; }
	global $_, $_locale, $g_translation_strings, $g_translation_replacements, $g_translation_cache;
	
	if (
		isset($g_translation_strings[$ps_key]) && 
		(
			is_string($g_translation_strings[$ps_key]) || 
			(is_array($g_translation_strings[$ps_key]) && isset($g_translation_strings[$ps_key][(string)$_locale]))
		)
	) { return is_array($g_translation_strings[$ps_key]) ? $g_translation_strings[$ps_key][(string)$_locale] : $g_translation_strings[$ps_key]; }
	
	if((defined('__CA_DONT_CACHE_TRANSLATIONS__') && __CA_DONT_CACHE_TRANSLATIONS__) || !isset($g_translation_cache[$ps_key])) {
		if (is_array($_)) {
			$vs_str = $ps_key;
			foreach($_ as $o_locale) {
				if ($o_locale->isTranslated($ps_key)) {
					$vs_str = $o_locale->_($ps_key);
					break;
				}
			}
		} else {
			if (!is_object($_)) {
				$vs_str = $ps_key;
			} else {
				$vs_str = $_->_($ps_key);
			}
		}
		
		if(is_array($g_translation_replacements)) {
		    $vs_str = str_replace(array_keys($g_translation_replacements), array_values($g_translation_replacements), $vs_str);
		}
		
		$g_translation_cache[$ps_key] = $vs_str;
	} else {
		$vs_str = $g_translation_cache[$ps_key];
	}

	if (sizeof($va_args = func_get_args()) > 1) {
		$vn_num_args = sizeof($va_args) - 1;
		for($vn_i=$vn_num_args; $vn_i >= 1; $vn_i--) {
			$vs_str = str_replace("%{$vn_i}", is_array($va_args[$vn_i]) ? join('; ', $va_args[$vn_i]) : $va_args[$vn_i], $vs_str);
		}
	}
	return $vs_str;
}
# ----------------------------------------
/**
 * The same as _t(), but rather than returning the translated string, it prints it
 */
function _p($ps_key) {
	if(!$ps_key) { return; }
	global $_, $g_translation_cache;

	if (!sizeof(func_get_args()) & isset($g_translation_cache[$ps_key])) {
		print $g_translation_cache[$ps_key]; return;
	}

	if (is_array($_)) {
		$vs_str = $ps_key;
		foreach($_ as $o_locale) {
			if ($o_locale->isTranslated($ps_key)) {
				$vs_str = $o_locale->_($ps_key);
				break;
			}
		}
	} else {
		if (!is_object($_)) {
			$vs_str = $ps_key;
		} else {
			$vs_str = $_->_($ps_key);
		}
	}

	if (sizeof($va_args = func_get_args()) > 1) {
		$vn_num_args = sizeof($va_args) - 1;
		for($vn_i=$vn_num_args; $vn_i >= 1; $vn_i--) {
			$vs_str = str_replace("%{$vn_i}", $va_args[$vn_i], $vs_str);
		}
	}

	$g_translation_cache[$ps_key] = $vs_str;
	print $vs_str;
	return;
}
# ----------------------------------------
/**
 *
 */
function caGetTextStringsFromPHPFile(string $filepath) : ?array {
	$code = file_get_contents($filepath);
	$parser = (new ParserFactory())->createForNewestSupportedVersion();
	try {
		$ast = $parser->parse($code);
	} catch (Error $e) {
		throw new LocalizationGetTextStringException(_t('Could not extract strings for localization from file %1: %2', $filepath, $e->getMessage()));
	}

	$traverser = new NodeTraverser();
	$traverser->addVisitor($c = new class extends NodeVisitorAbstract {
		var $strings = [];
		public function enterNode(Node $node) {
			if ($node instanceof \PhpParser\Node\Expr\FuncCall) {
				if(($node->name == '_t') || ($node->name == '_p')) {
					$arg = $node->args[0]->jsonSerialize();
					$line = $arg['attributes']['startLine'];
					$str = $node->args[0]->value->jsonSerialize();
					$value = $str['value'];
					if(!strlen($value)) { return; }
					$this->strings[] = [
						'text' => caEscapeStringforGetTextPOT($value),
						'line' => $line
					];
				} else {
					// find <t>...</t> templates in function calls
					foreach($node->args as $arg) {
						$arg = $arg->jsonSerialize();
						$line = $arg['attributes']['startLine'];
						$str = $arg['value']->jsonSerialize();
						$argvalue = $str['value'];
						
						if(preg_match_all("!<t>(.*?)</t>!s", $argvalue, $m)) {
							foreach($m[1] as $value) {
								if(!strlen($value) || ($value === '(.*?)')) { continue; }
								$this->strings[] = [
									'text' => caEscapeStringforGetTextPOT($value),
									'line' => $line
								];
							}	
						}
					}
				}
			} elseif($node instanceof PhpParser\Node\Stmt\InlineHTML) {
				// find <t>...</t> templates in inline HTML
				if(preg_match_all("!<t>(.*?)</t>!s", (string)$node->value, $m)) {
					$n = $node->jsonSerialize();
					$line = $n['attributes']['startLine'];
					foreach($m[1] as $value) {
						if(!strlen($value) || ($value === '(.*?)')) { continue; }
						$this->strings[] = [
							'text' => caEscapeStringforGetTextPOT($value),
							'line' => $line
						];
					}	
				}
			}
		}
	});
	$traverser->traverse($ast);
	return $c->strings;
}
# ----------------------------------------
/**
 *
 */
function caEscapeStringforGetTextPOT(string $value) : string {
	$value = str_replace('"', '\\"', $value);
	$value = str_replace("\t", '\\t', $value);
	$value = str_replace("\n", '\\n', $value);
	$value = str_replace("\r", '\\n', $value);
	return $value;
}
# ----------------------------------------
