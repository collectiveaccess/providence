<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/Mapping.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace Elastic8;

use ApplicationVars;
use Configuration;
use Datamodel;
use Db;
use SearchBase;

class Mapping {
	/**
	 * @var Configuration
	 */
	protected $search_conf;
	/**
	 * @var Configuration
	 */
	protected $indexing_conf;
	/**
	 * @var SearchBase
	 */
	protected $search_base;

	/**
	 * @var Db
	 */
	protected $db;

	/**
	 * Element info array
	 *
	 * @var array
	 */
	protected $element_info;

	/**
	 * @var ApplicationVars
	 */
	protected $app_vars;

	/**
	 * Elastic major version number in use
	 *
	 * @var int
	 */
	protected $version = 8;

	/**
	 * Mapping constructor.
	 */
	public function __construct() {
		// set up basic properties
		$this->search_conf = Configuration::load( Configuration::load()->get( 'search_config' ) );
		$this->indexing_conf = Configuration::load( __CA_CONF_DIR__ . '/search_indexing.conf' );

		$this->db = new Db();
		$this->search_base = new SearchBase( $this->db, null, false );

		$this->element_info = [];

		$this->app_vars = new ApplicationVars( $this->db );

		$this->prefetchElementInfo();
	}

	/**
	 * Check if the ElasticSearch mapping needs refreshing
	 *
	 * @return bool
	 */
	public function needsRefresh() {
		return ( time() > $this->app_vars->getVar( 'ElasticSearchMappingRefresh' ) );
	}

	/**
	 * Ping the ElasticSearch mapping, effectively resetting the refresh time
	 */
	public function ping() {
		$this->app_vars->setVar( 'ElasticSearchMappingRefresh', time() + 24 * 60 * 60 );
		$this->app_vars->save();
	}

	/**
	 * @return Configuration
	 */
	protected function getIndexingConf() {
		return $this->indexing_conf;
	}

	/**
	 * @return SearchBase
	 */
	protected function getSearchBase() {
		return $this->search_base;
	}

	/**
	 * @return Db
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 * Returns all tables that are supposed to be indexed
	 *
	 * @return array
	 */
	public function getTables() {
		return $this->getIndexingConf()->getAssocKeys();
	}

	/**
	 * Get indexing fields and options for a given table (and its related tables)
	 *
	 * @param $table
	 *
	 * @return array
	 */
	public function getFieldsToIndex( $table ) {
		if ( ! Datamodel::tableExists( $table ) ) {
			return [];
		}
		$table_fields = $this->getSearchBase()
			->getFieldsToIndex( $table, null, [ 'clearCache' => true, 'includeNonRootElements' => true ] );
		if ( ! is_array( $table_fields ) ) {
			return [];
		}

		$rewritten_fields = [];
		foreach ( $table_fields as $field_name => $field_options ) {
			if ( preg_match( '!^_ca_attribute_([\d]*)$!', $field_name, $matches ) ) {
				$rewritten_fields[ $table . '.A' . $matches[1] ] = $field_options;
			} else {
				$i = Datamodel::getFieldNum( $table, $field_name );
				if ( ! $i ) {
					continue;
				}

				$rewritten_fields[ $table . '.I' . $i ] = $field_options;
			}
		}

		$related_tables = $this->getSearchBase()->getRelatedIndexingTables( $table );
		foreach ( $related_tables as $related_table ) {
			$related_table_fields = $this->getSearchBase()->getFieldsToIndex( $table, $related_table,
				[ 'clearCache' => true, 'includeNonRootElements' => true ] );
			foreach ( $related_table_fields as $related_table_field => $related_table_field_options ) {
				if ( preg_match( '!^_ca_attribute_([\d]*)$!', $related_table_field, $matches ) ) {
					$rewritten_fields[ $related_table . '.A' . $matches[1] ] = $related_table_field_options;
				} else {
					$i = Datamodel::getFieldNum( $related_table, $related_table_field );
					if ( ! $i ) {
						continue;
					}

					$rewritten_fields[ $related_table . '.I' . $i ] = $related_table_field_options;
				}
			}
		}

		return $rewritten_fields;
	}

	/**
	 * Prefetch all element info. This is more efficient than running a db query every
	 * time @see Mapping::getElementInfo() is called. Also @see $opa_element_info.
	 */
	protected function prefetchElementInfo() {
		if ( is_array( $this->element_info ) && ( sizeof( $this->element_info ) > 0 ) ) {
			return;
		}

		$elements = $this->getDb()->query( 'SELECT * FROM ca_metadata_elements' );

		$this->element_info = [];
		while ( $elements->nextRow() ) {
			$element_id = $elements->get( 'element_id' );

			$this->element_info[ $element_id ] = [
				'element_id' => $element_id,
				'element_code' => $elements->get( 'element_code' ),
				'datatype' => $elements->get( 'datatype' )
			];
		}
	}

	/**
	 * Get info for given element id. Keys in the result array are:
	 *    element_id
	 *    element_code
	 *    datatype
	 *
	 * @param int $element_id
	 *
	 * @return array|bool
	 */
	public function getElementInfo( $element_id ) {
		if ( isset( $this->element_info[ $element_id ] ) ) {
			return $this->element_info[ $element_id ];
		}

		return false;
	}

