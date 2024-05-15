<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/BaseGettyLODServicePlugin.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2023 Whirl-i-Gig
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
require_once( __CA_LIB_DIR__ . "/Plugins/IWLPlugInformationService.php" );
require_once( __CA_LIB_DIR__ . "/Plugins/InformationService/BaseInformationServicePlugin.php" );

abstract class BaseGettyLODServicePlugin extends BaseInformationServicePlugin {
	# ------------------------------------------------
	protected $opo_linked_data_conf = null;

	# ------------------------------------------------
	public function __construct() {
		parent::__construct(); // sets app.conf

		$this->opo_linked_data_conf = Configuration::load( $this->opo_config->get( 'linked_data_config' ) );
	}

	# ------------------------------------------------
	abstract protected function getConfigName();
	# ------------------------------------------------

	/**
	 * Perform lookup on AAT-based data service
	 *
	 * @param array  $pa_settings Plugin settings values
	 * @param string $ps_search   The expression with which to query the remote data service
	 * @param array  $pa_options  Lookup options
	 *                            phrase => send a lucene phrase search instead of keywords
	 *                            raw => return raw, unprocessed results from getty service
	 *                            short = return short label (term only) [Default is false]
	 *                            start =>
	 *                            limit =>
	 *
	 * @return array
	 */
	public function lookup( $pa_settings, $ps_search, $pa_options = null ) {
		if ( ! is_array( $pa_options ) ) {
			$pa_options = array();
		}

		$va_service_conf = $this->opo_linked_data_conf->get( $this->getConfigName() );
		$va_params = $this->_getParams( $pa_options, $va_service_conf );
		if ($pa_settings) {
			$pa_options['settings'] = $pa_settings;
		}
		$vs_search = $this->_rewriteSearch($ps_search, $pa_options, $va_params);
		$vs_query = $this->_buildQuery($vs_search, $pa_options, $va_params);

		$curl_options[CURLOPT_CONNECTTIMEOUT] =  caGetOption( 'curlopt_connecttimeout', $va_service_conf, 2 );
		$va_results = self::queryGetty( $vs_query, $curl_options );

		$va_clean_results = $this->_cleanResults($va_results, $pa_options, $va_params);
		return $va_clean_results;
	}

	/**
	 * Perform lookup on Getty linked open data service
	 *
	 * @param string $ps_query   The sparql query
	 * @param        $pa_options Options array
	 *
	 * @return array The decoded JSON result
	 */
	public static function queryGetty( $ps_query, $pa_options = null ) {
		$o_curl = curl_init();
		if ( ! isset( $pa_options ) || ! is_array( $pa_options ) ) {
			$pa_options = [];
		}

		// Set default values for options
		$pa_options[ CURLOPT_URL ]            = caGetOption( CURLOPT_URL, $pa_options,
			"http://vocab.getty.edu/sparql.json?query={$ps_query}" );
		$pa_options[ CURLOPT_CONNECTTIMEOUT ] = caGetOption( CURLOPT_CONNECTTIMEOUT, $pa_options, 2 );
		$pa_options[ CURLOPT_RETURNTRANSFER ] = caGetOption( CURLOPT_RETURNTRANSFER, $pa_options, 1 );
		$pa_options[ CURLOPT_USERAGENT ]      = caGetOption( CURLOPT_USERAGENT, $pa_options,
			'CollectiveAccess web service lookup' );

		foreach ( $pa_options as $pa_key => $pa_value ) {
			curl_setopt( $o_curl, $pa_key, $pa_value );
		}
		$vs_result = curl_exec( $o_curl );
		curl_close( $o_curl );

		if ( ! $vs_result ) {
			return null;
		}

		$va_result = json_decode( $vs_result, true );
		if ( ! isset( $va_result['results']['bindings'] ) || ! is_array( $va_result['results']['bindings'] ) ) {
			return null;
		}

		return $va_result['results']['bindings'];
	}
	# ------------------------------------------------

