<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/DataImporterConfigurationController.php :
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
require_once(__CA_LIB_DIR__.'/Service/GraphQLServiceController.php');
require_once(__CA_APP_DIR__.'/service/schemas/DataImporterConfigurationSchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\DataImporterConfigurationSchema;


class DataImporterConfigurationController extends \GraphQLServices\GraphQLServiceController {
	# -------------------------------------------------------
	#
	static $config = null;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$request, &$response, $view_paths) {
		parent::__construct($request, $response, $view_paths);
	}
	
	/**
	 *
	 */
	public function _default(){
		$qt = new ObjectType([
			'name' => 'Query',
			'fields' => [
				// ------------------------------------------------------------
				// Forms
				// ------------------------------------------------------------
				'importerList' => [
					'type' => DataImporterConfigurationSchema::get('DataImporterList'),
					'description' => _t('List of available importers'),
					'args' => [
						[
							'name' => 'table',
							'type' => Type::string(),
							'description' => _t('Table to return forms for. (Ex. ca_entities)')
						],
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = null;
						if($args['jwt']) {
							try {
								$u = self::authenticate($args['jwt']);
							} catch(Exception $e) {
								$u = new ca_users();
							}
						}
						
						$table = $args['table'];
						
						$importer_list = ca_data_importers::getImporters($table);
						
						$available_formats = array_flip(ca_data_importers::getAvailableInputFormats());
					
						$ret = array_map(function($v) use ($available_formats) {
							$entry = [
								'id' => $v['importer_id'],
								'code' => $v['importer_code'],
								'name' => $v['label'],
								'type' => Datamodel::getTableName($v['table_num']),
								'displayType' => $v['importer_type'],
								'dataFormats' => array_map(function($f) use ($available_formats) {
									return ['name' => $available_formats[strtolower($f)], 'code' => $f];
								}, $v['settings']['inputFormats'])
							];
							
							return $entry;
						}, $importer_list);
						
						return ['importers' => $ret];
					}
				]
				// ------------------------------------------------------------
			]
		]);
		
		$mt = new ObjectType([
			'name' => 'Mutation',
			'fields' => [
			
			]
		]);
		
		return self::resolve($qt, $mt);
	}
	# -------------------------------------------------------
}
