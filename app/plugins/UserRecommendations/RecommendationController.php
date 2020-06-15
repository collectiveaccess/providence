<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/CommentsController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 */

define("__CA_USERRECOMMENDATIONS_DIR__",__CA_APP_DIR__."/plugins/UserRecommendations");

require_once(__CA_LIB_DIR__."/ca/BaseSearchController.php");
require_once(__CA_MODELS_DIR__."/ca_objects_x_collections.php");
require_once(__CA_USERRECOMMENDATIONS_DIR__."/models/ca_item_recommendations.php");
require_once(__CA_USERRECOMMENDATIONS_DIR__."/Search/ItemRecommendationSearch.php");

class RecommendationController extends BaseSearchController {
	# -------------------------------------------------------
	/**
	 * Name of subject table (ex. for an object search this is 'ca_objects')
	 */
	protected $ops_tablename = 'ca_item_recommendations';
	
	/** 
	 * Number of items per search results page
	 */
	protected $opa_items_per_page = array(10, 20, 30, 40, 50);
		 
	/**
	 * List of search-result views supported for this find
	 * Is associative array: values are view labels, keys are view specifier to be incorporated into view name
	 */ 
	protected $opa_views;
		
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		//$this->opo_datamodel->addModelToGraph('ca_item_recommendations', 238);
		$this->opa_views = array(
			'list' => _t('list')
		);
		
