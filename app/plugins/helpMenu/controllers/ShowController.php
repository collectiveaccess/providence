<?php
/* ----------------------------------------------------------------------
 * app/plugins/helpMenu/controllers/ShowController.php :
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
require_once( __CA_MODELS_DIR__ . '/ca_site_pages.php' );


class ShowController extends ActionController {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __call( $ps_method, $pa_path ) {
		$this->view->setVar( 'response', $this->response );

		$page = new ca_site_pages( (int) $this->request->getAction() );
		if ( ! $page->isLoaded() ) {
			throw new ApplicationException( _t( 'Cannot load page' ) );
		}
		if ( $page->get( 'path' ) !== 'PROVIDENCE_HELP_MENU' ) {
			throw new ApplicationException( _t( 'Is not help menu page' ) );
		}
		if ( $vs_content = $page->render( $this, [ 'incrementViewCount' => true ] ) ) {
			$this->response->addContent( $vs_content );

			return;
		}
	}
	# -------------------------------------------------------
}
