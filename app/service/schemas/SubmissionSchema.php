<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/SubmissionSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2022 Whirl-i-Gig
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
namespace GraphQLServices\Schemas;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

require_once(__CA_LIB_DIR__.'/Service/GraphQLSchema.php'); 

class SubmissionSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$importerFileProcessingWarningType = new ObjectType([
				'name' => 'ImporterFileProcessingWarning',
				'description' => 'Report for warning while processing file',
				'fields' => [
					'filename' => [
						'type' => Type::string(),
						'description' => 'File name'
					],
					'message' => [
						'type' => Type::string(),
						'description' => 'Warning message'
					]
				]
			]),
			$importerFileProcessingErrorType = new ObjectType([
				'name' => 'ImporterFileProcessingError',
				'description' => 'Report for error while processing file',
				'fields' => [
					'filename' => [
						'type' => Type::string(),
						'description' => 'File name'
					],
					'message' => [
						'type' => Type::string(),
						'description' => 'Error message'
					]
				]
			]),
			$importerFileLinkType = new ObjectType([
				'name' => 'ImporterFileLink',
				'description' => 'Link to imported file',
				'fields' => [
					'filename' => [
						'type' => Type::string(),
						'description' => 'File name'
					],
					'url' => [
						'type' => Type::string(),
						'description' => 'URL'
					]
				]
			]),
			$SubmissionFormFieldInfoType = new ObjectType([
				'name' => 'SubmissionFormFieldInfo',
				'description' => 'Description of form field',
				'fields' => [
					'bundle' => [
						'type' => Type::string(),
						'description' => 'Bundle specifier'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Field type'
					],
					'title' => [
						'type' => Type::string(),
						'description' => 'Title of form field for display'
					],
					'description' => [
						'type' => Type::string(),
						'description' => 'Title of form field for display'
					],
					'minimum' => [
						'type' => Type::float(),
						'description' => 'Minimum value'
					],
					'maximum' => [
						'type' => Type::float(),
						'description' => 'Maximum value'
					],
					'default' => [
						'type' => Type::string(),
						'description' => 'Field default value'
					]
				]
			]),	
			//
			//
			//
			$SubmissionSessionSummaryType = new ObjectType([
				'name' => 'SubmissionSessionSummary',
				'description' => 'Short information for Submission session',
				'fields' => [
					'sessionKey' => [
						'type' => Type::string(),
						'description' => 'Session key'
					],
					'status' => [
						'type' => Type::string(),
						'description' => 'Status code'
					],
					'statusDisplay' => [
						'type' => Type::string(),
						'description' => 'Status as display text'
					],
					'createdOn' => [
						'type' => Type::string(),
						'description' => 'Session created on'
					],
					'completedOn' => [
						'type' => Type::string(),
						'description' => 'Session completed on'
					],
					'lastActivityOn' => [
						'type' => Type::string(),
						'description' => 'Last activity for session'
					],
					'source' => [
						'type' => Type::string(),
						'description' => 'Source of session'
					],
					'user_id' => [
						'type' => Type::int(),
						'description' => 'Session user_id'
					],
					'username' => [
						'type' => Type::string(),
						'description' => 'Session user name'
					],
					'user' => [
						'type' => Type::string(),
						'description' => 'Session full user name'
					],
					'email' => [
						'type' => Type::string(),
						'description' => 'Session user email'
					],
					'label' => [
						'type' => Type::string(),
						'description' => 'Display label for session'
					],
					'source' => [
						'type' => Type::string(),
						'description' => 'Source of session data'
					],
					'files' => [
						'type' => Type::int(),
						'description' => 'Number of files uploaded'
					],
					'filesImported' => [
						'type' => Type::int(),
						'description' => 'Number of files actually imported (duplicates and errors may reduce the number of imported files)'
					],
					'totalBytes' => [
						'type' => Type::float(),
						'description' => 'Total quantity of data for upload, in bytes'
					],
					'receivedBytes' => [
						'type' => Type::float(),
						'description' => 'Quantity of data received, in bytes'
					],
					'totalSize' => [
						'type' => Type::string(),
						'description' => 'Total quantity of data for upload, formatted for display'
					],
					'receivedSize' => [
						'type' => Type::string(),
						'description' => 'Quantity of data received, formatted for display'
					],
					'warnings' => [
						'type' => Type::listOf($importerFileProcessingWarningType),
						'description' => 'List of warnings while processing'
					],
					'errors' => [
						'type' => Type::listOf($importerFileProcessingErrorType),
						'description' => 'List of errors while processing'
					],
					'urls' => [
						'type' => Type::listOf($importerFileLinkType),
						'description' => 'List URLs for imported files'
					],
					'searchUrl' => [
						'type' => Type::string(),
						'description' => 'URL for search results containing contents of submission'
					]
				]
			]),
			$SubmissionSessionUploadFileType = new ObjectType([
				'name' => 'SubmissionSessionUploadedFile',
				'description' => 'Uploaded file information',
				'fields' => [
					'path' => [
						'type' => Type::string(),
						'description' => 'File path'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'File name'
					],
					'totalBytes' => [
						'type' => Type::float(),
						'description' => 'Total quantity of data in file, in bytes'
					],
					'receivedBytes' => [
						'type' => Type::float(),
						'description' => 'Quantity of data received, in bytes'
					],
					'totalSize' => [
						'type' => Type::string(),
						'description' => 'Total quantity of data for upload, formatted for display'
					],
					'receivedSize' => [
						'type' => Type::string(),
						'description' => 'Quantity of data received, formatted for display'
					],
					'complete' => [
						'type' => Type::boolean(),
						'description' => 'File upload completed?'
					]
				]
			]),
			$SubmissionSessionDataType = new ObjectType([
				'name' => 'SubmissionSessionData',
				'description' => 'Full information for Submission session',
				'fields' => [
					'sessionKey' => [
						'type' => Type::string(),
						'description' => 'Session key'
					],
					'status' => [
						'type' => Type::string(),
						'description' => 'Status code'
					],
					'statusDisplay' => [
						'type' => Type::string(),
						'description' => 'Status as display text'
					],
					'createdOn' => [
						'type' => Type::string(),
						'description' => 'Session created on'
					],
					'completedOn' => [
						'type' => Type::string(),
						'description' => 'Session completed on'
					],
					'lastActivityOn' => [
						'type' => Type::string(),
						'description' => 'Last activity for session'
					],
					'source' => [
						'type' => Type::string(),
						'description' => 'Source of session'
					],
					'user_id' => [
						'type' => Type::int(),
						'description' => 'Session user_id'
					],
					'username' => [
						'type' => Type::string(),
						'description' => 'Session user name'
					],
					'email' => [
						'type' => Type::string(),
						'description' => 'Session user email'
					],
					'formData' => [
						'type' => Type::string(),
						'description' => 'Session form metadata'
					],
					'label' => [
						'type' => Type::string(),
						'description' => 'Display label for session'
					],
					'files' => [
						'type' => Type::int(),
						'description' => 'Number of files uploaded'
					],
					'filesUploaded' => [
						'type' => Type::listOf($SubmissionSessionUploadFileType),
						'description' => 'Data about uploaded files'
					],
					'totalBytes' => [
						'type' => Type::float(),
						'description' => 'Total quantity of data for upload, in bytes'
					],
					'receivedBytes' => [
						'type' => Type::float(),
						'description' => 'Quantity of data received, in bytes'
					],
					'totalSize' => [
						'type' => Type::string(),
						'description' => 'Total quantity of data for upload, formatted for display'
					],
					'receivedSize' => [
						'type' => Type::string(),
						'description' => 'Quantity of data received, formatted for display'
					]
					
				]
			]),
			$SubmissionSessionListType = new ObjectType([
				'name' => 'SubmissionSessionList',
				'description' => 'List Submission sessions',
				'fields' => [
					'sessions' => [
						'type' => Type::listOf($SubmissionSessionSummaryType),
						'description' => 'List of sessions forms'
					]
				]
			]),
			$SubmissionSessionKeyType = new ObjectType([
				'name' => 'SubmissionSessionKey',
				'description' => 'Submission session key',
				'fields' => [
					'sessionKey' => [
						'type' => Type::string(),
						'description' => 'Session key'
					],
					'defaults' => [
						'type' => Type::string(),
						'description' => 'Serialized form default values'
					]
				]
			]),
			$SubmissionSessionUpdateResultType = new ObjectType([
				'name' => 'SubmissionSessionUpdateResult',
				'description' => 'Result of session update',
				'fields' => [
					'updated' => [
						'type' => Type::int(),
						'description' => 'Update status'
					],
					'validationErrors' => [			// TODO: should be list
						'type' => Type::string(),
						'description' => 'Validation errors'
					]
				]
			]),
			$SubmissionSessionDeleteResultType = new ObjectType([
				'name' => 'SubmissionSessionDeleteResult',
				'description' => 'Result of session delete',
				'fields' => [
					'deleted' => [
						'type' => Type::int(),
						'description' => 'Delete status'
					]
				]
			]),
			$SubmissionSessionProcessResultType = new ObjectType([
				'name' => 'SubmissionProcessUpdateResult',
				'description' => 'Result of session processing',
				'fields' => [
					'status' => [
						'type' => Type::int(),
						'description' => 'Processing status'
					],
					'errors' => [			// TODO: should be list
						'type' => Type::string(),
						'description' => 'Procssing errors'
					]
				]
			]),	
			$SubmissionSessionUserType = new ObjectType([
				'name' => 'SubmissionSessionUser',
				'description' => 'List of users with sessions',
				'fields' => [
					'user_id' => [
						'type' => Type::int(),
						'description' => 'User id'
					],
					'fname' => [		
						'type' => Type::string(),
						'description' => 'First name'
					],
					'lname' => [		
						'type' => Type::string(),
						'description' => 'Last name'
					],
					'email' => [		
						'type' => Type::string(),
						'description' => 'Email address'
					],
					'user_name' => [		
						'type' => Type::string(),
						'description' => 'Username'
					],
				]
			]),	
			
			$SubmissionSessionFilterValuesType = new ObjectType([
				'name' => 'SubmissionSessionFilterValues',
				'description' => 'List of session user and status filter values',
				'fields' => [
					'users' => [
						'type' => Type::listOf($SubmissionSessionUserType),
						'description' => 'User list'
					],
					'statuses' => [
						'type' => Type::listOf(Type::string()),
						'description' => 'Status list'
					]
				]
			]),				
		];
	}
	# -------------------------------------------------------
}