	/**
	 * Fetch details about a specific item from getty data service
	 *
	 * @param array  $pa_settings Plugin settings values
	 * @param string $ps_url      The URL originally returned by the data service uniquely identifying the item
	 *
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation( $pa_settings, $ps_url ) {
		$va_service_conf = $this->opo_linked_data_conf->get( $this->getConfigName() );
		if ( ! $va_service_conf || ! is_array( $va_service_conf ) ) {
			return array( 'display' => '' );
		}
		if ( ! isset( $va_service_conf['detail_view_info'] ) || ! is_array( $va_service_conf['detail_view_info'] ) ) {
			return array( 'display' => '' );
		}

		$vs_display_url = self::getLiteralFromRDFNode( $ps_url, '<http://www.w3.org/2000/01/rdf-schema#seeAlso>' );
		if ( ! $vs_display_url ) {
			$vs_display_url = $ps_url;
		}
		$vs_display = '<div style="margin-top:10px; margin-bottom: 10px;"><a target="_blank" href="' . $vs_display_url
		              . '">' . $vs_display_url . '</a></div>';
		foreach ( $va_service_conf['detail_view_info'] as $va_node ) {
			if ( ! isset( $va_node['literal'] ) ) {
				continue;
			}

			$vs_uri_for_pull = isset( $va_node['uri'] ) ? $va_node['uri'] : null;

			// only display if there's content
			if ( strlen( $vs_content = self::getLiteralFromRDFNode( $ps_url, $va_node['literal'], $vs_uri_for_pull,
					$va_node ) ) > 0
			) {
				$vs_display .= "<div class='formLabel'>";
				$vs_display .= isset( $va_node['label'] ) ? $va_node['label'] . ": " : "";
				$vs_display .= "<span class='formLabelPlain'>" . $vs_content . "</span>";
				$vs_display .= "</div>\n";
			}
		}

		return array( 'display' => $vs_display );
	}

	/**
	 * Get extra values for search indexing. This is called once when the attribute is saved.
	 *
	 * @param array  $pa_settings
	 * @param string $ps_url
	 *
	 * @return array
	 */
	public function getDataForSearchIndexing( $pa_settings, $ps_url ) {
		$va_service_conf = $this->opo_linked_data_conf->get( $this->getConfigName() );
		if ( ! $va_service_conf || ! is_array( $va_service_conf ) ) {
			return array();
		}
		if ( ! isset( $va_service_conf['additional_indexing_info'] )
		     || ! is_array( $va_service_conf['additional_indexing_info'] )
		) {
			return array();
		}

		$va_return = array();
		foreach ( $va_service_conf['additional_indexing_info'] as $vs_key => $va_node ) {
			if ( ! isset( $va_node['literal'] ) ) {
				continue;
			}

			$vs_uri_for_pull      = isset( $va_node['uri'] ) ? $va_node['uri'] : null;
			$va_return[ $vs_key ] = str_replace( '; ', ' ',
				self::getLiteralFromRDFNode( $ps_url, $va_node['literal'], $vs_uri_for_pull, $va_node ) );
		}

		return $va_return;
	}
	# ------------------------------------------------

