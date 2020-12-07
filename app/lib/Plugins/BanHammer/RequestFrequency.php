<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/RequestFrequency.php : 
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

require_once(__CA_LIB_DIR__ . "/Plugins/BanHammer/BaseBanHammerPlugin.php");

class WLPlugBanHammerRequestFrequency Extends BaseBanHammerPlugIn
{
    # ------------------------------------------------------
    /**
     *
     */
    static public $priority = 100;

    # ------------------------------------------------------

    /**
     *
     */
    public static function evaluate($request, $options = null)
    {
        self::init($request, $options);
        $config = self::$config->get('plugins.RequestFrequency');
        if ((($frequency_threshold = (float)$config['frequency_threshold']) < 0.1) || ($frequency_threshold > 999)) {
            $frequency_threshold = 10;
        }

        if ((($ban_probability = (float)$config['ban_probability']) < 0) || ($ban_probability > 1.0)) {
            $ban_probability = 1.0;
        }


        if (!($ip = RequestHTTP::ip())) {
            return 0;
        }
        $request_count = ExternalCache::fetch($ip, 'BanHammer_RequestCounts');
        if (!is_array($request_count)) {
            $request_count = ['s' => time(), 'c' => 1];
        } elseif ((time() - $request_count['s']) > 60) {
            $request_count = ['s' => time(), 'c' => 1];
        } else {
            $request_count['c']++;
        }
        ExternalCache::save($ip, $request_count, 'BanHammer_RequestCounts');

        if (($interval = (time() - $request_count['s'])) > 0) {
            $freq = (float)$request_count['c'] / (float)$interval;
            if ($freq > $frequency_threshold) {
                return $ban_probability;
            }
        }

        return 0;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public static function shouldBanIP()
    {
        return true;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public static function banTTL()
    {
        return null;    // forever
    }
    # ------------------------------------------------------
}
