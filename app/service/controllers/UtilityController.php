<?php
/* ----------------------------------------------------------------------
 * app/service/controllers/UtilityController.php :
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
require_once(__CA_APP_DIR__.'/service/schemas/UtilitySchema.php');

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQLServices\Schemas\UtilitySchema;


class UtilityController extends \GraphQLServices\GraphQLServiceController {
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
				// Dates
				// ------------------------------------------------------------
				'parseDate' => [
					'type' => UtilitySchema::get('DateParseResult'),
					'description' => _t('Parse date expression'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'date',
							'type' => Type::string(),
							'description' => _t('Table to search')
						],
						[
							'name' => 'format',
							'type' => Type::string(),
							'defaultValue' => 'historic',
							'description' => _t('Format to return date interval start and end values. Possible values are historic (floating point format), unix (Unix timestamp). Default is historic. Note that Unix timestamp values cannot be returned for dates prior to 1 January 1970.')
						],
						[
							'name' => 'displayFormat',
							'type' => Type::string(),
							'defaultValue' => 'text',
							'description' => _t('Format to return text display date in. Possible values are text, delimited, iso8601, yearOnly, ymd. If omitted text is used.')
						],
						[
							'name' => 'locale',
							'type' => Type::string(),
							'description' => _t('Locale code to use when parsing. If omitted default user locale is used.')
						]
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						$date = trim($args['date']);
						
						if(!$date) {
							throw new \ServiceException(_t('Must specify date'));
						}
						
						$tep = new TimeExpressionParser();
						if(!$tep->parse($date)) {
							throw new \ServiceException(_t('Could not parse date %1', $date));
						}
						if($args['locale']) {
							$tep->setLanguage($args['locale']);
						}
						if(strtolower($args['format']) === 'unix') {
							$ret = $tep->getUnixTimestamps();
						} else {
							$ret = $tep->getHistoricTimestamps();
						}
						
						return ['start' => $ret['start'], 'end' => $ret['end'], 'text' => $tep->getText(['dateFormat' => $args['displayFormat']])];
					}
				],
				// ------------------------------------------------------------
				// Entity names
				// ------------------------------------------------------------
				'splitEntityName' => [
					'type' => UtilitySchema::get('EntityNameParseResult'),
					'description' => _t('Split entity name into components'),
					'args' => [
						[
							'name' => 'jwt',
							'type' => Type::string(),
							'description' => _t('JWT'),
							'defaultValue' => self::getBearerToken()
						],
						[
							'name' => 'name',
							'type' => Type::string(),
							'description' => _t('Name to split')
						],
						[
							'name' => 'displaynameFormat',
							'type' => Type::string(),
							'defaultValue' => 'original',
							'description' => _t('Format for generated displayname value. Possible values are surnameCommaForename, forenameCommaSurname, forenameSurname, forenamemiddlenamesurname, original. Default is original (original text)')
						],
						[
							'name' => 'locale',
							'type' => Type::string(),
							'description' => _t('Locale code to use when parsing. If omitted default user locale is used.')
						]
						
					],
					'resolve' => function ($rootValue, $args) {
						$u = self::authenticate($args['jwt']);
						
						$name = trim($args['name']);
						if(!strlen($name)) {
							throw new \ServiceException(_t('Must specify name'));
						}
						return DataMigrationUtils::splitEntityName($name, ['displaynameFormat' => $args['displaynameFormat'], 'locale' => $args['locale']]);
					}
				],
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