	/**
	 * Pull extra info that is available via get() and in display templates
	 *
	 * @param array  $pa_settings
	 * @param string $ps_url
	 *
	 * @return array
	 */
	public function getExtraInfo( $pa_settings, $ps_url ) {
		$va_service_conf = $this->opo_linked_data_conf->get( $this->getConfigName() );
		if ( ! $va_service_conf || ! is_array( $va_service_conf ) ) {
			return array();
		}
		if ( ! isset( $va_service_conf['extra_info'] ) || ! is_array( $va_service_conf['extra_info'] ) ) {
			return array();
		}

		$va_return = array();
		foreach ( $va_service_conf['extra_info'] as $vs_key => $va_node ) {
			if ( ! isset( $va_node['literal'] ) ) {
				continue;
			}

			$vs_uri_for_pull      = isset( $va_node['uri'] ) ? $va_node['uri'] : null;
			$va_return[ $vs_key ] = str_replace( '; ', ' ',
				self::getLiteralFromRDFNode( $ps_url, $va_node['literal'], $vs_uri_for_pull, $va_node ) );
		}

		return $va_return;
	}
	# ------------------------------------------------
	// HELPERS
	# ------------------------------------------------
	/**
	 * Fetches a literal property string value from given node
	 *
	 * @param string      $ps_base_node
	 * @param string      $ps_literal_propery EasyRdf property definition
	 * @param string|null $ps_node_uri        Optional related node URI to pull from
	 * @param array       $pa_options         Available options are
	 *                                        limit -> limit number of processed related notes, defaults to 10
	 *                                        stripAfterLastComma -> strip everything after (and including) the last comma in the individual literal string
	 *                                        this is useful for gvp:parentString where the top-most category is usually not very useful
	 *
	 * @return string|bool
	 */
	static function getLiteralFromRDFNode(
		$ps_base_node, $ps_literal_propery, $ps_node_uri = null, $pa_options = array()
	) {
		if ( ! isURL( $ps_base_node ) ) {
			return false;
		}
		if ( ! is_array( $pa_options ) ) {
			$pa_options = array();
		}

		$vs_cache_key = md5( serialize( func_get_args() ) );
		if ( CompositeCache::contains( $vs_cache_key, 'GettyRDFLiterals' ) ) {
			return CompositeCache::fetch( $vs_cache_key, 'GettyRDFLiterals' );
		}

		$pn_limit                  = (int) caGetOption( 'limit', $pa_options, 10 );
		$pb_strip_after_last_comma = (bool) caGetOption( 'stripAfterLastComma', $pa_options, false );
		$pb_invert                 = (bool) caGetOption( 'invert', $pa_options, false );
		$pb_recursive              = (bool) caGetOption( 'recursive', $pa_options, false );

		if ( ! ( $o_graph = self::getURIAsRDFGraph( $ps_base_node ) ) ) {
			return false;
		}

		// if we're pulling from a related node, add those graphs (technically nodes) to the list (we pull from all of them)
		if ( strlen( $ps_node_uri ) > 0 ) {
			$va_pull_graphs = self::getListOfRelatedGraphs( $o_graph, $ps_base_node, $ps_node_uri, $pn_limit,
				$pb_recursive );
		} else {
			// the only graph/node we pull from is the base node
			$va_pull_graphs = array( $ps_base_node => $o_graph );
		}

		if ( ! is_array( $va_pull_graphs ) ) {
			return false;
		}

		$va_return = array();

		$vn_j = 0;
		foreach ( $va_pull_graphs as $vs_uri => $o_g ) {
			$va_literals = $o_g->all( $vs_uri, $ps_literal_propery );

			foreach ( $va_literals as $o_literal ) {
				if ( $o_literal instanceof EasyRdf_Literal ) {
					$vs_string_to_add = htmlentities( preg_replace( '/[\<\>]/', '', $o_literal->getValue() ) );
				} else {
					$vs_string_to_add = preg_replace( '/[\<\>]/', '', (string) $o_literal );
				}

				if ( $pb_strip_after_last_comma ) {
					$vn_last_comma_pos = strrpos( $vs_string_to_add, ',' );
					$vs_string_to_add  = substr( $vs_string_to_add, 0,
						( $vn_last_comma_pos - strlen( $vs_string_to_add ) ) );
				}

				$va_tmp = explode( ', ', $vs_string_to_add );
				if ( $pb_invert && sizeof( $va_tmp ) ) {
					$vs_string_to_add = join( ' &gt; ', array_reverse( $va_tmp ) );
				}

				// make links click-able
				if ( isURL( $vs_string_to_add ) ) {
					$vs_string_to_add = "<a href='{$vs_string_to_add}' target='_blank'>{$vs_string_to_add}</a>";
				}

				$va_return[] = $vs_string_to_add;
				if ( ( ++ $vn_j ) >= $pn_limit ) {
					break;
				}
			}
		}

		$vs_return = join( '; ', $va_return );
		CompositeCache::save( $vs_cache_key, $vs_return, 'GettyRDFLiterals', 60 * 60 * 24 * 7 );

		return $vs_return;
	}
	# ------------------------------------------------

