<?php
/**
 * ----------------------------------------------------------------------
 * WLPlugSearchEngineSqlSearchTest.php
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
 * @package    CollectiveAccess
 * @subpackage tests/indexer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 *
 */

require_once(__CA_LIB_DIR__ . '/Search/SearchIndexer.php');
require_once(__CA_LIB_DIR__ . '/Plugins/SearchEngine/SqlSearch.php');
require_once(__CA_BASE_DIR__ . '/tests/testsWithData/BaseTestWithData.php');

class WLPlugSearchEngineSqlSearchTest extends BaseTestWithData {

    private $opn_object_id;

    protected function setUp(): void {
        // don't forget to call parent so that request is set up correctly
        parent::setUp();

        $this->assertGreaterThan(0, $this->opn_object_id = $this->addTestRecord('ca_objects', array(
                'intrinsic_fields' => array(
                        'type_id' => 'image',
                        'idno' => 'TEST & STUFF',
                        'acquisition_type_id' => 'gift'
                ),
                'preferred_labels' => array(
                        array(
                                "locale" => "en_US",
                                "name" => "Sound & Motion",
                        ),
                        array(
                                "locale" => "es_ES",
                                "name" => "Sonido y movimiento",
                        ),
                ),
                'nonpreferred_labels' => array(
                        array(
                                "locale" => "en_US",
                                "name" => "Radio is the technology of signaling and communicating using radio waves.",
                        ),
                        array(
                                "locale" => "es_ES",
                                "name" => "La radiocomunicación o radio es una forma de telecomunicación que se realiza a través de ondas de radio u ondas hertzianas..",
                        ),
                ),
                'attributes' => array(
                    // simple text
                        'internal_notes' => array(
                                array(
                                        'internal_notes' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque ullamcorper sapien nec velit porta luctus.'
                                )
                        ),

                    // text in a container
                        'external_link' => array(
                                array(
                                        'url_source' => 'My URL source'
                                )
                        ),

                    // Length
                        'dimensions' => array(
                                array(
                                        'dimensions_length' => '10 in',
                                        'dimensions_weight' => '2 lbs'
                                )
                        ),

                    // Integer
                        'integer_test' => array(
                                array(
                                        'integer_test' => 23,
                                ),
                                array(
                                        'integer_test' => 1984,
                                )
                        ),

                    // Currency
                        'currency_test' => array(
                                array(
                                        'currency_test' => '$100',
                                ),
                        ),

                    // DateRange
                        'date' => array(
                                array(
                                        'dates_value' => '6/1954',
                                        'dc_dates_types' => 'created',
                                        'locale_id' => ca_locales::codeToID('en_US'),
                                        'locale' => 'en_US',
                                ),
                        ),

                    // coverageNotes
                        'coverageNotes' => array(
                                array(
                                        'coverageNotes' => ''
                                ),
                        ),
                )
        )));

    }

    public function testGetWordID() {
        $o = new ca_objects();
        $o->load($this->opn_object_id);
        $o_si = new SearchIndexer(null, 'SqlSearch');

        $vn_table_num = $o->tableNum();
        $vn_id = $o->get($o->getPrimaryKey());
        $va_field_values = $o->getFieldValuesArray(true);
        $o_si->indexRow($vn_table_num, $vn_id, $va_field_values);

        // Check words are properly indexed on sql tables.
        $vo_sql_search = new WLPlugSearchEngineSqlSearch();
        $vn_word_id_en = $vo_sql_search->getWordID('radio', ca_locales::codeToID('en_US'));
        $vn_word_id_es = $vo_sql_search->getWordID('radio', ca_locales::codeToID('es_ES'));
        $this->assertGreaterThan(0, $vn_word_id_en);
        $this->assertGreaterThan(0, $vn_word_id_es);
    }

    public function testIndexField() {

    }

    public function testStartRowIndexing() {

    }

    public function testFlushContentBuffer() {

    }

    public function testCommitRowIndexing() {

    }

    public function testTruncateIndex() {

    }

    public function testRemoveRowIndexing() {

    }

    public function testUpdateIndexingInPlace() {

    }
}
