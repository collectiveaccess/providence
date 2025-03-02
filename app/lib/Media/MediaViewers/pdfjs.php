<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaViewers/pdfjs.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2021 Whirl-i-Gig
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

/**
 *
 */
 
class pdfjs extends BaseMediaViewer implements IMediaViewer {
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getViewerHTML($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
		if ($o_view = BaseMediaViewer::getView($po_request)) {
			$o_view->setVar('identifier', $ps_identifier);
			
			$va_params = ['identifier' => $ps_identifier, 'context' => caGetOption('context', $pa_options, $po_request->getAction())];
			
			// Pass subject key when getting viewer data
			if ($t_subject = caGetOption('t_subject', $pa_data, null)) { $va_params[$pa_data['t_subject']->primaryKey()] = $pa_data['t_subject']->getPrimaryKey(); }
			
			$o_view->setVar('viewer', 'pdfjs');
			$o_view->setVar('width', caGetOption('width', $pa_data['display'], null));
			$o_view->setVar('height', caGetOption('height', $pa_data['display'], null));
			
			switch($scroll_mode = caGetOption('scroll_mode', $pa_data['display'], "DEFAULT", ['forceUppercase' => true])) {
				case 'VERTICAL':
					$scroll_mode_num = 0;
					break;
				case 'HORIZONTAL':
					$scroll_mode_num = 1;
					break;
				case 'WRAPPED':
					$scroll_mode_num = 2;
					break;
				default:
					$scroll_mode_num = -1;
					break;
			}
			
			$o_view->setVar('scroll_mode', $scroll_mode_num);

			$t_instance = isset($pa_data['t_instance']) ? $pa_data['t_instance'] : null;
			
			$o_context = $t_subject ? ResultContext::getResultContextForLastFind($po_request, $t_subject->tableName()) : null;
			$o_view->setVar('search', trim(preg_replace("![\(\)\*\"]+!", "", $o_context ? $o_context->getSearchExpression() : null)));
		}
		
		return BaseMediaViewer::prepareViewerHTML($po_request, $o_view, $pa_data, $pa_options);
	}
	# -------------------------------------------------------
}
