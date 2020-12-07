<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/GlobalNames.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 * @subpackage InformationService
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */


require_once( __CA_LIB_DIR__ . "/Plugins/IWLPlugInformationService.php" );
require_once( __CA_LIB_DIR__ . "/Plugins/InformationService/BaseInformationServicePlugin.php" );

global $g_information_service_settings_global_names;
$g_information_service_settings_global_names = array(
	'source' => array(
		'formatType'  => FT_NUMBER,
		'displayType' => DT_SELECT,
		'default'     => '',
		'width'       => 90,
		'height'      => 1,
		'label'       => _t( 'Source' ),
		'description' => _t( 'Data source.' ),
		'options'     => [
			"All sources"                                                             => 0,
			"Catalogue of Life"                                                       => 1,
			"Encyclopedia of Life"                                                    => 12,
			"GBIF Backbone Taxonomy"                                                  => 11,
			"ITIS"                                                                    => 3,
			"NCBI"                                                                    => 4,
			"The Paleobiology Database"                                               => 172,
			"Wikispecies"                                                             => 2,
			"Index Fungorum"                                                          => 5,
			"GRIN Taxonomy for Plants"                                                => 6,
			"Union 4"                                                                 => 7,
			"The Interim Register of Marine and Nonmarine Genera"                     => 8,
			"World Register of Marine Species"                                        => 9,
			"Freebase"                                                                => 10,
			"Passiflora vernacular names"                                             => 93,
			"Inventory of Fish Species in the Wami River Basin"                       => 94,
			"Pheasant Diversity and Conservation in the Mt. Gaoligonshan Region"      => 95,
			"Finding Species"                                                         => 96,
			"Birds of Lindi Forests Plantation"                                       => 97,
			"Nemertea"                                                                => 98,
			"Kihansi Gorge Amphibian Species Checklist"                               => 99,
			"Mushroom Observer"                                                       => 100,
			"TaxonConcept"                                                            => 101,
			"Amphibia and Reptilia of Yunnan"                                         => 102,
			"Common names of Chilean Plants"                                          => 103,
			"Invasive Species of Belgium"                                             => 104,
			"ZooKeys"                                                                 => 105,
			"COA Wildlife Conservation List"                                          => 106,
			"AskNature"                                                               => 107,
			"China: Yunnan, Southern Gaoligongshan, Rapid Biological Inventories"     => 108,
			"Native Orchids from Gaoligongshan Mountains, China"                      => 109,
			"Illinois Wildflowers"                                                    => 110,
			"Coleorrhyncha Species File"                                              => 112,
			"zoological names.zip"                                                    => 113,
			"Peces de la zona hidrogeográfica de la Amazonia, Colombia (Spreadsheet)" => 114,
			"Eastern Mediterranean Syllidae"                                          => 115,
			"Gaoligong Shan Medicinal Plants Checklist"                               => 116,
			"birds_of_tanzania"                                                       => 117,
			"AmphibiaWeb"                                                             => 118,
			"tanzania_plant_sepecimens"                                               => 119,
			"Papahanaumokuakea Marine National Monument"                              => 120,
			"Taiwanese IUCN species list"                                             => 121,
			"BioPedia"                                                                => 122,
			"AnAge"                                                                   => 123,
			"Embioptera Species File"                                                 => 124,
			"Global Invasive Species Database"                                        => 125,
			"Sendoya S., Fernández F. AAT de hormigas (Hymenoptera: Formicidae)"      => 126,
			"Flora of Gaoligong Mountains"                                            => 127,
			"ARKive"                                                                  => 128,
			"True Fruit Flies (Diptera, Tephritidae) of the Afrotropical Region"      => 129,
			"3i - Typhlocybinae Database"                                             => 130,
			"CATE Sphingidae"                                                         => 131,
			"ZooBank"                                                                 => 132,
			"Diatoms"                                                                 => 133,
			"AntWeb"                                                                  => 134,
			"Endemic species in Taiwan"                                               => 135,
			"Dermaptera Species File"                                                 => 136,
			"Mantodea Species File"                                                   => 137,
			"Birds of the World: Recommended English Names"                           => 138,
			"New Zealand Animalia"                                                    => 139,
			"Blattodea Species File"                                                  => 140,
			"Plecoptera Species File"                                                 => 141,
			"/home/dimus/files/dwca/clemens.zip"                                      => 142,
			"Coreoidea Species File"                                                  => 143,
			"Freshwater Animal Diversity Assessment - Normalized export"              => 144,
			"Catalogue of Vascular Plant Species of Central and Northeastern Brazil"  => 145,
			"Wikipedia in EOL"                                                        => 146,
			"Database of Vascular Plants of Canada (VASCAN)"                          => 147,
			"Phasmida Species File"                                                   => 148,
			"OBIS"                                                                    => 149,
			"USDA NRCS PLANTS Database"                                               => 150,
			"Catalog of Fishes"                                                       => 151,
			"Aphid Species File"                                                      => 152,
			"The National Checklist of Taiwan"                                        => 153,
			"Psocodea Species File"                                                   => 154,
			"FishBase"                                                                => 155,
			"3i - Typhlocybinae Database"                                             => 156,
			"Belgian Species List"                                                    => 157,
			"EUNIS"                                                                   => 158,
			"CU*STAR"                                                                 => 159,
			"Orthoptera Species File"                                                 => 161,
			"Bishop Museum"                                                           => 162,
			"IUCN Red List of Threatened Species"                                     => 163,
			"BioLib.cz"                                                               => 164,
			"Tropicos - Missouri Botanical Garden"                                    => 165,
			"nlbif"                                                                   => 166,
			"The International Plant Names Index"                                     => 167,
			"Index to Organism Names"                                                 => 168,
			"uBio NameBank"                                                           => 169,
			"Arctos"                                                                  => 170,
			"Checklist of Beetles (Coleoptera) of Canada and Alaska. Second Edition." => 171,
			"The Reptile Database"                                                    => 173,
			"The Mammal Species of The World"                                         => 174,
			"BirdLife International"                                                  => 175,
			"Checklist da Flora de Portugal (Continental, Açores e Madeira)"          => 176,
			"FishBase Cache"                                                          => 177,
			"Silva"                                                                   => 178,
			"Open Tree of Life Reference Taxonomy"                                    => 179,
			"iNaturalist"                                                             => 180,
			"The Interim Register of Marine and Nonmarine Genera"                     => 181,
			"Gymno"                                                                   => 182,
			"Index Animalium"                                                         => 183,
			"ASM Mammal Diversity Database"                                           => 184,
			"IOC World Bird List"                                                     => 185,
			"MCZbase"                                                                 => 186,
			"The eBird/Clements Checklist of Birds of the World"                      => 187,
			"American Ornithological Society"                                         => 188,
			"Howard and Moore Complete Checklist of the Birds of the World"           => 189,
			"Myriatrix"                                                               => 193
		]
	)
);

