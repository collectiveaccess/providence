<?php
/* ----------------------------------------------------------------------
 * app/lib/BanHammer.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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

require_once(__CA_MODELS_DIR__ . "/ca_ip_bans.php");

class BanHammer
{
    # ------------------------------------------------------
    /**
     *
     */
    static public $plugin_names = null;

    /**
     *
     */
    static public $config = null;

    # ------------------------------------------------------

    /**
     *
     */
    public static function init()
    {
        if (!self::$config) {
            self::$config = Configuration::load(__CA_CONF_DIR__ . '/ban_hammer.conf');
        }
        return true;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public static function getPluginNames()
    {
        if (is_array(self::$plugin_names)) {
            return self::$plugin_names;
        }

        self::$plugin_names = [];
        $dir = opendir($p = __CA_LIB_DIR__ . '/Plugins/BanHammer');
        if (!$dir) {
            throw new ApplicationException(_t('Cannot open BanHammer plugin directory %1', p));
        }

        while (($plugin = readdir($dir)) !== false) {
            if ($plugin == "BaseBanHammerPlugin.php") {
                continue;
            }
            if (preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $plugin, $m)) {
                $n = $m[1];
                require("{$p}/{$plugin}");
                $classname = "WLPlugBanHammer{$n}";
                self::$plugin_names[$classname::$priority][] = $n;
            }
        }

        ksort(self::$plugin_names);
        self::$plugin_names = array_reduce(
            self::$plugin_names,
            function ($c, $v) {
                return array_merge($c, array_values($v));
            },
            []
        );

        return self::$plugin_names;
    }
    # ------------------------------------------------------

    /**
     *
     * @return bool Refuse connection?
     */
    public static function verdict($request, $options = null)
    {
        self::init();
        if (!self::$config->get('enabled')) {
            return true;
        }
        if (ca_ip_bans::isBanned($request)) {
            return false;
        }


        $module = $request->getModulePath();
        $controller = $request->getController();
        if ((strtolower($module) === 'system') && (strtolower($controller) === 'error')) {
            return true;
        }
        if (($module === '') && (strtolower($controller) === 'media')) {
            return true;
        }
        if ($request->isAjax()) {
            return true;
        }

        $plugin_names = self::getPluginNames();

        $mode = strtolower(self::$config->get('evaluation_mode'));
        if (!in_array($mode, ['absolute', 'average'])) {
            $mode = 'absolute';
        }
        $threshold = (float)(self::$config->get('threshold'));
        if (($threshold < 0) || ($threshold > 1)) {
            $threshold = 1.0;
        }

        $non_zero_acc = 0;
        $non_zero_count = 0;
        $max_ttl = null;
        $should_ban_ip = false;
        $non_zero_plugins = [];
        foreach ($plugin_names as $p) {
            $classname = "WLPlugBanHammer{$p}";
            $prob = $classname::evaluate($request, $options);

            if ($prob >= $threshold) {
                if ($classname::shouldBanIP()) {
                    ca_ip_bans::ban($request, $classname::banTTL(), $p);
                }
                return false;
            }

            if ($prob > 0) {
                $non_zero_acc += $prob;
                $non_zero_count++;
                if (($ttl = $classname::banTTL()) && ($ttl > $max_ttl)) {
                    $max_ttl = $ttl;
                }
                if ($classname::shouldBanIP()) {
                    $should_ban_ip = true;
                }
                $non_zero_plugins[] = $p;
            }
        }

        if (($mode == 'average') && (((float)$non_zero_acc / (float)$non_zero_count) >= $threshold)) {
            if ($should_ban_ip) {
                ca_ip_bans::ban($request, $max_ttl, join(", ", $non_zero_plugins));
            }
            return false;
        }

        return true;
    }
    # ------------------------------------------------------
}
