<?php
/* ----------------------------------------------------------------------
 * app/service/schemas/DataImporterConfigurationSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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

class DataImporterConfigurationSchema extends \GraphQLServices\GraphQLSchema {
	# -------------------------------------------------------
	/**
	 * 
	 */
	protected static function load() {
		return [
			$dataImporterFormatType = new ObjectType([
				'name' => 'DataImporterFormat',
				'description' => 'Data format',
				'fields' => [
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of data format'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Code for data format'
					]
				]
			]),
			$dataImporterType = new ObjectType([
				'name' => 'DataImporter',
				'description' => 'Data importer',
				'fields' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Data importer id'
					],
					'code' => [
						'type' => Type::string(),
						'description' => 'Data importer identifier'
					],
					'name' => [
						'type' => Type::string(),
						'description' => 'Name of data importer'
					],
					'type' => [
						'type' => Type::string(),
						'description' => 'Type of importer (Eg. ca_objects)'
					],
					'displayType' => [
						'type' => Type::float(),
						'description' => 'Type of importer for display (Eg. objects)'
					],
					'dataFormats' => [
						'type' => Type::listOf($dataImporterFormatType),
						'description' => 'Accepted data formats'
					]
				]
			]),
			$dataImporterListType = new ObjectType([
				'name' => 'DataImporterList',
				'description' => 'List of data importers',
				'fields' => [
					'importers' => [
						'type' => Type::listOf($dataImporterType),
						'description' => 'Available importers'
					]
				]
			])
		];
	}
	# -------------------------------------------------------
}