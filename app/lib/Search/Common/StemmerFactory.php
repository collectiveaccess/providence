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
 *
 * ----------------------------------------------------------------------
 *
 */

trait IPlugin {
    static private $ops_plugin_names = null;
    static private $plugin_path = null;
    static private $ops_file_pattern = "/^([A-Za-z_]+[A-Za-z0-9_]*).php$/";

    static public function skip_file($file, $dir) {
        return false;
    }

    static public function getPluginNames() {
        if (is_array(static::$ops_plugin_names)) {
            return static::$ops_plugin_names;
        }

        static::$ops_plugin_names = array();
        $dir = opendir(static::$plugin_path);
        if (!$dir) {
            throw new ApplicationException(_t('Cannot open plugin directory %1', static::$plugin_path));
        }

        while (($plugin = readdir($dir))!==false) {
            if (static::skip_file($plugin, $dir)) {
                continue;
            }
            if (preg_match(static::$ops_file_pattern, $plugin, $m)) {
                static::$ops_plugin_names[] = $m[1];
            }
        }

        sort(static::$ops_plugin_names);

        return static::$ops_plugin_names;
    }

    static public function loadPluginFiles(): void {
        foreach (static::getPluginNames() as $vs_name) {
            require_once(static::$plugin_path . "/{$vs_name}.php");
        }
    }

    /**
     * @return null
     */
    public static function getPluginPath() {
        return static::$plugin_path;
    }

    /**
     * @param null $plugin_path
     *
     * @throws ApplicationException
     */
    public static function setPluginPath($plugin_path): void {
        static::$plugin_path = $plugin_path;
        static::$ops_plugin_names = null;
        static::getPluginNames();
    }

    /**
     * @return string
     */
    public static function getFilePattern(): string {
        return self::$ops_file_pattern;
    }

    /**
     * @param string $vs_file_pattern
     */
    public static function setFilePattern(string $vs_file_pattern): void {
        self::$ops_file_pattern = $vs_file_pattern;
    }

}

trait Singleton {

    protected static $_instance = array();

    /**
     * Protected class constructor to prevent direct object creation.
     */
    protected function __construct() {
    }

    /**
     * Prevent object cloning
     */
    final protected function __clone() {
    }

    /**
     * To return new or existing Singleton instance of the class from which it is called.
     * As it sets to final it can't be overridden.
     *
     * @return object Singleton instance of the class.
     */
    final public static function get_instance() {

        /**
         * Returns name of the class the static method is called in.
         */
        $called_class = get_called_class();

        if (!isset(static::$_instance[$called_class])) {

            static::$_instance[$called_class] = new $called_class();

        }

        return static::$_instance[$called_class];

    }

}

class StemmerFactory {

    use IPlugin;
    use Singleton;

    /**
     * Stem a word on a language
     *
     * @param $ps_word
     * @param $ps_language
     *
     * @return string
     */

    private $ops_stemmer_path = __DIR__ . '/Stemmer';

    public function __construct($plugin_path = null) {
        if (!$plugin_path) {
            $plugin_path = $this->ops_stemmer_path;
        }
        static::setPluginPath($plugin_path);
    }

    static public function create($ps_stemmer) {
        if (in_array($ps_stemmer, static::getPluginNames())) {
            static::loadPluginFiles();
            return new $ps_stemmer();
        }
    }
}
