<?php
/** ---------------------------------------------------------------------
 * app/helpers/externalMediaHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022-2025 Whirl-i-Gig
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


# ---------------------------------------
/**
 * 
 *
 * @param string $url
 * @param array $options Options include:
 *		title = 
 *		width = 
 *		height = 
 * 
 * @return string Embed HTML code or null if URL is invalid or unsupported
 */
function caGetExternalMediaEmbedCode(string $url, ?array $options=null) {
	$media_url = new CA\MediaUrl();
	return $media_url->embedTag($url, $options);
}
# ---------------------------------------
