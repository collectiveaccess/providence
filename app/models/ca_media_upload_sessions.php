<?php
/** ---------------------------------------------------------------------
 * app/models/ca_media_upload_sessions.php
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

BaseModel::$s_ca_models_definitions['ca_media_upload_sessions'] = array(
 	'NAME_SINGULAR' 	=> _t('media submission'),
 	'NAME_PLURAL' 		=> _t('media submissions'),
 	'FIELDS' 			=> array(
 		'session_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
			'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '','LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this upload')
		),
		'user_id' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_OMIT,
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => null,
			'DONT_ALLOW_IN_UI' => true,
			'LABEL' => _t('Submitted by user'), 'DESCRIPTION' => _t('User submitting this upload.')
		),
		'created_on' => array(
			'FIELD_TYPE' => FT_TIMESTAMP, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => null,
			'LABEL' => _t('Creation date'), 'DESCRIPTION' => _t('The date and time the upload was started on.')
		),
		'submitted_on' => array(
			'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'LABEL' => _t('Submission  date'), 'DESCRIPTION' => _t('The date and time the upload was submitted on. An empty value indicates an unsubmitted upload.')
		),
		'completed_on' => array(
			'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'LABEL' => _t('Upload completion date'), 'DESCRIPTION' => _t('The date and time the upload was completed on. An empty value indicates an incomplete upload.')
		),
		'last_activity_on' => array(
			'FIELD_TYPE' => FT_DATETIME, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => true, 
			'DEFAULT' => null,
			'LABEL' => _t('Date of last upload activity'), 'DESCRIPTION' => _t('The date and time activity was last recorded on the upload.')
		),
		'session_key' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => '',
			'LABEL' => _t('Upload key'), 'DESCRIPTION' => _t('Unique key for the upload.'),
			'BOUNDS_LENGTH' => array(1,36)
		),
		'source' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'BOUNDS_LENGTH' => [0, 30],
			'DEFAULT' => '',
			'LABEL' => _t('Source of session'), 'DESCRIPTION' => _t('Source of session. Use UPLOADER for file uploader; FORM:<form_code> for front-end importer forms.')
		),
		'status' => array(
			'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 30, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'BOUNDS_LENGTH' => [0, 30],
			'DEFAULT' => 'IN_PROGRESS',
			'BOUNDS_CHOICE_LIST' => array(
				_t('In progress') => 'IN_PROGRESS',
				_t('Submitted') => 'SUBMITTED',
				_t('Processing') => 'PROCESSING',
				_t('Processed') => 'PROCESSED',
				_t('In review') => 'IN_REVIEW',
				_t('Accepted') => 'ACCEPTED',
				_t('Rejected') => 'REJECTED',
				_t('Completed') => 'COMPLETED',
				_t('Cancelled') => 'CANCELLED',
				_t('Error') => 'ERROR'
			),
			'LABEL' => _t('Status of session'), 'DESCRIPTION' => _t('Status of session. Possible states: IN_PROGRESS, SUBMITTED, PROCESSING, PROCESSED, IN_REVIEW, ACCEPTED, REJECTED, ERROR.')
		),
		'num_files' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('File count'), 'DESCRIPTION' => _t('Number of files in upload.')
		),
		'total_bytes' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Total upload size'), 'DESCRIPTION' => _t('The total size of the upload for all files, in bytes.')
		),
		'error_code' => array(
			'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Error code'), 'DESCRIPTION' => _t('Error code. Zero if no error.')
		),
		'metadata' => array(
			'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
			'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
			'IS_NULL' => false, 
			'DEFAULT' => 0,
			'LABEL' => _t('Associated form metadata'), 'DESCRIPTION' => _t('User-entered metadata for upload.')
		)
 	)
);

class ca_media_upload_sessions extends BaseModel {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_media_upload_sessions';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'session_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('session_key');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';


	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('created_on');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => [],
		"RELATED_TABLES" => []
	);
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	# Search
	# ------------------------------------------------------
	protected $SEARCH_CLASSNAME = null;
	protected $SEARCH_RESULT_CLASSNAME = 'MediaUploadSessionSearchResult';
	
	# ------------------------------------------------------
	# --- Constructor
	#
	# This is a function called when a new instance of this object is created. This
	# standard constructor supports three calling modes:
	#
	# 1. If called without parameters, simply creates a new, empty objects object
	# 2. If called with a single, valid primary key value, creates a new objects object and loads
	#    the record identified by the primary key value
	#
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function updateStats() {
		if(!$this->isLoaded()) { return null; }
		$files = $this->getFileList();
		
		$this->set('num_files', sizeof($files));
		
		$total_bytes = 0;
		foreach($files as $f) {
			$total_bytes += $f['total_bytes'];
		}	
	
		$this->set('total_bytes', $total_bytes);	
		
		return $this->update();
	}
	# ------------------------------------------------------
	/**
	 * Check if currently loaded upload is marked as complete
	 *
	 * @return int Unix timestamp for date/time completed, null if no upload is loaded, or false if the uploaf is not complete.
	 */
	public function isComplete() {
		if(!$this->isLoaded()) { return null; }
		$completed_on = $this->get('completed_on', ['getDirectDate' => true]);
		return ($completed_on > 0) ? $completed_on : false;
	}
	# ------------------------------------------------------
	/**
	 * Check if currently loaded upload is marked as errored
	 *
	 * @return int Error code, or false if no error
	 */
	public function hasError() {
		if(!$this->isLoaded()) { return null; }
		return ($error_code = (int)$this->get('error_code')) ? $error_code : false;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	public function getFileList(?array $options=null) : ?array {
		if(!($session_id = caGetOption('session_id', $options, null))){
			$session_id = $this->getPrimaryKey();
		} 
		if(!$session_id) { return null; }
		
		$db = $this->getDb();
		
		$qr = $db->query("SELECT * FROM ca_media_upload_session_files WHERE session_id = ?", [$session_id]);
		
		$files = [];
		while($qr->nextRow()) {
			$row = $qr->getRow();
			$files[$row['filename']] = $row;
		}
		
		return $files;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return array
	 */
	public static function getFileListForSession(int $session_id) : ?array {
		$t = new ca_media_upload_sessions();
		return $t->getFileList(['session_id' => $session_id]);
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return 
	 */
	public function getFile(string $filename) : ?ca_media_upload_session_files {
		if(!$this->isLoaded()) { return null; }
		
		return ca_media_upload_session_files::find(
			['session_id' => $this->getPrimaryKey(), 'filename' => $filename], 
			['returnAs' => 'firstModelInstance']
		);
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @return bool
	 */
	public function setFile(string $filename, array $data, ?array $options=null) : ?ca_media_upload_session_files {
		if(!$this->isLoaded()) { return false; }
		
		if(!($t_file = $this->getFile($filename))) {
			$t_file = new ca_media_upload_session_files();
			$t_file->set('filename', $filename);
			$t_file->set('session_id', $this->getPrimaryKey());
		}
		
		foreach(['completed_on', 'last_activity_on', 'bytes_received', 'total_bytes', 'error_code'] as $f) {
			if(isset($data[$f])) {
				$t_file->set($f, $data[$f]);
			}
		}
		if($t_file->getPrimaryKey() ? $t_file->update() : $t_file->insert()) {
			return $t_file;
		}
		$this->errors = $t_file->errors;
		return null;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function processSessions(?array $options=null) : int {
		$limit = caGetOption('limit', $options, 10);
		$session_ids = ca_media_upload_sessions::find(['submitted_on' => ['>', 0], 'completed_on' => null], ['returnAs' => 'ids']);
			
		$log = caGetImportLogger();
		
		$c = 0;
		
		$errors = [];
		while($session_id = array_shift($session_ids)) {
			$session = new ca_media_upload_sessions($session_id);
			
			$errors = $warnings = [];
			$files_imported = 0;
			
			$d = $session->get('metadata');
			$data = $d['data'];
			$config = $d['configuration'];
			$mode = strtolower(caGetOption('importMode', $config['options'], 'media'));
			
			$user_id = $session->get('user_id');
			
			$form = preg_replace('!^FORM:!', 'IMPORTER:', $session->get('source'));
			
			$log->logInfo("Processing session for form ".$config['formTitle']);
			
			$table = $config['table'];
			$type = $config['type'];
			$idno = $config['idno'];
			$status = $config['status'];
			$access = $config['access'];
			
			$rep_type = $config['representation_type'];
			$rep_status = $config['representation_status'];
			$rep_access = $config['representation_access'];
			
			$submission_status = $config['submission_status'];
			
			$locale_id = ca_locales::codeToID(caGetOption('alwaysUseLocale', $config, ca_locales::getDefaultCataloguingLocaleID()));
			
			$form_values = [];
			foreach($config['content'] as $k => $info) {
				$v = $data[$info['bundle']];
				$form_values[$k] = $v;
			}
			$label = caProcessTemplate($config['display'], $form_values);
			
			$media = array_filter($session->getFileList(), function($v) {
				return ($v['completed_on'] > 0);
			});
			foreach($media as $path => $info) {
				if(ca_object_representations::mediaExists($path)) {
					$filename = pathinfo($path, PATHINFO_BASENAME);
					unset($media[$path]);
					self::_setSessionWarning($session, $label, $warnings[$filename][] = _t('Media file <em>%1</em> is already loaded (file was skipped)', $filename));
				}
			}
			
			if(($mode === 'hierarchy') && (sizeof($media) === 1)) {	// don't create hierarchies with only one media item
				$mode = 'media';
			}
			if(sizeof($media) === 0) {
				// all media filtered - send warning notification and bail
				self::_setSessionError($session, $label, $errors['__general__'][] = _t('Submission was skipped because there are no media files to import'));
				goto updateSession;
			}
			
			$dont_moderate = Configuration::load()->get("dont_moderate_tags");
			
			$album_rep = null;	// rep used on hierarchy "album"
			
			$file_map = [];
			switch($mode) {
				case 'hierarchy':
					// create top-level record
					$t = Datamodel::getInstance($table);
					$t->set('type_id', $type);
					$t->set('status', $status);
					$t->set('access', $access);
					$t->setIdnoWithTemplate($idno);
					$t->insert();
					
					if ($t->numErrors()) {
						self::_setSessionError($session, $label, $errors['__general__'][] = _t('Could not create hierarchy parent %1: %2 (submission was skipped)', $label, join(", ", $t->getErrors())));
						goto updateSession;
					}
					
					$t->addLabel(['name' => $label], $locale_id, null, true);
					
					$t->set('submission_user_id', $user_id);
					$t->set('submission_group_id', null);
					$t->set('submission_status_id', $submission_status);
					$t->set('submission_via_form', $form);
					$t->set('submission_session_id', $session_id);
					$t->update();
					
					if($t->numErrors()) {
						self::_setSessionWarning($session, $label, $warnings['__general__'][] = _t('Could not set submitter information for hierarchy parent %1: %2', $label, join(", ", $t->getErrors())));
					}
					
					$errors = self::_processContent($t, $config, $data);
							
					$album_rep = $t->addRepresentation(
						$p=array_shift(array_keys($media)), $rep_type, $locale_id, $rep_status, $rep_access, $is_primary, [], ['returnRepresentation' => true, 'original_filename' => pathinfo($p, PATHINFO_BASENAME)]
					);
					
					$root_filename = pathinfo($p, PATHINFO_BASENAME);
					
					if($t->numErrors()) {
						self::_setSessionWarning($session, $label, $warnings[$root_filename][] = _t('Could not add media %1 for hierarchy parent %2: %3', $root_filename, $label, join(", ", $t->getErrors())));
					} else {
						$files_imported++;
						$file_map[$root_filename][] = $t->getPrimaryKey();
					}
				
					$t_pk = $t->getPrimaryKey();
					
					// Add media
					$index = 1;
					$is_primary = true;
					foreach($media as $path => $info) {
						$filename = pathinfo($path, PATHINFO_BASENAME);
						
						$r = Datamodel::getInstance($table);
						$r->set('parent_id', $t_pk);
						$r->set('status', $status);
						$r->set('access', $access);
						$r->set('type_id', 'item');			// TODO: make configurable for sub-item
						$r->setIdnoWithTemplate($idno);		// TODO: make configurable for sub-item
						
						$r->insert();
						
						if ($r->numErrors()) {
							self::_setSessionError($session, $label, $errors[$filename][] = _t('Could not create media child record %1 for %2: %3 (file was skipped)', $filename, $label, join(", ", $r->getErrors())));
							continue;
						}
					
						$r->addLabel(['name' => $label." [{$index}]"], $locale_id, null, true);
						
						if ($r->numErrors()) {
							self::_setSessionError($session, $label, $errors[$filename][] = _t('Could not add label for media child record for %1: %2 (file was skipped)', $label, join(", ", $t->getErrors())));
							continue;
						}
						
						$r->set('submission_user_id', $user_id);
						$r->set('submission_group_id', null);
						$r->set('submission_status_id', $submission_status);
						$r->set('submission_via_form', $form);
						$r->set('submission_session_id', $session_id);
						
						$r->update();
						
						if($r->numErrors()) {
							self::_setSessionWarning($session, $label, $warnings[$filename][] = _t('Could not set submitter information for media child record for %1: %2', $label, join(", ", $r->getErrors())));
						}
						
						//
						if ($is_primary && $album_rep) {
							$r->addRelationship('ca_object_representations', $album_rep->getPrimaryKey());
							
							if($r->numErrors()) {
								self::_setSessionWarning($session, $label, $warnings[$filename][] = _t('Could not add media relation to %1 for media child record for %2: %3', $filename, $label, join(", ", $r->getErrors())));
							}
						} else { 
							$r->addRepresentation(
								$path, $rep_type, $locale_id, $rep_status, $rep_access, true, [], ['original_filename' => $root_filename]
							);
							
							if($r->numErrors()) {
								self::_setSessionWarning($session, $label, $warnings[$filename][] = _t('Could not add media %1 for media child record for %2: %3', $filename, $label, join(", ", $r->getErrors())));
							} else {
								$files_imported++;
								$file_map[$filename][] = $r->getPrimaryKey();
							}
						}
						$index++;
						$is_primary = false;
					}
					
					ca_metadata_alert_triggers::fireApplicableTriggers($t, __CA_MD_ALERT_CHECK_TYPE_SUBMISSION__);
					break;
				case 'allinone':
					// create top-level record
					$t = Datamodel::getInstance($table);
					$t->set('type_id', $type);
					$t->set('status', $status);
					$t->set('access', $access);
					$t->setIdnoWithTemplate($idno);
					$t->insert();
					
					if ($t->numErrors()) {
						self::_setSessionError($session, $label, $errors['__general__'][] = _t('Could not create all-in-one record for %1: %2 (submission was skipped)', $label, join(", ", $t->getErrors())));
						goto updateSession;
					}
					
					$t->addLabel(['name' => $label], $locale_id, null, true);
					
					if ($t->numErrors()) {
						self::_setSessionError($session, $label, $errors['__general__'][] = _t('Could not add label for all-in-one record for %1: %2 (submission was skipped)', $label, join(", ", $t->getErrors())));
						goto updateSession;
					}
					
					$t->set('submission_user_id', $user_id);
					$t->set('submission_group_id', null);
					$t->set('submission_status_id', $submission_status);
					$t->set('submission_via_form', $form);
					$t->set('submission_session_id', $session_id);
					$t->update();
					
					if($t->numErrors()) {
						self::_setSessionWarning($session, $label, $warnings['__general__'][] = _t('Could not set submitter information for all-in-one record for %1: %2', $label, join(", ", $t->getErrors())));
					}
					
					$errors = self::_processContent($t, $config, $data);
					
					// Add media
					$index = 1;
					$is_primary = true;
					foreach($media as $path => $info) {
						$filename = pathinfo($path, PATHINFO_BASENAME);
						
						$r = $t->addRepresentation(
							$path, $rep_type, $locale_id, $rep_status, $rep_access, $is_primary, [], ['returnRepresentation' => true, 'original_filename' => $filename]
						);
						$is_primary = false;
						$index++;
						
						if($t->numErrors()) {
							self::_setSessionWarning($session, $label, $warnings[$filename][] =  _t('Could not add media %1 for all-in-one record for %2: %3', $filename, $label, join(", ", $t->getErrors())));
						} else {
							$files_imported++;
							$file_map[$filename][] = $t->getPrimaryKey();
						}
					}
					
					ca_metadata_alert_triggers::fireApplicableTriggers($t, __CA_MD_ALERT_CHECK_TYPE_SUBMISSION__);
					break;
				case 'media':
				default:
					foreach($media as $path => $info) {
						$filename = pathinfo($path, PATHINFO_BASENAME);
						
						if(!($r = Datamodel::getInstance($table))) { 
							continue;
						}
						$r->set('parent_id', null);
						$r->set('status', $status);
						$r->set('access', $access);
						$r->set('type_id', 'item');			// TODO: make configurable for sub-item
						$r->setIdnoWithTemplate($idno);		// TODO: make configurable for sub-item
						
						$r->insert();
						
						if ($r->numErrors()) {
							self::_setSessionError($session, $label, $errors[$filename][] = _t('Could not create media record %1 for %2: %3 (file was skipped)', $filename, $label, join(", ", $t->getErrors())));
							continue;
						}
					
						$r->addLabel(['name' => $label." [{$index}]"], $locale_id, null, true);
						
						if ($r->numErrors()) {
							self::_setSessionError($session, $label, $errors[$filename][] = _t('Could not add label for media record %1 for %2: %3 (file was skipped)', $filename, $label, join(", ", $r->getErrors())));
							continue;
						}
						
						$r->set('submission_user_id', $user_id);
						$r->set('submission_group_id', null);
						$r->set('submission_status_id', $submission_status);
						$r->set('submission_via_form', $form);
						$r->set('submission_session_id', $session_id);
						
						$r->update();
						
						if($r->numErrors()) {
							self::_setSessionWarning($session, $label, $warnings[$filename][] = _t('Could not set submitter information for media record for %1: %2', $label, join(", ", $r->getErrors())));
						}
						
						$errors = self::_processContent($r, $config, $data);
	
						$r->addRepresentation(
							$path, $rep_type, $locale_id, $rep_status, $rep_access, true, [], ['original_filename' => $filename]
						);
						if($r->numErrors()) {
							self::_setSessionWarning($session, $label, $warnings[$filename][] = _t('Could not add media %1 for media record for %2: %3', $filename, $label, join(", ", $r->getErrors())));
						} else {
							$files_imported++;
							$file_map[$filename][] = $r->getPrimaryKey();
						}
						
						$index++;
						
					}
					
					ca_metadata_alert_triggers::fireApplicableTriggers($r, __CA_MD_ALERT_CHECK_TYPE_SUBMISSION__);
					break;
			}
			
		updateSession:
			$d['warnings'] = $warnings;
			$d['errors'] = $errors;
			$d['file_map'] = $file_map;
			$d['files_imported'] = $files_imported;
			print_R($d);
			$session->set('metadata', $d);
			$session->set('completed_on', _t('now'));
			$session->set('status', 'PROCESSED');
			$session->update();
			
			
			foreach(array_keys($media) as $path) {
				unlink($path);
			}
			
			$c++;
			if ($c > $limit) { break; }
		}
		
		return $c;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static private function _processContent(BaseModel $t_instance, array $config, array $data) : array {
		$table = $t_instance->tableName();
		$tags_added = 0;
		
		$errors = [];
		foreach($config['content'] as $k => $info) {
			$bundle_bits = explode('.', $info['bundle']);
		
			if($bundle_bits[0] === $table) {
				switch(sizeof($bundle_bits)) {
					case 3:	// container
						// TODO: implement
						break;
					case 2:	// attribute or intrinsic
						if(!is_array($data[$info['bundle']])) { $data[$info['bundle']] = [$data[$info['bundle']]]; }
						
						foreach($data[$info['bundle']] as $i => $d) {
							if($t_instance->hasField($bundle_bits[1])) {
								$t_instance->set($bundle_bits[1], $data[$info['bundle']]);
							} else {
								$t_instance->addAttribute([
									$bundle_bits[1] => $d
								], $bundle_bits[1]);
							}
						}
						$t_instance->update();
						
						break;
				}
			} else {
				if($bundle_bits[0] === 'ca_item_tags') {
					if(!is_array($data[$info['bundle']])) { $data[$info['bundle']] = [$data[$info['bundle']]]; }
					foreach($data[$info['bundle']] as $i => $d) {
						// is tags
						$tags = $d ? preg_split("![ ]*[,;]+[ ]*!", $d) : [];
						foreach($tags as $tag) {
							if($t_instance->addTag(
								$tag, $user_id, ca_locales::getDefaultCataloguingLocaleID(), 
								((in_array($table, ["ca_sets", "ca_set_items"])) || $dont_moderate) ? 1 : 0, null
							)) {
								$tags_added++;
							} else {
								$errors[] = join('; ', $t_instance->getErrors());
							}
						}	
					}
				} else {
					// is relationship
					if(!is_array($data[$info['bundle']])) { $data[$info['bundle']] = [$data[$info['bundle']]]; }
					foreach($data[$info['bundle']] as $i => $d) {
						$reltype = $info['relationshipType'];
						$t_instance->addRelationship($bundle_bits[0], $d, $reltype, null, null, null, null, ['idnoOnly' => true]);
					}
				}
			}
		}	
		return $errors;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static private function _setSessionError($session, $label, $error) {
		ca_metadata_alert_triggers::fireApplicableTriggers($session, __CA_MD_ALERT_CHECK_TYPE_SUBMISSION_ERROR__, array_merge(
			[
				'label' => $label, 
				'error' => $error
			]
		));
		
		$session->set('completed_on', _t('now'));
		$session->set('status', 'ERROR');
		return $session->update();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static private function _setSessionWarning($session, $label, $warning) {
		ca_metadata_alert_triggers::fireApplicableTriggers($session, __CA_MD_ALERT_CHECK_TYPE_SUBMISSION_WARNING__, array_merge(
			[
				'label' => $label, 
				'warning' => $warning
			]
		));
		
		return true;
	}
	# ------------------------------------------------------
}