	/**
	 * Get ElasticSearch property config fragment for a given element_id
	 *
	 * @todo: We should respect settings in the indexing config here. Right now they're ignored.
	 * @todo: The default cfg doesn't have any element-level indexing settings but sometimes they can come in handy
	 *
	 * @param string $table
	 * @param int $element_id
	 * @param array $pa_element_info @see Mapping::getElementInfo()
	 * @param array $indexing_config
	 *
	 * @return array
	 */
	public function getConfigForElement( $table, $element_id, $pa_element_info, $indexing_config ) {
		if ( ! is_numeric( $element_id ) && ( intval( $element_id ) > 0 ) ) {
			return [];
		}
		$element_info = $this->getElementInfo( $element_id );
		$element_code = $element_info['element_code'];
		if ( ! $element_code ) {
			return [];
		}

		// init: we never store -- all SearchResult::get() operations are now done on our database tables
		$element_config = [
			$table . '/' . $element_code => []
		];

		if ( in_array( 'INDEX_AS_IDNO', $indexing_config ) ) {
			$element_config[ $table . '/' . $element_code ]['analyzer'] = 'keyword_lowercase';
		}

		if ( in_array( 'TOKENIZE_WS', $indexing_config ) ) {
			$element_config[ $table . '/' . $element_code ]['analyzer'] = 'whitespace';
		}

		// @todo break this out into separate classes in the Elastic8\FieldTypes namespace!?
		switch ( $pa_element_info['datatype'] ) {
			case __CA_ATTRIBUTE_VALUE_DATERANGE__:
				$element_config[ $table . '/' . $element_code ]['type'] = 'date';
				$element_config[ $table . '/' . $element_code ]['format'] = 'date_time_no_millis';
				$element_config[ $table . '/' . $element_code ]['ignore_malformed'] = true;
				$element_config[ $table . '/' . $element_code . '_text' ]['type'] = 'text';
				$element_config[ $table . '/' . $element_code . '_start' ]['type'] = 'date';
				$element_config[ $table . '/' . $element_code . '_start' ]['ignore_malformed'] = true;
				$element_config[ $table . '/' . $element_code . '_end' ]['type'] = 'date';
				$element_config[ $table . '/' . $element_code . '_end' ]['ignore_malformed'] = true;
				break;
			case __CA_ATTRIBUTE_VALUE_GEOCODE__:
				//@see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-geo-shape-type.html
				$element_config[ $table . '/' . $element_code ] = [
					'type' => 'geo_shape',
				];
				// index text content as is -- sometimes useful for full text place search
				$element_config[ $table . '/' . $element_code . '_text' ] = [ 'type' => 'text' ];
				break;
			case __CA_ATTRIBUTE_VALUE_CURRENCY__:
				// we want to do range searches on currency too, so we gotta store the currency identified (USD) separately
				$element_config[ $table . '/' . $element_code ]['type'] = 'double';
				$element_config[ $table . '/' . $element_code . '_currency' ] = [ 'type' => 'text' ];
				break;
			case __CA_ATTRIBUTE_VALUE_LENGTH__:
			case __CA_ATTRIBUTE_VALUE_WEIGHT__:
				// we don't index units here -- we always index in meters / kg, so it's just a float
				$element_config[ $table . '/' . $element_code ]['type'] = 'double';
				break;
			case __CA_ATTRIBUTE_VALUE_TIMECODE__:
			case __CA_ATTRIBUTE_VALUE_NUMERIC__:
				$element_config[ $table . '/' . $element_code ]['type'] = 'double';
				break;
			case __CA_ATTRIBUTE_VALUE_INTEGER__:
				$element_config[ $table . '/' . $element_code ]['type'] = 'long';
				break;
			case __CA_ATTRIBUTE_VALUE_TEXT__:
			case __CA_ATTRIBUTE_VALUE_LIST__:
			case __CA_ATTRIBUTE_VALUE_URL__:
			case __CA_ATTRIBUTE_VALUE_LCSH__:
			case __CA_ATTRIBUTE_VALUE_GEONAMES__:
			case __CA_ATTRIBUTE_VALUE_FILE__:
			case __CA_ATTRIBUTE_VALUE_MEDIA__:
			case __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__:
			case __CA_ATTRIBUTE_VALUE_OBJECTREPRESENTATIONS__:
			case __CA_ATTRIBUTE_VALUE_ENTITIES__:
			case __CA_ATTRIBUTE_VALUE_PLACES__:
			case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:
			case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:
			case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:
			case __CA_ATTRIBUTE_VALUE_LOANS__:
			case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:
			case __CA_ATTRIBUTE_VALUE_OBJECTS__:
			case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:
			default:
				$element_config[ $table . '/' . $element_code ]['type'] = 'text';
				break;
		}

		return $element_config;
	}

