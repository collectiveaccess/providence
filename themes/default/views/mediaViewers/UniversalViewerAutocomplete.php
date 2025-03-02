<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/UniversalViewerAutocomplete.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
    $va_matches = $this->getVar('matches');
    
    $vs_url = caNavUrl($this->request, '*', '*', '*', ['q' => $this->request->getParameter('q', pString)]);
    if (is_array($va_matches)) {
        $va_matches = array_map(function($v) use ($vs_url) {
            return [
                'match' => $v,
                'search' => $vs_url
            ];
        }, $va_matches);
    }
 
    $va_manifest = [
      "@context" => "http://iiif.io/api/search/0/context.json",
      "@id" => $vs_url,
      "@type" => "search:TermList",
      "terms" => $va_matches
    ];
    
    print json_encode($va_manifest);