		AssetLoadManager::register('tableList');
	}
	# -------------------------------------------------------
	/**
	 * Search handler (returns search form and results, if any)
	 * Most logic is contained in the BaseSearchController->Index() method; all you usually
	 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
	 * (eg. CollectionSearch for collections, EntitySearch for entities) and pass it to BaseSearchController->Index() 
	 */ 
	public function Index($pa_options=null) {
		$pa_options['search'] = new ItemRecommendationSearch();
        return parent::Index($pa_options);
        //$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
        //$this->view->setVar('t_model', $t_model);
        //$this->render('recommendation_test_html.php');
	}
	# -------------------------------------------------------
    /**
     * List all the recommendations
     * 
     * Views Used: [User Recommendations Plugin]/views/recommendation_list_html.php
     */
    public function List() {
        $t_recommendations = new ca_item_recommendations();
		$this->view->setVar('t_model', $t_recommendations);
		$this->view->setVar('comments_list', $t_recommendations->getRecommendationList());
		if(sizeof($t_recommendations->getRecommendationList()) == 0){
			$this->notification->addNotification(_t("There are no unmoderated comments"), __NOTIFICATION_TYPE_INFO__);
		}
		$this->render('recommendation_list_html.php');
	}
    # -------------------------------------------------------
    /**
     * Approves the recommendation
     * 
     * Association Process:
     *      1. Create record in collection's object table (ca_objects_x_collections)
     * 
     * Creation Process:
     *      1. Create record in the collection table (ca_collections)
     *      2. Create the preferred label record for the newly created collection (ca_colletion_labels)
     *      3. Create record in the collection's object table associating the item with the newly created table (ca_objects_x_collections)
     */
	public function Approve() {
		// Get the values submitted in the form
		$recommendation_id = $this->request->getParameter('recommendation_id', pString);
        $recommendation_type = $this->request->getParameter('type', pString);
        $recommendation_access = $this->request->getParameter('recommendation_access', pString);
        
        // Get an instance of the model
		$t_recommendation = new ca_item_recommendations($recommendation_id);
        
        // Make sure we're working with a valid record
		if (!$t_recommendation->getPrimaryKey()) {
        	$va_error = _t("The recommendation does not exist");	
        }

        if($recommendation_type == 'Association') {
            // Technically we could probably generalize this but for now make it specific to items being included in collections
            if($this->opo_datamodel->getTableName($t_recommendation->get('table_num')) == 'ca_objects' && $this->opo_datamodel->getTableName($t_recommendation->get('assoc_table_num')) == 'ca_collections') {
                // Add the item to the collection
                $objects_x_collections = new ca_objects_x_collections();
                $objects_x_collections->setMode(ACCESS_WRITE);
		        $objects_x_collections->set('object_id', $t_recommendation->get('row_id'));
		        $objects_x_collections->set('collection_id', $t_recommendation->get('assoc_row_id'));
		        $objects_x_collections->set('type_id', 120);
                $objects_x_collections->insert();
            }
        }
        else if($recommendation_type == 'Creation') {
            // Technically we could probably generalize this but for now with collections this makes the most sense
            if($this->opo_datamodel->getTableName($t_recommendation->get('assoc_table_num')) == 'ca_collections') {
                //
                // Create the collection
                //
                $collections = new ca_collections();
                $collections->setMode(ACCESS_WRITE);
                $collections->set('type_id', 118);
                $collections->set('access', $recommendation_access);
                $collections->insert();
                //
                // Create the collection's label
                //
                $collection_labels = new ca_collection_labels();
                $collection_labels->setMode(ACCESS_WRITE);
                $collection_labels->set('collection_id', $collections->get('collection_id'));
                $collection_labels->set('locale_id', 1);
                $collection_labels->set('name', $t_recommendation->get('new_assoc_name'));
                $collection_labels->set('is_preferred', 1);
                $collection_labels->insert();
                //
                // Associate the item with the new collection
                //
                $objects_x_collections = new ca_objects_x_collections();
                $objects_x_collections->setMode(ACCESS_WRITE);
		        $objects_x_collections->set('object_id', $t_recommendation->get('row_id'));
		        $objects_x_collections->set('collection_id', $collections->get('collection_id'));
		        $objects_x_collections->set('type_id', 120);
                $objects_x_collections->insert();
            }
        }
        
        // Actually "approve" it in the database by setting the `Moderated On` field
		if (!$t_recommendation->moderate($this->request->getUserID())) {
		 	$va_error = _t("Could not approve recommendation");
        }
        
        // Show the user a notification to make sure they know whats happening
		if(isset($va_error)){
			$this->notification->addNotification($va_error, __NOTIFICATION_TYPE_ERROR__);
		}
		else{
		    $this->notification->addNotification(_t("The recommendation has been approved"), __NOTIFICATION_TYPE_INFO__);
		}
        
        // Show the listing again
		$this->List();
	}
	# -------------------------------------------------------
    /**
     * Delete the recommendation
     */
    public function Delete() {
        // Get the values submitted in the form
		$recommendation_id = $this->request->getParameter('recommendation_id', pString);
        
        // Get an instance of the model
		$t_recommendation = new ca_item_recommendations($recommendation_id);
        
        // Make sure we're working with a valid record
		if (!$t_recommendation->getPrimaryKey()) {
			$va_error = _t("The recommendation does not exist");	
        }
        
        // Delete the record from the database
		$t_recommendation->setMode(ACCESS_WRITE);;
		if (!$t_comment->delete()) {
		 	$va_error = _t("Could not delete recommendation");
		}
        
        // Show the user a notification to make sure they know whats happening
        if(isset($va_error)){
		    $this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
		}
		else{
			$this->notification->addNotification(_t($t_comment.get('comment') . " has been deleted"), __NOTIFICATION_TYPE_INFO__);
        }
        
        // Show the listing again
		$this->List();
	}
	
	# -------------------------------------------------------
	/**
	 * Returns string representing the name of the item the search will return
	 *
	 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
	 */
	public function searchName($ps_mode='singular') {
		return ($ps_mode == 'singular') ? _t("recommendation") : _t("recommendations");
	}
	# -------------------------------------------------------
	/**
	 * Returns string representing the name of this controller (minus the "Controller" part)
	 */
	public function controllerName() {
		return 'Recommendation';
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function Info() {
		$o_dm = Datamodel::load();
		
        $t_recommendations = new ca_item_recommendations();
        $this->view->setVar('association_recommendation_count', ($t_recommendations->getAssociationRecommendationCount()));
        $this->view->setVar('creation_recommendation_count', ($t_recommendations->getCreationRecommendationCount()));
		$this->view->setVar('unmoderated_recommendation_count', ($t_recommendations->getUnmoderatedRecommendationCount()));
		$this->view->setVar('moderated_recommendation_count', ($t_recommendations->getModeratedRecommendationCount()));
		$this->view->setVar('total_recommendation_count', ($t_recommendations->getRecommendationCount()));
		
		return $this->render('widget_recommendations_info_html.php', true);
	}
 }
 ?>