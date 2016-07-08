<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Attributes/Values/UrlAttributeValue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 	define("__CA_ATTRIBUTE_VALUE_URL__", 5);
 	
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/IAttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/ca/Attributes/Values/AttributeValue.php');
 	require_once(__CA_LIB_DIR__.'/core/BaseModel.php');	// we use the BaseModel field type (FT_*) and display type (DT_*) constants
 
 	global $_ca_attribute_settings;
 	$_ca_attribute_settings['UrlAttributeValue'] = array(		// global
		'minChars' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 5, 'height' => 1,
			'default' => 0,
			'label' => _t('Minimum number of characters'),
			'description' => _t('The minimum number of characters to allow. Input shorter than required will be rejected.')
		),
		'maxChars' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 5, 'height' => 1,
			'default' => 65535,
			'label' => _t('Maximum number of characters'),
			'description' => _t('The maximum number of characters to allow. Input longer than required will be rejected.')
		),
		'regex' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 60, 'height' => 1,
			'default' => '',
			'label' => _t('Regular expression to validate input with'),
			'description' => _t('A Perl-format regular expression with which to validate the input. Input not matching the expression will be rejected. Do not include the leading and trailling delimiter characters (typically "/") in your expression. Leave blank if you don\'t want to use regular expression-based validation.')
		),
		'fieldWidth' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 40,
			'width' => 5, 'height' => 1,
			'label' => _t('Width of data entry field in user interface'),
			'description' => _t('Width, in characters, of the field when displayed in a user interface.')
		),
		'fieldHeight' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'default' => 1,
			'width' => 5, 'height' => 1,
			'label' => _t('Height of data entry field in user interface'),
			'description' => _t('Height, in characters, of the field when displayed in a user interface.')
		),
		'doesNotTakeLocale' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Does not use locale setting'),
			'description' => _t('Check this option if you don\'t want your urls to be locale-specific. (The default is to not be.)')
		),
		'requireValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Require value'),
			'description' => _t('Check this option if you want an error to be thrown if the URL is left blank.')
		),
		'canBeUsedInSort' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used for sorting'),
			'description' => _t('Check this option if this attribute value can be used for sorting of search results. (The default is to be.)')
		),
		'suggestExistingValues' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'width' => 1, 'height' => 1,
			'default' => 0,
			'label' => _t('Suggest existing values?'),
			'description' => _t('Check this option if you want this attribute to suggest previously saved values as text is entered. This option is only effective if the display height of the element is equal to 1.')
		),
		'suggestExistingValueSort' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => 'value',
			'width' => 20, 'height' => 1,
			'label' => _t('Sort suggested values by?'),
			'description' => _t('If suggestion of existing values is enabled this option determines how returned values are sorted. Choose <i>value</i> to sort alphabetically. Choose <i>most recently added </i> to sort with most recently entered values first.'),
			'options' => array(
				_t('Value') => 'value',
				_t('Most recently added') => 'recent'
			)
		),
		'canBeUsedInSearchForm' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in search form'),
			'description' => _t('Check this option if this attribute value can be used in search forms. (The default is to be.)')
		),
		'canBeUsedInDisplay' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 1,
			'width' => 1, 'height' => 1,
			'label' => _t('Can be used in display'),
			'description' => _t('Check this option if this attribute value can be used for display in search results. (The default is to be.)')
		),
		'canMakePDF' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Allow PDF output?'),
			'description' => _t('Check this option if this metadata element can be output as a printable PDF. (The default is not to be.)')
		),
		'canMakePDFForValue' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_CHECKBOXES,
			'default' => 0,
			'width' => 1, 'height' => 1,
			'label' => _t('Allow PDF output for individual values?'),
			'description' => _t('Check this option if individual values for this metadata element can be output as a printable PDF. (The default is not to be.)')
		),
		'displayTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 4,
			'label' => _t('Display template'),
			'validForRootOnly' => 1,
			'description' => _t('Layout for value when used in a display (can include HTML). Element code tags prefixed with the ^ character can be used to represent the value in the template. For example: <i>^my_element_code</i>.')
		),
		'displayDelimiter' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '; ',
			'width' => 10, 'height' => 1,
			'label' => _t('Value delimiter'),
			'validForRootOnly' => 1,
			'description' => _t('Delimiter to use between multiple values when used in a display.')
		)
	);
 
	class UrlAttributeValue extends AttributeValue implements IAttributeValue {
 		# ------------------------------------------------------------------
 		private $ops_text_value;
 		# ------------------------------------------------------------------
 		public function __construct($pa_value_array=null) {
 			parent::__construct($pa_value_array);
 		}
 		# ------------------------------------------------------------------
 		public function loadTypeSpecificValueFromRow($pa_value_array) {
 			$this->ops_text_value = $pa_value_array['value_longtext1'];
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * @param array $pa_options Supported options are
 		 *		asHTML = if set, URL is returned as a simple HTML link with target set to _url_details (deprecated)
 		 *		returnAsLink = if set, URL is returned as a link formatted according to settings in the other returnAsLink* options described below
 		 *		returnAsLinkText = text to use a content of HTML link. If omitted the url itself is used as the link content.
 		 *		returnAsLinkAttributes = array of attributes to include in link <a> tag. Use this to set class, alt and any other link attributes.
 		 *		
 		 * @return string The url or link HTML
 		 */
		public function getDisplayValue($pa_options=null) {
			if (isset($pa_options['asHTML']) && $pa_options['asHTML']) {
				return caHTMLLink($this->ops_text_value, array('href' => $this->ops_text_value, 'target' => '_url_details'));
			} 
			
			$vs_return_as_link = 				(isset($pa_options['returnAsLink'])) ? (bool)$pa_options['returnAsLink'] : false;
			if ($vs_return_as_link) {
				$vs_return_as_link_text = 			(isset($pa_options['returnAsLinkText'])) ? (string)$pa_options['returnAsLinkText'] : '';
				$va_return_as_link_attributes = 	(isset($pa_options['returnAsLinkAttributes']) && is_array($pa_options['returnAsLinkAttributes'])) ? $pa_options['returnAsLinkAttributes'] : array();
			
				$va_return_as_link_attributes['href'] = $this->ops_text_value;
			
				if (!$vs_return_as_link_text) { $vs_return_as_link_text = $this->ops_text_value; }
				return caHTMLLink($vs_return_as_link_text, $va_return_as_link_attributes);
			}
			
			return $this->ops_text_value;
		}
 		# ------------------------------------------------------------------
 		public function parseValue($ps_value, $pa_element_info, $pa_options=null) {
 			$ps_value = trim($ps_value);
 			$va_settings = $this->getSettingValuesFromElementArray(
 				$pa_element_info, 
 				array('minChars', 'maxChars', 'regex', 'requireValue')
 			);
 			if (!$va_settings['requireValue'] && !$ps_value) {
 				return array(
					'value_longtext1' => ''
				);
 			}
 			
 			$vn_strlen = unicode_strlen($ps_value);
 			if ($vn_strlen < $va_settings['minChars']) {
 				// text is too short
 				$vs_err_msg = ($va_settings['minChars'] == 1) ? _t('%1 must be at least 1 character long', $pa_element_info['displayLabel']) : _t('%1 must be at least %2 characters long', $pa_element_info['displayLabel'], $va_settings['minChars']);
				$this->postError(1970, $vs_err_msg, 'UrlAttributeValue->parseValue()');
				return false;
 			}
 			if ($vn_strlen > $va_settings['maxChars']) {
 				// text is too short
 				$vs_err_msg = ($va_settings['maxChars'] == 1) ? _t('%1 must be no more than 1 character long', $pa_element_info['displayLabel']) : _t('%1 be no more than %2 characters long', $pa_element_info['displayLabel'], $va_settings['maxChars']);
				$this->postError(1970, $vs_err_msg, 'UrlAttributeValue->parseValue()');
				return false;
 			}
 			
 			if (!$va_settings['regex']) {
 				$va_settings['regex'] = "(http|ftp|https|rtmp|rtsp):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&;:/~\+#]*[\w\-\@?^=%&/~\+#])?";
 			}
 			if ($va_settings['regex'] && !preg_match("!".$va_settings['regex']."!", $ps_value)) {
 				// default to http if it's just a hostname + path
 				if (!preg_match("!^[A-Za-z]+:\/\/!", $ps_value)) {
 					$ps_value = "http://{$ps_value}";
 				} else {
					// regex failed
					$this->postError(1970, _t('%1 is not a valid url', $pa_element_info['displayLabel']), 'UrlAttributeValue->parseValue()');
					return false;
				}
 			}
 			
 			return array(
 				'value_longtext1' => $ps_value
 			);
 		}
 		# ------------------------------------------------------------------/**
 		/**
 		 * Return HTML form element for editing.
 		 *
 		 * @param array $pa_element_info An array of information about the metadata element being edited
 		 * @param array $pa_options array Options include:
 		 *			class = the CSS class to apply to all visible form elements [Default=urlBg]
 		 *			width = the width of the form element [Default=field width defined in metadata element definition]
 		 *			height = the height of the form element [Default=field height defined in metadata element definition]
 		 *			t_subject = an instance of the model to which the attribute belongs; required if suggestExistingValues lookups are enabled [Default is null]
 		 *			request = the RequestHTTP object for the current request; required if suggestExistingValues lookups are enabled [Default is null]
 		 *			suggestExistingValues = suggest values based on existing input for this element as user types [Default is false]		
 		 *
 		 * @return string
 		 */
 		public function htmlFormElement($pa_element_info, $pa_options=null) {
 			$va_settings = $this->getSettingValuesFromElementArray($pa_element_info, array('fieldWidth', 'fieldHeight', 'minChars', 'maxChars', 'suggestExistingValues'));
 			$vs_class = trim((isset($pa_options['class']) && $pa_options['class']) ? $pa_options['class'] : 'urlBg');
 			
 			$vs_element = caHTMLTextInput(
 				'{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}', 
 				array(
					'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
					'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : $va_settings['fieldHeight'],
					'value' => '{{'.$pa_element_info['element_id'].'}}',
					'maxlength' => $va_settings['maxChars'],
					'id' => '{fieldNamePrefix}'.$pa_element_info['element_id'].'_{n}',
					'class' => $vs_class
 				)
 			);
 			
 			$vs_element .= " <a href='#' style='display: none; vertical-align: top;' id='{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}' target='_url_details'>"._t("Open &rsaquo;")."</a>";
		
 			$vs_bundle_name = $vs_lookup_url = null;
 			if (isset($pa_options['t_subject']) && is_object($pa_options['t_subject'])) {
 				$vs_bundle_name = $pa_options['t_subject']->tableName().'.'.$pa_element_info['element_code'];
 				
 				if ($pa_options['request']) {
 					$vs_lookup_url	= caNavUrl($pa_options['request'], 'lookup', 'AttributeValue', 'Get', array('bundle' => $vs_bundle_name, 'max' => 500));
 				}
 			}
 			
 			if ($va_settings['suggestExistingValues'] && $vs_lookup_url && $vs_bundle_name) { 
 				$vs_element .= "<script type='text/javascript'>
 					jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_{n}').autocomplete( 
						{ source: '{$vs_lookup_url}', minLength: 3, delay: 800}
					);
 				</script>\n";
 			}
 			$vs_element .= "
				<script type='text/javascript'>
					if ('{{".$pa_element_info['element_id']."}}') {
							jQuery('#{fieldNamePrefix}".$pa_element_info['element_id']."_link{n}').css('display', 'inline').attr('href', '{{".$pa_element_info['element_id']."}}');
						}
				</script>
			";
 			
 			return $vs_element;
 		}
 		# ------------------------------------------------------------------
 		public function getAvailableSettings($pa_element_info=null) {
 			global $_ca_attribute_settings;
 			
 			return $_ca_attribute_settings['UrlAttributeValue'];
 		}
 		# ------------------------------------------------------------------
		/**
		 * Returns name of field in ca_attribute_values to use for sort operations
		 * 
		 * @return string Name of sort field
		 */
		public function sortField() {
			return 'value_longtext1';
		}
 		# ------------------------------------------------------------------
		/**
		 * Returns constant for url attribute value
		 * 
		 * @return int Attribute value type code
		 */
		public function getType() {
			return __CA_ATTRIBUTE_VALUE_URL__;
		}
		# ------------------------------------------------------------------
		public function checkIntegrity() {
			if(!$this->ops_text_value) { return false; }
			$ps_url = $this->ops_text_value;

			if(!((bool)ini_get('allow_url_fopen'))) {
				throw new Exception("It looks like allow_url_fopen is set to false. This means CollectiveAccess is not able to download the given file. Please contact your system administrator.");
			}

			if(!isURL($ps_url)) {
				throw new Exception("This does not look like a URL");
			}

			$o_context = stream_context_create( array(
				'http' => array(
					'timeout' => 3
				)
			));

			$r_fp = @fopen($ps_url, 'r', false, $o_context);

			if(!$r_fp) {
				throw new Exception(_t('Could not open remote URL [%1]', $ps_url));
			}

			if(($vs_content = fgets($r_fp, 64)) === false) {
				throw new Exception(_t('Could not read data from remote URL [%1]', $ps_url));
			}

			fclose($r_fp);
		}
 		# ------------------------------------------------------------------
		/**
		 * @param array $pa_options
		 * 		request = Request object used to build links
		 * 		printStatusViaCLIUtils = defaults to true
		 * 		notifyUsers =
		 * 		notifyGroups =
		 * @throws Exception
		 */
		public static function checkIntegrityForAllElements(array $pa_options = []) {
			$pb_print_status = caGetOption('printStatusViaCLIUtils', $pa_options, true);
			$ps_notify_users = caGetOption('notifyUsers', $pa_options, false);
			$ps_notify_groups = caGetOption('notifyGroups', $pa_options, false);
			$po_request = caGetOption('request', $pa_options, null);

			$o_db = new Db();

			$qr_elements = $o_db->query('SELECT element_id FROM ca_metadata_elements WHERE datatype=? ORDER BY element_id', __CA_ATTRIBUTE_VALUE_URL__);

			$va_notifications = [];
			while($qr_elements->nextRow()) {
				$vs_element_code = ca_metadata_elements::getElementCodeForId($qr_elements->get('element_id'));

				if($pb_print_status) {
					CLIUtils::addMessage(_t("Checking values for element code [%1]", $vs_element_code));
				}

				$qr_vals = $o_db->query('SELECT * FROM ca_attribute_values WHERE element_id=? ORDER BY value_id', $qr_elements->get('element_id'));

				if($pb_print_status) { print CLIProgressBar::start($qr_vals->numRows(), _t("Processing element [%1]", $vs_element_code)); }

				while($qr_vals->nextRow()) {
					$o_val = new UrlAttributeValue($qr_vals->getRow());

					if($pb_print_status) { print CLIProgressBar::next(); }

					try {
						$o_val->checkIntegrity();
					} catch(Exception $e) {
						$qr_attr = $o_db->query('SELECT * FROM ca_attributes where attribute_id=?', $qr_vals->get('attribute_id'));
						if(!$qr_attr->nextRow()) { throw new Exception('Something went horribly wrong.'); } // each value should have an attribute

						$vs_msg = _t("There was an error while veryfing URL for %1 with ID %2: %3",
							caGetTableDisplayName($qr_attr->get('table_num'), false),
							$qr_attr->get('row_id'), $e->getMessage()
						);

						if($po_request instanceof RequestHTTP) {
							$vs_msg .= "\n<br/><br/>" . caEditorLink($po_request, _t("Open record"), '', $qr_attr->get('table_num'), $qr_attr->get('row_id'), null, null, ['action' => 'Edit']);
						}

						$va_notifications[] = $vs_msg;

						if($pb_print_status) {
							CLIUtils::addError($vs_msg);
						}
					}

					if($pb_print_status) { print CLIProgressBar::finish(); }
				}

				// notify users
				if((strlen($ps_notify_users) > 0) && sizeof($va_notifications)) {
					$t_user = new ca_users();
					$pa_users = preg_split('/[,:]/', $ps_notify_users);

					foreach($pa_users as $vs_user_name) {
						if(!$t_user->load(['user_name' => $vs_user_name])) {
							continue;
						}

						foreach($va_notifications as $vs_notification) {
							$t_user->addNotification(__CA_NOTIFICATION_TYPE_URL_REFERENCE_CHECK__, $vs_notification);
						}
					}
				}

				// notify user groups (each user individually)
				if((strlen($ps_notify_groups) > 0) && sizeof($va_notifications)) {
					$t_group = new ca_user_groups();
					$t_user = new ca_users();
					$pa_groups = preg_split('/[,:]/', $ps_notify_groups);

					foreach($pa_groups as $vs_group_code) {
						if(!$t_group->load(['code' => $vs_group_code])) {
							continue;
						}

						foreach($t_group->getGroupUsers() as $va_user) {
							if(!$t_user->load(['user_name' => $va_user['user_name']])) {
								continue;
							}

							foreach($va_notifications as $vs_notification) {
								$t_user->addNotification(__CA_NOTIFICATION_TYPE_URL_REFERENCE_CHECK__, $vs_notification);
							}
						}
					}
				}
			}
		}
		# ------------------------------------------------------------------
	}
