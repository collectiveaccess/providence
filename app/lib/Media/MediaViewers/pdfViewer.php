<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/MediaViewers/pdfViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2026 Whirl-i-Gig
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
class pdfViewer extends BaseMediaViewer implements IMediaViewer {
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getViewerHTML($request, $identifier, $data=null, $options=null) {
		if ($o_view = BaseMediaViewer::getView($request)) {
			$o_view->setVar('identifier', $identifier);
			
			// Pass subject key when getting viewer data
			if ($t_subject = caGetOption('t_subject', $data, null)) {  $data['t_subject']->getPrimaryKey(); }
			
			$o_view->setVar('viewer', 'pdfViewer');
			$o_view->setVar('width', caGetOption('width', $data['display'], null));
			$o_view->setVar('height', caGetOption('height', $data['display'], null));
			
			switch($scroll_mode = caGetOption('scroll_mode', $data['display'], "DEFAULT", ['forceUppercase' => true])) {
				case 'VERTICAL':
				default:
					$scroll_mode_num = 'vertical';
					break;
				case 'HORIZONTAL':
					$scroll_mode_num = 'horizontal';
					break;
			}
			
			$o_view->setVar('scroll_mode', $scroll_mode_num);

			$t_instance = isset($data['t_instance']) ? $data['t_instance'] : null;
			
			$o_context = $t_subject ? ResultContext::getResultContextForLastFind($request, $t_subject->tableName()) : null;
			$o_view->setVar('search', trim(preg_replace("![\(\)\*\"]+!", "", $o_context ? $o_context->getSearchExpression() : null)));
		}
		
		return BaseMediaViewer::prepareViewerHTML($request, $o_view, $data, $options);
	}
	# -------------------------------------------------------
}
