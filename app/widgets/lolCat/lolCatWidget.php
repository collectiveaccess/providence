<?php
/* ----------------------------------------------------------------------
 * lolCatWidget.php : 
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
 	require_once(__CA_LIB_DIR__.'/BaseWidget.php');
 	require_once(__CA_LIB_DIR__.'/IWidget.php');
 	require_once(__CA_LIB_DIR__.'/Zend/Feed.php');
 	require_once(__CA_LIB_DIR__.'/Zend/Feed/Rss.php');
 
	class lolCatWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('lol Katz');
			$this->description = _t('I Can Has Cheezburger?');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/lolCatWidget.conf');
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

			$vs_feed_url = 'http://feeds.feedburner.com/ICanHasCheezburger';
			$vs_feed_url_md5 = md5($vs_feed_url);

			if(ExternalCache::contains($vs_feed_url_md5, 'Meow')) {
				$feed = ExternalCache::fetch($vs_feed_url_md5, 'Meow');
			} else {
				try {
					$feed = Zend_Feed::import($vs_feed_url);
				} catch (Exception $e) {
					return null;
				}

				ExternalCache::save($vs_feed_url_md5, $feed, 'Meow');
				$feed->__wakeup();
			}

			$this->opo_view->setVar('title', $feed->title());
			
			$vn_i = (int)rand(0, ($feed->count() - 1));		// pick a random cat
			
			$vn_c = 0;
			foreach($feed as $item){
				if ($vn_c < $vn_i) {
					$vn_c++; continue;		// skip until we get to our random cat
				}
				$this->opo_view->setVar('item_title', $item->title());
				$this->opo_view->setVar('item_description', $item->description());
				$this->opo_view->setVar('item_link', $item->link());
				
				// Find the image URL in the encoded HTML content...
				if (preg_match("!(https://i.chzbgr.com/full/[^\"']+)!i", $item->encoded(), $va_matches)) {
					$vs_url = $va_matches[1];
					
					$vn_width = 430;							// force width of image to 430 pixels
					//$vn_height = floor($vn_width / 1.57);		// assume aspect ratio is 1.57 (typical). This results is an occasional squished cat but who's counting?
					
					$this->opo_view->setVar('item_image', "<img src='{$vs_url}' width='{$vn_width}'/>");
					break;
				}
				
				// if we fall through to here it means we couldn't find an image link in the encoded HTML content
				// so we just skip to the next one and see if there's a cat in there.
				$vn_c++;
			}
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
	
	BaseWidget::$s_widget_settings['lolCatWidget'] = array(		
		
	);
