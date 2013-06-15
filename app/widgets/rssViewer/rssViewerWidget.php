<?php
/* ----------------------------------------------------------------------
 * rssViewerWidget.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
 * Ajout Gautier MICHELIN
 */
 	require_once(__CA_LIB_DIR__.'/ca/BaseWidget.php');
 	require_once(__CA_LIB_DIR__.'/ca/IWidget.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Cache.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Feed.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Feed/Rss.php');
 
	class rssViewerWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('RSS Viewer');
			$this->description = _t('Display the content of an RSS feed');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/rssViewerWidget.conf');
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

			if ($pa_settings['feed_url']) {
				$vs_feed_url = $pa_settings['feed_url'];
			} else {
				$vs_feed_url = "http://icom.museum/rss.xml";
			}
						
			$vs_feed_url_md5 = md5($vs_feed_url);

			$va_frontend_options = array(
				'lifetime' => 3 * 3600, 			/* cache lives 3 hours */
				'logging' => false,					/* do not use Zend_Log to log what happens */
				'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
				'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
				'automatic_serialization' => true	/* we store objects, so we have to enable that */
			);
			
			$va_backend_options = array(
				'cache_dir' =>  __CA_APP_DIR__.'/tmp',		/* where to store cache data? */
				'file_locking' => true,				/* cache corruption avoidance */
				'read_control' => false,			/* no read control */
				'file_name_prefix' => 'ca_browse',	/* prefix of cache files */
				'cache_file_perm' => 0700			/* permissions of cache files */
			);


			try {
				$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);
			} catch (exception $e) {
				// noop
			}
			
			if ($vo_cache) {
				if (!($feed = $vo_cache->load($vs_feed_url_md5))) {
					$vb_feed_error = false;
					try {
						$feed = Zend_Feed::import($vs_feed_url);
					} catch (Exception $e) {
						$vb_feed_error = true;
					}
					
					if ($vb_feed_error) {
						return null;
					}
					$vo_cache->save($feed, $vs_feed_url_md5, array('ca_widget_rssView'));
					$feed->__wakeup();
				}
			} else {
				// If no caching then just suck it over the network every time. Yay!
				$feed = Zend_Feed::import($vs_feed_url);
			}
			

			//A little style definition
			$rssViewContent  .= "" .
						"<STYLE type=\"text/css\">" .
						".rssViewerWidgetContent, .rssViewerWidgetContent P, .rssViewerWidgetContent DIV, .rssViewerWidgetContent H1," .
						".rssViewerWidgetContent H2" .
						" {margin:0px;padding:0px;margin-right:10px;padding-right:20px;}" .
						".rssViewerWidgetContent H3 " .
						" {margin:0px;padding:0px;margin-right:10px;margin-top:10px;padding-right:20px;}" .
						"</STYLE>".
						"<span class=\"rssViewerWidgetContent\">";

			//Initializing count to the limit : number_of_feeds
			$vn_c=0;
			//Initializing description variable to store description with or without images
			$description="";
						
			// Reading RSS feeds title, URL and description
			$this->opo_view->setVar('title', $feed->title());
			$this->opo_view->setVar('description', $feed->description());
			$this->opo_view->setVar('link', $feed->link());
			
			$rssViewContent .= "<h1><a href=\"".$feed->link()."\" target=\"_blank\">"
				.$feed->title()."</a></h1>\n"
				.$feed->description();
			
			// Main loop : reading the items
			foreach($feed as $item){
				$vn_c++;
				if ($vn_c <= $pa_settings['number_of_feeds'])	{
					// retrieve content
					$this->opo_view->setVar('item_title', $item->title());
					$this->opo_view->setVar('item_description', $item->description());
					$this->opo_view->setVar('item_link', $item->link());
					
					$description = $item->description();
					// when filtering images is on, remove IMG tags from description
					// when filtering HTML is on, remove all HTML tags
					if ($pa_settings['filter_html'] == 2 ) {
							$description = strip_tags($description);
						} elseif ($pa_settings['filter_html'] == 1 ) { 
							$description = preg_replace("/<img[^>]+\>/i", " ", $description);  
						}
					
					// HTML generation of the content to display, 1 span surrounding each feed
					$rssViewContent .= "" .
							"<h3><a href=\"".$item->link()."\" target=\"blank\">".$item->title()."</a></h3>\n" .
							"".$description."\n";					
				}
				$rssViewContent .= "</span>";	
			}
			$this->opo_view->setVar('item_content', $rssViewContent);
					
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
	
	BaseWidget::$s_widget_settings['rssViewerWidget'] = array(		
		'feed_url' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 55, 'height' => 1,
			'takesLocale' => false,
			'default' => 'http://www.collectiveaccess.org/forum/rss.php',
			'label' => _t('Feed URL'),
			'description' => _t('Feed URL to display in the widget.')
		),
		'number_of_feeds' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 3,
			'options' => array(
				_t('3') => 3,
				_t('5') => 5,
				_t('10') => 10				
			),
			'label' => _t('Number of feed items to display'),
			'description' => _t('Limits the number of the feed items displayed at one time.')
		),
		'filter_html' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'default' => 0,
			'options' => array(
				_t('No filter') => 0,
				_t('Remove images') => 1,
				_t('Remove HTML') => 2
			),
			'label' => _t('Filter to remove images or HTML?'),
			'description' => _t('When remove images is chosen, images (IMG) will be removed from the display. Useful for ads and big images feeds.<br/>'.
				'When remove HTML is chosen, all of the feed will be cleaned of any HTML tags, leaving text only.')
		)		
	);
	
?>