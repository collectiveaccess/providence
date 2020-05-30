<?php
/**
 * ----------------------------------------------------------------------
 * StemmerFactory.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage Search
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * ----------------------------------------------------------------------
 *
 */

require_once(__CA_LIB_DIR__ . '/Search/Common/IStemmer.php');

class PeclStemmer implements IStemmer {

    /**
     * Stem a word on a language
     *
     * @param $ps_word
     * @param $ps_language
     *
     * @return string
     */
    static public function stem($ps_word, $ps_language): string {
        if (isset(static::$opa_stem_cache[$ps_word])) {
            return static::$opa_stem_cache[$ps_word];
        }
        // Use PECL function if it is installed
        if (function_exists('stem')) {
            static::$opa_stem_cache[$ps_word] = stem($ps_word, self::lang2code($ps_language));
            return static::$opa_stem_cache[$ps_word];
        }
        return "";
    }

    #
    # Convert language code to PECL Stem language constant
    #
    private static function lang2code($lang) {
        if (!function_exists('stem')) { return null; }
        switch($lang) {
            case 'en':
                return STEM_ENGLISH;
                break;
            case 'da':
                return STEM_DANISH;
                break;
            case 'nl':
                return STEM_DUTCH;
                break;
            case 'fi':
                return STEM_FINNISH;
                break;
            case 'fr':
                return STEM_FRENCH;
                break;
            case 'de':
                return STEM_GERMAN;
                break;
            case 'it':
                return STEM_ITALIAN;
                break;
            case 'no':
                return STEM_NORWEGIAN;
                break;
            case 'pt':
                return STEM_PORTUGUESE;
                break;
            case 'ru':
                return STEM_RUSSIAN;
                break;
            case 'sp':
                return STEM_SPANISH;
                break;
            case 'sv':
                return STEM_SWEDISH;
                break;
            default:
                return STEM_PORTER;
        }
    }

}