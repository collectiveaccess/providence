<?php
/* ----------------------------------------------------------------------
 * UserRecommendationsPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 */
 
define("__CA_USERRECOMMENDATIONS_DIR__",__CA_APP_DIR__."/plugins/UserRecommendations");

require_once(__CA_USERRECOMMENDATIONS_DIR__."/models/ca_item_recommendations.php");
require_once(__CA_MODELS_DIR__.'/ca_item_comments.php');

class UserRecommendationsPlugin extends BaseApplicationPlugin {
	# -------------------------------------------------------
	protected $description = 'Plugin to create User Recommendations (associating items and tables) for CollectiveAccess';
	# -------------------------------------------------------
	private $opo_config;
	private $ops_plugin_path;
	# -------------------------------------------------------
	public function __construct($ps_plugin_path) {
		$this->ops_plugin_path = $ps_plugin_path;
		$this->description = _t('Provide a mechanismfor for User Generated Recommendations within Providence');
		parent::__construct();
		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/UserRecommendations.conf');
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true - the statisticsViewerPlugin always initializes ok... (part to complete)
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
	 * Insert activity menu
	 */
	public function hookRenderMenuBar($pa_menu_bar) {
		if ($o_req = $this->getRequest()) {
            if($this->has_comment_ui_plugin()) {
                $pa_menu_bar['UserContent_menu']['navigation']['Recommendations'] = array(
				    'is_enabled' => 1,
				    'displayName' => _t('Recommendations'),
				    'default' => array(
					    'module' => 'UserRecommendations',
					    'controller' => 'Recommendation',
					    'action' => 'List'
                    )
                );
            }
		}
				
		return $pa_menu_bar;
    }

    public function has_comment_ui_plugin() {
        $exists_and_enabled = false;

        if(file_exists(__CA_APP_DIR__."/plugins/CommentUI/conf/CommentUI.conf")) {
            $exists_and_enabled = ((bool)Configuration::load(__CA_APP_DIR__.'/plugins/CommentUI/conf/CommentUI.conf')->get('enabled'));
        }

        return $exists_and_enabled;
    }

    public function hookRenderSideNav($va_nav_info) {
        $module = $this->getRequest()->getModulePath();
        $controller = $this->getRequest()->getController();
        if($module == 'UserRecommendations' && $controller == 'Recommendation') {
            $va_nav_info['Recommendations'] = array(
                'is_enabled' => 1,
                'displayName' => _t('Recommendations'),
                'default' => array(
                    'module' => 'UserRecommendations',
                    'controller' => 'Recommendation',
                    'action' => 'List'
                ),
                'navigation' => array(
                    'moderate' => array(
                        'displayName' => _t('Moderate'),
                        'default' => array(
                            'module' => 'UserRecommendations',
                            'controller' => 'Recommendation',
                            'action' => 'List'
                        ),
                        'is_enabled' => 1,
                        'requires' => array()
                    ),
                    'search' => array(
                        'displayName' => _t('Search'),
                        'default' => array(
                            'module' => 'UserRecommendations',
                            'controller' => 'Recommendation',
                            'action' => 'Index'
                        ),
                        'useActionInPath' => 1,
                        'is_enabled' => 1,
                        'requires' => array()
                    )
                )
            );
        }
        else {
            if($va_nav_info == null) {
                return true;
            }
        }

        return $va_nav_info;
    }

    public function hookRenderWidgets($va_widgets_config) {
        $va_widgets_config['recommendationInfo'] = array(
            "domain" => array(
                'module' => 'UserRecommendations',
                'controller' => 'Recommendation'
            ),
            "handler" => array(
                'module' => 'UserRecommendations',
                'controller' => 'Recommendation',
                'action' => 'Info',
                'isplugin' => 1
            ),
            "requires" => array(),
            "parameters" => array()
        );
        return $va_widgets_config;
    }

    /**
	* Use the hookBeforeBundleUpdate hook to intercept update requests to parse comments.
    */
    public function hookBeforeBundleUpdate($id, $table_num, $table_name, $instance) {
        // Parse the raw request body (couldn't find any other way to get the desired information that was passed)
        $info = json_decode($this->getRequest()->getRawPostData());

        //$this->addRecommendation('A fourth test from hookBeforeBundleUpdate', 3, 2);

        if($info->type == 'recommendation') {
            // Check if the name is set
		    if(isset($info->name)) {
                // Check if the email is set (the assumption being the email would never be set if the name isn't which makes sense there should never really be an annoynamous email)
			    if(isset($info->email)) {
				    $this->addRecommendation($info->recommendation, $info->object, $info->collection, $info->name, $info->email);
			    }
			    else {
				    $this->addRecommendation($info->recommendation, $info->object, $info->collection, $info->name);
			    }
		    }
		    else {
			    $this->addRecommendation($info->recommendation, $info->object, $info->collection);
            }
        }

        return true;
    }
    
    public function addRecommendation($recommendation, $object_idno, $collection, $name = null, $email = null) {
        global $g_ui_locale_id;

        // For right now we only deal with collections but could be expanded later
        $assoc_table_num = 13;

        // Create the comment and fill out the appropraite values
        $t_recommendation = new ca_item_recommendations();
		$t_recommendation->setMode(ACCESS_WRITE);
		$t_recommendation->set('table_num', 57);
        $t_recommendation->set('row_id', $object_idno);
        $t_recommendation->set('assoc_table_num', $assoc_table_num);
		$t_recommendation->set('user_id', null);
		$t_recommendation->set('locale_id', $g_ui_locale_id);
		$t_recommendation->set('recommendation', $recommendation);
		$t_recommendation->set('email', $email);
		$t_recommendation->set('name', $name);
        $t_recommendation->set('access', 0);
        
        if(is_numeric($collection)) {
            $t_recommendation->set('assoc_row_id', $collection);
            $t_recommendation->set('type', 0);
        }
        else {
            $t_recommendation->set('new_assoc_name', $collection);
            $t_recommendation->set('type', 1);
        }

        $t_recommendation->insert();
    }
    
    public function hookNewModel() {
        return array(
            'table_name' => 'ca_item_recommendations',
            'table_num' => 238,
            'model_path' => __CA_USERRECOMMENDATIONS_DIR__."/models/ca_item_recommendations.php"
        );
    }

	# -------------------------------------------------------
	/**
	 * Add plugin user actions
	 */
	static public function getRoleActionList() {
		return array();
	}
}
?>
