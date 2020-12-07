<?php
/* ----------------------------------------------------------------------
 * app/lib/Plugins/IPAddress.php : 
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

class WLPlugBanHammerIPAddress Extends BaseBanHammerPlugIn
{
    # ------------------------------------------------------
    /**
     *
     */
    static public $priority = 10;

    # ------------------------------------------------------

    /**
     *
     */
    public static function evaluate($request, $options = null)
    {
        self::init($request, $options);
        $config = self::$config->get('plugins.IPAddress');
        $banned_ip_addresses = caGetOption('banned_ip_addresses', $config, []);

        $request_ip = RequestHTTP::ip();
        $request_ip_long = ip2long($request_ip);

        foreach ($banned_ip_addresses as $ip) {
            $ip_s = ip2long(str_replace("*", "0", $ip));
            $ip_e = ip2long(str_replace("*", "255", $ip));
            if (($request_ip_long >= $ip_s) && ($request_ip_long <= $ip_e)) {
                return 1.0;
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
        return 60 * 60 * 24;    // ban for 1 day
    }
    # ------------------------------------------------------
}