	/**
	 * Try to load a given URI as RDF Graph
	 *
	 * @param string $ps_uri
	 *
	 * @return bool|EasyRdf_Graph
	 */
	static function getURIAsRDFGraph( $ps_uri ) {
		if ( ! $ps_uri ) {
			return false;
		}

		if ( CompositeCache::contains( $ps_uri, 'GettyLinkedDataRDFGraphs' ) ) {
			return CompositeCache::fetch( $ps_uri, 'GettyLinkedDataRDFGraphs' );
		}

		try {
			$o_graph = new EasyRdf_Graph( "http://vocab.getty.edu/download/rdf?uri={$ps_uri}.rdf" );
			$o_graph->load([]);
		} catch ( Exception $e ) {
			return false;
		}

		CompositeCache::save( $ps_uri, $o_graph, 'GettyLinkedDataRDFGraphs' );

		return $o_graph;
	}
	# ------------------------------------------------

	/**
	 * @param EasyRdf_Graph $po_graph
	 * @param string        $ps_base_node
	 * @param string        $ps_node_uri
	 * @param int           $pn_limit
	 * @param bool          $pb_recursive
	 *
	 * @return array
	 */
	static function getListOfRelatedGraphs( $po_graph, $ps_base_node, $ps_node_uri, $pn_limit, $pb_recursive = false ) {
		$vs_cache_key = md5( serialize( func_get_args() ) );

		if ( CompositeCache::contains( $vs_cache_key, 'GettyLinkedDataRelatedGraphs' ) ) {
			return CompositeCache::fetch( $vs_cache_key, 'GettyLinkedDataRelatedGraphs' );
		}

		$va_related_nodes = $po_graph->all( $ps_base_node, $ps_node_uri );
		$va_pull_graphs   = array();

		if ( is_array( $va_related_nodes ) ) {
			$vn_i = 0;
			foreach ( $va_related_nodes as $o_related_node ) {
				$vs_pull_uri = (string) $o_related_node;
				if ( ! ( $o_pull_graph = self::getURIAsRDFGraph( $vs_pull_uri ) ) ) {
					return false;
				}
				$va_pull_graphs[ $vs_pull_uri ] = $o_pull_graph;

				if ( ( ++ $vn_i ) >= $pn_limit ) {
					break;
				}
			}
		}

		if ( $pb_recursive ) {
			$va_sub_pull_graphs = array();
			foreach ( $va_pull_graphs as $vs_pull_uri => $o_pull_graph ) {
				// avoid circular references
				if ( isset( $va_pull_graphs[ $vs_pull_uri ] ) || isset( $va_sub_pull_graphs[ $vs_pull_uri ] ) ) {
					continue;
				}
				$va_sub_pull_graphs = array_merge( $va_sub_pull_graphs,
					self::getListOfRelatedGraphs( $o_pull_graph, $vs_pull_uri, $ps_node_uri, $pn_limit, true ) );
			}

			$va_pull_graphs = array_merge( $va_pull_graphs, $va_sub_pull_graphs );
		}

		CompositeCache::save( $vs_cache_key, $va_pull_graphs, 'GettyLinkedDataRelatedGraphs' );

		return $va_pull_graphs;
	}

	# ------------------------------------------------

