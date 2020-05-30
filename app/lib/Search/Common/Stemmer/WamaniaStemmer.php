<?php
/**
 * ----------------------------------------------------------------------
 * WamaniaStemmer.php
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

use Wamania\Snowball\StemmerManager;

require_once(__CA_LIB_DIR__ . '/Search/Common/IStemmer.php');

class WamaniaStemmer implements IStemmer {

    protected static $opo_manager = null;

    public function __construct() {
        if (!isset(static::$opo_manager)){
            static::$opo_manager = new StemmerManager();
        }
    }

    /**
     * Stem a word on a language
     *
     * @param $ps_word
     * @param $ps_language
     *
     * @return string
     * @throws \Wamania\Snowball\NotFoundException
     */
    static public function stem($ps_word, $ps_language): string {
        $stem = static::$opo_manager->stem($ps_word, $ps_language);
        return $stem;
    }

}