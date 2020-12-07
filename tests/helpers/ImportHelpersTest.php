<?php
/** ---------------------------------------------------------------------
 * tests/helpers/ImportHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage tests
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

require_once( __CA_APP_DIR__ . "/helpers/importHelpers.php" );


class ImportHelpersTest extends TestCase {

	protected $data;
	protected $item;
	protected $attributes;
	protected $parents;
	protected $groups;

	static $opa_valid_tables
		= array(
			'ca_objects',
			'ca_entities',
			'ca_occurrences',
			'ca_movements',
			'ca_loans',
			'ca_object_lots',
			'ca_storage_locations',
			'ca_places',
			'ca_item_comments'
		);

	private $va_mock_response_AAT
		= array(
			'People and Culture:Associated Concepts:concepts in the arts:artistic concepts:forms of expression:forms of expression:visual arts:abstraction'                                                                                                                 => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300056508',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'abstraction',
							),
					)
			),
			'Objects We Use:Visual Works:visual works:visual works by medium or technique:prints:prints by process or technique:prints by process:transfer method:intaglio prints:etchings'                                                                                 => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300041365',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'etchings',
							),

					)
			),
			'Objects We Use:Visual Works:visual works:visual works by medium or technique:works on paper'                                                                                                                                                                   => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300189621',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'works on paper',
							),
					)
			),
			'People and Culture:Styles and Periods:styles and periods by region:European:European styles and periods:modern European styles and movements:modern European fine arts styles and movements:Abstract'                                                          => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300108127',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'Abstract',
							),
					)
			),
			'People and Culture:Associated Concepts:concepts in the arts:artistic concepts:art genres:computer art'                                                                                                                                                         => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300069478',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'computer art',
							),
					)
			),
			'Descriptors:Processes and Techniques:processes and techniques:processes and techniques by specific type:image-making processes and techniques:painting and painting techniques:painting techniques:painting techniques by medium:acrylic painting (technique)' => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300182574',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'acrylic painting (technique)',
							),
					)
			),
			'Descriptors:Processes and Techniques:processes and techniques:processes and techniques by specific type:image-making processes and techniques:painting and painting techniques:painting (image-making)'                                                        => array(
				0 =>
					array(
						'ID'            =>
							array(
								'type'  => 'uri',
								'value' => 'http://vocab.getty.edu/aat/300054216',
							),
						'TermPrefLabel' =>
							array(
								'xml:lang' => 'en',
								'type'     => 'literal',
								'value'    => 'painting image-making',
							),
					)
			),
		);

	protected function setUp(): void {
		$this->data = [
			1 => "Verdun",
			2 => [ 'Cambrai', 'Arras' ],
			3 => 'Chateau Thierry',
			4 => 'Somme',
			5 => 'Popperinge',
			6 => 'Ypres;Somme;Cambrai;Ypres;Popperinge',
			7 => [ 'Antwerp', 'Dieppe|Charleois|Paschendale', 'Bruges' ]
		];
		$this->item = [
			'settings' => [
				'original_values'    => [
					'sector_ypres',
					'sector_somme',
					'sector_cambrai'
				],
				'replacement_values' => [
					'Value_Ypres',
					'Value_Somme',
					'Value_Cambrai'
				]
			]
		];

		$this->attributes = array(
			'movement_reason'    => "^1",
			'preferred_label'    => [ "^3" ],
			'nonpreferred_label' => array( "name" => "^5" )
		);

		$this->parents = array(
			array(
				'idno'       => '^1',
				'name'       => '^3',
				'attributes' => array(
					'description' => '^5'
				)
			)
		);

		$this->groups = array();
	}

	/**
	 * Delete all records we created for this test to avoid side effects with other tests
	 */
	protected function tearDown(): void {
		if ( $this->opb_care_about_side_effects ) {
			foreach ( $this->opa_record_map as $vs_table => &$va_records ) {
				$t_instance = Datamodel::getInstance( $vs_table );
				// delete in reverse order so that we can properly
				// catch potential hierarchical relationships
				rsort( $va_records );
				foreach ( $va_records as $vn_id ) {
					if ( $t_instance->load( $vn_id ) ) {
						$t_instance->setMode( ACCESS_WRITE );
						$t_instance->delete( true, array( 'hard' => true ) );
					}
				}
			}

			// check record counts again (make sure there are no lingering records)
			$this->checkRecordCounts();
		}
	}

	# -------------------------------------------------------
	private function checkRecordCounts() {
		// ensure there are no lingering records
		$o_db = new Db();
		foreach ( self::$opa_valid_tables as $vs_table ) {
			$qr_rows = $o_db->query( "SELECT count(*) AS c FROM {$vs_table}" );
			$qr_rows->nextRow();

			// these two are allowed to have hierarchy roots
			if ( in_array( $vs_table, array( 'ca_storage_locations', 'ca_places' ) ) ) {
				$vn_allowed_records = 1;
			} else {
				$vn_allowed_records = 0;
			}

			$this->assertEquals( $vn_allowed_records, $qr_rows->get( 'c' ),
				"Table {$vs_table} should be empty to avoid side effects between tests" );
		}
	}

	protected function _runGenericImportSplitter( $ps_refinery_name, $ps_table, $ps_type ) {
		global $g_ui_locale_id;
		$g_ui_locale_id       = 1;
		$ps_item_prefix       = "";
		$ps_refinery_class    = $this->_loadRefinery( $ps_refinery_name );
		$po_refinery_instance = $this->_createRefineryMock( $ps_refinery_class );
		$pa_destination_data  = array();
		$pa_group             = $this->groups;
		$pa_item              = $this->item;
		$pa_source_data       = $this->data;
		$pa_options           = array();
		$result               = caGenericImportSplitter( $ps_refinery_name, $ps_item_prefix, $ps_table,
			$po_refinery_instance, $pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options );

		return $result;
	}

	/**
	 * @param $refinery_name
	 *
	 * @return string
	 */
	protected function _loadRefinery( $refinery_name ): string {
		$refinery_class = $refinery_name . 'Refinery';
		require_once( join( DIRECTORY_SEPARATOR,
			[ __CA_APP_DIR__, 'refineries', $refinery_name, $refinery_class . '.php' ] ) );

		return $refinery_class;
	}

	protected function _createRefineryMock( $ps_name ) {
		$stubRefinery = $this->createMock( $ps_name );
		$stubRefinery->method( 'getName' )->willReturn( $ps_name );
		$stubRefinery->method( 'setReturnsMultipleValues' );

		return $stubRefinery;
	}

	protected function _runProcessRefineryParents( $refinery_name, $ps_table_name, $ps_type ) {
		global $g_ui_locale_id;
		$g_ui_locale_id = 1;
		$refinery_class = $this->_loadRefinery( $refinery_name );

		$stubRefinery          = $this->_createRefineryMock( $refinery_class );
		$ps_refinery_name      = $refinery_name;
		$ps_table              = $ps_table_name;
		$pa_parents            = $this->parents;
		$pa_parents[0]['type'] = $ps_type;
		$pa_source_data        = $this->data;
		$pa_item               = $this->item;
		$pn_c                  = 0;
		$pa_options            = array(
			'refinery' => $stubRefinery,
		);
		$result                = caProcessRefineryParents( $ps_refinery_name, $ps_table, $pa_parents, $pa_source_data,
			$pa_item, $pn_c,
			$pa_options );

		return $result;
	}

	protected function _createAATServiceStub( $ps_query ) {
		$o_service = $this->createStub( WLPlugInformationServiceAAT::class );
		$o_service->method( 'lookup' )
		          ->willReturn( $this->va_mock_response_AAT[ $ps_query ] );

		return $o_service;
	}

	# -------------------------------------------------------
	public function testAATExternal() {
		// some real-world examples
		$o_service = new WLPlugInformationServiceAAT();
		$result    = $o_service->lookup( [], 'test' );
		$this->assertIsArray( $result );
	}

	# -------------------------------------------------------
	public function testAATMatchPeople() {
		// some real-world examples
		$vs_query
			       = 'People and Culture:Associated Concepts:concepts in the arts:artistic concepts:forms of expression:forms of expression:visual arts:abstraction';
		$o_service = $this->_createAATServiceStub( $vs_query );
		$vm_ret    = caMatchAAT( explode( ':', $vs_query ), null, null, $o_service );
		$this->assertEquals( 'http://vocab.getty.edu/aat/300056508', $vm_ret );
	}

	public function testAATMatchPrints() {
		// some real-world examples
		$vs_query
			       = 'Objects We Use:Visual Works:visual works:visual works by medium or technique:prints:prints by process or technique:prints by process:transfer method:intaglio prints:etchings';
		$o_service = $this->_createAATServiceStub( $vs_query );
		$vm_ret    = caMatchAAT( explode( ':', $vs_query ), null, null, $o_service );

		$this->assertEquals( 'http://vocab.getty.edu/aat/300041365', $vm_ret );
	}

	public function testAATMatchPaper() {
		$vs_query  = 'Objects We Use:Visual Works:visual works:visual works by medium or technique:works on paper';
		$o_service = $this->_createAATServiceStub( $vs_query );
		// some real-world examples
		$vm_ret = caMatchAAT( explode( ':', $vs_query ), null, null, $o_service );

		$this->assertEquals( 'http://vocab.getty.edu/aat/300189621', $vm_ret );
	}

	public function testAATMatchAbstractArtRemoveParens() {
		// some real-world examples

		$vs_query
			= 'People and Culture:Styles and Periods:styles and periods by region:European:European styles and periods:modern European styles and movements:modern European fine arts styles and movements:Abstract';

		$o_service = $this->_createAATServiceStub( $vs_query );
		// some real-world examples
		$vm_ret = caMatchAAT( explode( ':', $vs_query ), 180, array( 'removeParensFromLabels' => true ), $o_service );

		$this->assertEquals( 'http://vocab.getty.edu/aat/300108127', $vm_ret );
	}

	public function testAATMatchComputerArtRemoveParens() {
		// some real-world examples

		$vs_query
			       = 'People and Culture:Associated Concepts:concepts in the arts:artistic concepts:art genres:computer art';
		$o_service = $this->_createAATServiceStub( $vs_query );
		$vm_ret    = caMatchAAT( explode( ':', $vs_query ),
			180, array( 'removeParensFromLabels' => true ),
			$o_service
		);

		$this->assertEquals( 'http://vocab.getty.edu/aat/300069478', $vm_ret );
	}

	public function testAATMatchAcrylicPaintingRemoveParens() {
		// some real-world examples

		$vs_query
			       = 'Descriptors:Processes and Techniques:processes and techniques:processes and techniques by specific type:image-making processes and techniques:painting and painting techniques:painting techniques:painting techniques by medium:acrylic painting (technique)';
		$o_service = $this->_createAATServiceStub( $vs_query );
		$vm_ret    = caMatchAAT( explode( ':', $vs_query ),
			180, array( 'removeParensFromLabels' => true ),
			$o_service
		);
		$this->assertEquals( 'http://vocab.getty.edu/aat/300182574', $vm_ret );
	}

	public function testAATMatchPaintingRemoveParens() {
		// some real-world examples

		$vs_query
			       = 'Descriptors:Processes and Techniques:processes and techniques:processes and techniques by specific type:image-making processes and techniques:painting and painting techniques:painting (image-making)';
		$o_service = $this->_createAATServiceStub( $vs_query );
		$vm_ret    = caMatchAAT( explode( ':', $vs_query ),
			180, array( 'removeParensFromLabels' => true ),
			$o_service
		);

		$this->assertEquals( 'http://vocab.getty.edu/aat/300054216', $vm_ret );
	}
	# -------------------------------------------------------

	/**
	 *
	 *
	 */
	public function testCaProcessImportItemSettingsForValue() {
		$ps_value         = '7.30.pepe';
		$pa_item_settings = array(
			'applyRegularExpressions' => array(
				array(
					"match"       => '([0-9]+)\\.([0-9]+)',
					"replaceWith" => "\\1:\\2"
				),
				array(
					"match"       => "[^0-9:]+",
					"replaceWith" => ""
				)
			)
		);

		$result = caProcessImportItemSettingsForValue( $ps_value, $pa_item_settings );
		$this->assertSame( '7:30', $result );
	}

	public function testCaProcessImportItemSettingsForArrayValue() {
		$va_value         = [ '7.30.pepe', '8.30.smith' ];
		$va_item_settings = array(
			'applyRegularExpressions' => array(
				array(
					"match"       => '([0-9]+)\\.([0-9]+)',
					"replaceWith" => "\\1:\\2"
				),
				array(
					"match"       => "[^0-9:]+",
					"replaceWith" => ""
				)
			)
		);

		$result = caProcessImportItemSettingsForValue( $va_value, $va_item_settings );
		$this->assertIsArray( $result );
		$this->assertEquals( 2, sizeof( $result ) );
		$this->assertSame( [ '7:30', '8:30' ], $result );
	}

	public function testCaProcessImportItemSettingsForArrayValueWithEmptyMatch() {
		$va_value         = [ '7.30.pepe', '8.30.smith' ];
		$va_item_settings = array(
			'applyRegularExpressions' => array(
				array(
					"match"       => '',
					"replaceWith" => "\\1:\\2"
				),
				array(
					"match"       => "",
					"replaceWith" => ""
				)
			)
		);

		$result = caProcessImportItemSettingsForValue( $va_value, $va_item_settings );
		$this->assertIsArray( $result );
		$this->assertEquals( 2, sizeof( $result ) );
		$this->assertSame( $va_value, $result );
	}

	public function testCaProcessImportItemSettingsForValueWithExclamation() {
		$ps_value         = '7!30!pepe';
		$pa_item_settings = array(
			'applyRegularExpressions' => array(
				array(
					"match"       => '([0-9]+)!([0-9]+)',
					"replaceWith" => "\\1:\\2"
				),
				array(
					"match"       => "[^0-9:]+",
					"replaceWith" => ""
				)
			)
		);

		$result = caProcessImportItemSettingsForValue( $ps_value, $pa_item_settings );
		$this->assertSame( '7:30', $result );
	}

	public function testCaValidateGoogleSheetsUrlReturnsNullForBadUrl() {
		$url    = "http://collectiveaccess.org";
		$result = caValidateGoogleSheetsUrl( $url );
		$this->assertNull( $result );
	}

	public function testCaValidateGoogleSheetsUrlReturnsValidatedUrl() {
		$url    = "https://docs.google.com/file/d/";
		$result = caValidateGoogleSheetsUrl( $url );
		$this->assertSame( 'https://docs.google.com/file/d/export?format=xlsx', $result );
	}

	public function testCaProcessRefineryAttributesEmptyIsNotNull() {
		$pa_attributes  = $this->attributes;
		$pa_source_data = $this->data;
		$pa_item        = $this->item;
		$pn_c           = 0;
		$pa_options     = array();
		$result         = caProcessRefineryAttributes( $pa_attributes, $pa_source_data, $pa_item, $pn_c, $pa_options );
		$this->assertNotNull( $result );
	}

	public function testCaProcessRefineryAttributesEmptyProducesEmptyResults() {
		$pa_attributes  = array();
		$pa_source_data = array();
		$pa_item        = array();
		$pn_c           = 0;
		$pa_options     = array();
		$result         = caProcessRefineryAttributes( $pa_attributes, $pa_source_data, $pa_item, $pn_c, $pa_options );
		$this->assertNotNull( $result );
		$this->assertEquals( 0, sizeof( $result ) );
	}

	public function testCaProcessRefineryAttributesWithFileType() {
		$this->markTestIncomplete( 'testCaProcessRefineryAttributesWithFileType pending test' );
	}

	public function testCaProcessRefineryAttributesWithIndexedArray() {
		$pa_attributes  = $this->attributes;
		$pa_source_data = $this->data;
		$pa_item        = $this->item;
		$pn_c           = 0;
		$pa_options     = array();
		$result         = caProcessRefineryAttributes( $pa_attributes, $pa_source_data, $pa_item, $pn_c, $pa_options );
		$this->assertNotNull( $result );
		$this->assertEquals( $this->data[3], $result["preferred_label"][0] );
	}

	public function testCaProcessRefineryAttributesWithAssociativeArray() {
		$pa_attributes  = $this->attributes;
		$pa_source_data = $this->data;
		$pa_item        = $this->item;
		$pn_c           = 0;
		$pa_options     = array();
		$result         = caProcessRefineryAttributes( $pa_attributes, $pa_source_data, $pa_item, $pn_c, $pa_options );
		$this->assertNotNull( $result );
		$this->assertEquals( $this->data[5], $result["nonpreferred_label"]["name"] );
	}

	/****************************
	 *
	 * Refinery Parents tests
	 *
	 ****************************
	 */

	public function testCaProcessRefineryParentsPlacesHierarchy() {
		$vs_table = 'ca_places';
		$result   = $this->_runProcessRefineryParents( 'placeHierarchyBuilder', $vs_table, 'country' );
		$this->_deleteInstance( $vs_table, $result );
		$this->assertNotNull( $result );
	}

	public function testCaProcessRefineryParentsCollectionHierarchy() {
		$vs_table = 'ca_collections';
		$result   = $this->_runProcessRefineryParents( 'collectionHierarchyBuilder', $vs_table, 'internal' );
		$this->_deleteInstance( $vs_table, $result );
		$this->assertNotNull( $result );
	}

	public function testCaProcessRefineryParentsEntityHierarchy() {
		$vs_table = 'ca_entities';
		$result   = $this->_runProcessRefineryParents( 'entityHierarchyBuilder', $vs_table, 'org' );
		$this->_deleteInstance( $vs_table, $result );
		$this->assertNotNull( $result );
	}

	public function testCaProcessRefineryParentsOccurrenceHierarchy() {
		$vs_table = 'ca_occurrences';
		$result   = $this->_runProcessRefineryParents( 'occurrenceHierarchyBuilder', $vs_table, 'event' );
		$this->_deleteInstance( $vs_table, $result );
		$this->assertNotNull( $result );
	}

	public function testCaProcessRefineryParentsObjectHierarchy() {
		$vs_table = 'ca_objects';
		$result   = $this->_runProcessRefineryParents( 'objectHierarchyBuilder', $vs_table, 'software' );
		$this->_deleteInstance( $vs_table, $result );
		$this->assertNotNull( $result );
	}

	public function testCaProcessRefineryParentsStorageLocationHierarchy() {
		$vs_table = 'ca_storage_locations';
		$result   = $this->_runProcessRefineryParents( 'storageLocationHierarchyBuilder', $vs_table, 'drawer' );
		$this->_deleteInstance( $vs_table, $result );
		$this->assertNotNull( $result );
	}

	/****************************
	 *
	 * Generic Splitter tests
	 *
	 ****************************
	 */

	/**
	 *
	 */
	public function testCaGenericImportSplitterEntity() {
		$vs_refinery_name                                         = 'entitySplitter';
		$vs_type                                                  = 'org';
		$this->groups['destination']                              = 'ca_objects.unitdate.date_value';
		$this->item['settings'][ $vs_refinery_name . '_parents' ] = array(
			array(
				'name' => '^1',
				'type' => $vs_type
			)
		);
		$vs_table                                                 = 'ca_entities';
		$result                                                   = $this->_runGenericImportSplitter( $vs_refinery_name,
			$vs_table, $vs_type );

		// Delete entity
		$this->_deleteInstance( $vs_table, $result[0]['date_value'] );

		$this->assertNotNull( $result );
	}

	public function testCaGenericImportSplitterEntitySkifIfValueMatches() {
		$vs_refinery_name                                             = 'entitySplitter';
		$vs_type                                                      = 'org';
		$this->groups['destination']                                  = 'ca_objects.unitdate.date_value';
		$this->item['settings'][ $vs_refinery_name . '_parents' ]     = array(
			array(
				'name' => '^1',
				'type' => $vs_type
			)
		);
		$this->item['settings'][ $vs_refinery_name . '_skipIfValue' ] = [ 'Verdun' ];
		$result
		                                                              = $this->_runGenericImportSplitter( $vs_refinery_name,
			'ca_entities', $vs_type );

		$this->assertNotNull( $result );
		$this->assertSame( 0, sizeof( $result ) );
	}

	public function testCaGenericImportSplitterEntityUseHierarchy() {
		$vs_refinery_name                                           = 'entitySplitter';
		$vs_type                                                    = 'org';
		$this->groups['destination']                                = 'ca_objects.unitdate.date_value';
		$this->item['settings'][ $vs_refinery_name . '_parents' ]   = array(
			array(
				'name' => '^1',
				'type' => $vs_type
			)
		);
		$this->item['settings'][ $vs_refinery_name . '_hierarchy' ] = $this->parents;
		$vs_table                                                   = 'ca_entities';
		$result
		                                                            = $this->_runGenericImportSplitter( $vs_refinery_name,
			$vs_table, $vs_type );

		// Delete entity
		$this->_deleteInstance( $vs_table, $result[0]['date_value'] );

		$this->assertNotNull( $result );
	}

	/**
	 * @param string $ps_table
	 * @param        $pn_id
	 */
	protected function _deleteInstance( string $ps_table, $pn_id ): void {
		$t_instance = Datamodel::getInstance( $ps_table );
		if ( $t_instance->load( $pn_id ) ) {
			$t_instance->setMode( ACCESS_WRITE );
			$t_instance->delete( true, array( 'hard' => true ) );
		}
	}

}