	public function _getParams( $pa_options, $pa_service_conf = null ) {
		$result               = [];
		$result['serviceUrl'] = self::_getServiceUrl();
		if ( ! isset( $pa_service_conf ) ) {
			$pa_service_conf = $this->opo_linked_data_conf->get( $this->getConfigName() );
		}

		$result['search_field'] = ( isset( $pa_service_conf['search_text'] ) && $pa_service_conf['search_text'] )
			? 'luc:text'
			: 'luc:term';

		$result['start'] = (int) caGetOption('start', $pa_options, 0);
		$result['isPhrase'] = (bool) caGetOption( 'phrase', $pa_options, false );

		$result['isRaw']    = (bool) caGetOption( 'raw', $pa_options, false );
		$result['limit']    = (int) caGetOption( 'limit', $pa_options,
			( $pa_service_conf['result_limit'] ) ? $pa_service_conf['result_limit'] : 50 );

		return $result;
	}

	protected function _getServiceUrl() {
		return 'http://vocab.getty.edu/' . $this->getConfigName();
	}

	/**
	 * Clean results
	 *
	 * @param $pa_results
	 * @param $pa_options
	 * @param $pa_params
	 *
	 * @return array|bool
	 */
	public function _cleanResults( $pa_results, $pa_options, $pa_params ) {
		return $pa_results;
	}

	/**
	 * Build query string.
	 *
	 * @param $ps_search
	 * @param $pa_options
	 * @param $pa_params
	 *
	 * @return
	 */
	public function _buildQuery( $ps_search, $pa_options, $pa_params ) {
		return $ps_search;
	}

	/**
	 * Rewrite search string.
	 *
	 * @param      $ps_search
	 * @param null $pa_options
	 * @param null $params
	 *
	 * @return mixed
	 */
	public function _rewriteSearch($ps_search, $pa_options=null, $params=null){
		/**
		 * Contrary to what the Getty documentation says the terms seem to get combined by OR, not AND, so if you pass
		 * "Coney Island" you get all kinds of Islands, just not the one you're looking for. It's in there somewhere but
		 * the order field might prevent it from showing up within the limit. So we do our own little piece of "query rewriting" here.
		 */
		if ( is_numeric( $ps_search ) ) {
			$vs_search = $ps_search;
		} elseif ( isURL( $ps_search ) ) {
			$vs_search = str_replace( caGetOption('serviceUrl', $params, null), '', $ps_search );
		} elseif ( caGetOption('isPhrase', $params, false) ) {
			$vs_search = '\"' . $ps_search . '\"';
		} else {
			$va_search = preg_split( '/[\s]+/', $ps_search );
			$vs_search = join( ' AND ', $va_search );
		}

		return $vs_search;
	}
	# ------------------------------------------------
	/** 
	 * Add id field
	 *
	 * @param array $pa_settings element settings
	 * @return array
	 */
	public function getAdditionalFields(array $pa_element_info) : array {
		$id = '{fieldNamePrefix}'.$pa_element_info['element_id'].'_id_{n}';
		$flds = [['name' => 'id', 'id' => $id, 'html' => caHTMLHiddenInput(
				$id, 
				['value' => '{{id}}'],
				['width' => '150px']
        )]];
        $id = '{fieldNamePrefix}'.$pa_element_info['element_id'].'_uri_{n}';
		$flds[] = ['name' => 'uri', 'id' => $id, 'html' => caHTMLHiddenInput(
				$id, 
				['value' => '{{uri}}'],
				['width' => '150px']
        )];
        return $flds;
	}
	# ------------------------------------------------
	/** 
	 * Return ids
	 *
	 * @param array $pa_settings element settings
	 * @return array
	 */
	public function getAdditionalFieldValues($attribute_value) : array {
		$uri =  $attribute_value->getUri();
		if(preg_match('!([\d]+)$!', $uri, $m)) {
			return ['id' => $m[1], 'uri' => $uri];
		}
		return ['id' => '', 'uri' => ''];
	}
	# ------------------------------------------------
}