	/**
	 * Get ElasticSearch property config fragment for a given intrinsic field
	 *
	 * @param string $table
	 * @param int $field_num
	 * @param array $indexing_config
	 *
	 * @return array
	 */
	public function getConfigForIntrinsic( $table, $field_num, $indexing_config ) {
		$field_name = Datamodel::getFieldName( $table, $field_num );
		if ( ! $field_name ) {
			return [];
		}
		$instance = Datamodel::getInstance( $table );

		$field_options = [
			$table . '/' . $field_name => []
		];

		if ( in_array( 'DONT_TOKENIZE', $indexing_config ) ) {
			$field_options[ $table . '/' . $field_name ]['index'] = 'not_analyzed';
		}

		if ( in_array( 'INDEX_AS_IDNO', $indexing_config ) ) {
			unset( $field_options[ $table . '/' . $field_name ]['index'] );
			$field_options[ $table . '/' . $field_name ]['analyzer'] = 'keyword_lowercase';
		}

		switch ( $instance->getFieldInfo( $field_name, 'FIELD_TYPE' ) ) {
			case ( FT_TEXT ):
			case ( FT_MEDIA ):
			case ( FT_FILE ):
			case ( FT_PASSWORD ):
			case ( FT_VARS ):
				$field_options[ $table . '/' . $field_name ]['type'] = 'text';
				unset( $field_options[ $table . '/' . $field_name ]['index'] );
				break;
			case ( FT_NUMBER ):
			case ( FT_TIME ):
			case ( FT_TIMERANGE ):
			case ( FT_TIMECODE ):
				// list-based intrinsics get indexed with both item_id and label text, like so:
				// image Image 24 -- for a ca_objects type_id image
				if ( $instance->getFieldInfo( $field_name, 'LIST_CODE' ) ) {
					$field_options[ $table . '/' . $field_name ]['type'] = 'text';
					unset( $field_options[ $table . '/' . $field_name ]['index'] );
					break;
				} else {
					$field_options[ $table . '/' . $field_name ]['type'] = 'double';
					unset( $field_options[ $table . '/' . $field_name ]['analyzer'] );
					unset( $field_options[ $table . '/' . $field_name ]['index'] );
					break;
				}
				break;
			case ( FT_TIMESTAMP ):
			case ( FT_DATETIME ):
			case ( FT_HISTORIC_DATETIME ):
			case ( FT_DATE ):
			case ( FT_HISTORIC_DATE ):
			case ( FT_DATERANGE ):
			case ( FT_HISTORIC_DATERANGE ):
				$field_options[ $table . '/' . $field_name ]['type'] = 'date';
				$field_options[ $table . '/' . $field_name ]['format'] = 'date_time_no_millis';
				$field_options[ $table . '/' . $field_name ]['ignore_malformed'] = true;
				unset( $field_options[ $table . '/' . $field_name ]['analyzer'] );
				unset( $field_options[ $table . '/' . $field_name ]['index'] );
				break;
			case ( FT_BIT ):
				$field_options[ $table . '/' . $field_name ]['type'] = 'integer';
				unset( $field_options[ $table . '/' . $field_name ]['index'] );
				break;
			default:
				$field_options[ $table . '/' . $field_name ]['type'] = 'text';
				unset( $field_options[ $table . '/' . $field_name ]['index'] );
				break;
		}

		return $field_options;
	}

	/**
	 * Get the mapping in the array format the Elasticsearch PHP API expects
	 *
	 * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_index_management_operations.html#_put_mappings_api
	 * @return array
	 */
	public function get() {
		$mapping_config = [];

		foreach ( $this->getTables() as $table ) {
			$mapping_config[ $table ]['_source']['enabled'] = true;
			$mapping_config[ $table ]['properties'] = [];

			foreach ( $this->getFieldsToIndex( $table ) as $field => $indexing_info ) {
				if ( preg_match( "/^(ca[\_a-z]+)\.A([0-9]+)$/", $field, $matches ) ) { // attribute
					$mapping_config[ $table ]['properties']
						= array_merge(
						$mapping_config[ $table ]['properties'],
						$this->getConfigForElement(
							$matches[1],
							(int) $matches[2],
							$this->getElementInfo( (int) $matches[2] ),
							$indexing_info
						)
					);
				} elseif ( preg_match( "/^(ca[\_a-z]+)\.I([0-9]+)$/", $field, $matches ) ) { // intrinsic
					$mapping_config[ $table ]['properties']
						= array_merge(
						$mapping_config[ $table ]['properties'],
						$this->getConfigForIntrinsic(
							$matches[1],
							(int) $matches[2],
							$indexing_info
						)
					);
				}
			}

			// add config for modified and created, which are always indexed
			$mapping_config[ $table ]['properties']["modified"] = [
				'type' => 'date',
				'format' => 'date_optional_time',
				'ignore_malformed' => true
			];
			$mapping_config[ $table ]['properties']["created"] = [
				'type' => 'date',
				'format' => 'date_optional_time',
				'ignore_malformed' => true
			];

			$mapping_config[ $table ]['dynamic_templates'] = [
				[
					'content_ids' => [
						'match' => '*_content_ids',
						'mapping' => [
							'type' => 'integer',
						]
					]
				]
			];
		}

		return $mapping_config;
	}
}