class WLPlugInformationServiceGlobalNames extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;

	private $display_fields;
	# ------------------------------------------------

	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_global_names;

		WLPlugInformationServiceGlobalNames::$s_settings = $g_information_service_settings_global_names;
		parent::__construct();
		$this->info['NAME'] = 'GlobalNames';

		$this->description = _t( 'Provides access to the GlobalNames taxonomic resolver.' );


		$this->display_fields = [
			'data_source_title'         => _t( 'Source' ),
			'data_source_id'            => _t( 'Source ID' ),
			'gni_uuid'                  => _t( 'GNU UUID' ),
			'name_string'               => _t( 'Name' ),
			'canonical_form'            => _t( 'Canonical form' ),
			'classification_path'       => _t( 'Classification path' ),
			'classification_path_ranks' => _t( 'Classification path ranks' )
		];

	}
	# ------------------------------------------------

	/**
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceGlobalNames::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/**
	 * Perform lookup on GlobalNames-based data service
	 *
	 * @param array  $settings Plugin settings values
	 * @param string $search   The expression with which to query the remote data service
	 * @param array  $options  Lookup options:
	 *                         count = Maximum number of records to return [Default is 30]
	 *
	 * @return array
	 */
	public function lookup( $settings, $search, $options = null ) {
		$search = urlencode( $search );

		$source_id = caGetOption( 'source', $settings, null );

		$maxcount = caGetOption( 'count', $options, 30 );
		$count    = 0;
		$items    = [];
		while ( $count <= $maxcount ) {
			$data_json
				= caQueryExternalWebservice( "http://resolver.globalnames.org/name_resolvers.json?names={$search}&data_source_ids={$source_id}&resolve_once=true&with_canonical_ranks=true" );

			if ( $data = json_decode( $data_json, true ) ) {
				if ( is_array( $data['data'] ) && ( sizeof( $data['data'] ) > 0 ) ) {
					foreach ( $data['data'] as $block ) {
						if ( ! is_array( $block['results'] ) ) {
							continue;
						}
						foreach ( $block['results'] as $entry ) {
							$items[ (string) $entry['canonical_form'] ] = [
								'label' => (string) $entry['canonical_form'],
								'idno'  => preg_replace( "![^A-Za-z0-9\-\_]+!", "_",
										(string) $entry['data_source_title'] ) . ':' . (string) $entry['gni_uuid'],
								'url'   => "http://resolver.globalnames.org/name_resolvers.json?names="
								           . urlencode( $entry['canonical_form'] ) . "&data_source_ids="
								           . (string) $entry['data_source_id']
								           . "&resolve_once=true&best_match_only=true&with_canonical_ranks=true"
							];
							$count ++;
						}
					}
				}
			}
			break;
		}
		ksort( $items );

		return [ 'results' => array_values( $items ) ];
	}
	# ------------------------------------------------

	/**
	 * Fetch details about a specific item from a GeoNames-based data service
	 *
	 * @param array  $settings Plugin settings values
	 * @param string $url      The URL originally returned by the data service uniquely identifying the item
	 *
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation( $settings, $url ) {
		$data_json = caQueryExternalWebservice( $url );

		$match = null;
		if ( $data = json_decode( $data_json, true ) ) {
			if ( is_array( $data['data'] ) && ( sizeof( $data['data'] ) > 0 ) ) {
				foreach ( $data['data'] as $block ) {
					if ( ! is_array( $block['results'] ) ) {
						continue;
					}
					foreach ( $block['results'] as $entry ) {
						$match = $entry;
					}
					break;
				}
			}
		}
		if ( is_array( $match ) ) {
			$display_data = [];
			foreach ( $this->display_fields as $k => $label ) {
				if ( sizeof( $bits = array_filter( explode( '|', $v = $match[ $k ] ), 'strlen' ) ) > 1 ) {
					$v = join( ' ➜ ', $bits );
				}
				$display_data[] = "<strong>{$label}</strong>: {$v}";
			}
			$display = join( "<br/>\n", $display_data );
		} else {
			$display = _t( 'Could not locate taxon' );
		}

		return [ 'display' => $display ];
	}
	# ------------------------------------------------
}
