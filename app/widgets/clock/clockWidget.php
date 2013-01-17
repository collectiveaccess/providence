<?php
/* ----------------------------------------------------------------------
 * clockWidget.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/BaseWidget.php');
 	require_once(__CA_LIB_DIR__.'/ca/IWidget.php');
 
	class clockWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Clock');
			$this->description = _t('Tells time in digital or faux analog formats');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/clockWidget.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function renderWidget($ps_widget_id, &$pa_settings) {
			parent::renderWidget($ps_widget_id, $pa_settings);
			
			$this->opo_view->setVar('request', $this->getRequest());
			
			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}
	
	BaseWidget::$s_widget_settings['clockWidget'] = array(		
		'display_mode' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 'standard',
			'options' => array(
				_t('Analog') => 'retro',
				_t('Digital') => 'standard'
			),
			'label' => _t('Display mode'),
			'description' => _t('Determines the clock\'s appearance. "Analog" will display the clock using a simulation of old-fashioned mechanical numbers. "Digital" will simply print the time numerically.')
		),
		'display_format' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 'h:i a',
			'options' => array(
				_t('Yes') => 'h:i:s a',
				_t('No') => 'h:i a'
			),
			'label' => _t('Show seconds?'),
			'description' => _t('If enabled display of time will be to the second. Otherwise it will be to the minute.')
		)
	);
	
?>