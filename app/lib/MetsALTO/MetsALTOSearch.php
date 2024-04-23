<?php
/** ---------------------------------------------------------------------
 * app/lib/MetsALTO/MetsALTOSearch.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */ 

class MetsALTOSearch {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $client;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$o_search = caGetSearchConfig();
		if(!($core = $o_search->get('mets_alto_solr_core'))) {
			throw new ApplicationException(_t('No core set for mets_alto_solr_core in search.conf'));
		}
		
		try {
			$this->client = MetsALTOSearch::getSolrClient($core, [
				'host' => $o_search->get('mets_alto_solr_host'), 
				'port' => $o_search->get('mets_alto_solr_port'),
				'username' => defined('__CA_METSALTO_SOLR_USER__') ? __CA_METSALTO_SOLR_USER__ : $o_search->get('mets_alto_solr_user'),
				'password' => defined('__CA_METSALTO_SOLR_PASSWORD__') ? __CA_METSALTO_SOLR_PASSWORD__ : $o_search->get('mets_alto_solr_password'),
			]);
		} catch(Exception $e) {
			throw new ApplicationException(_t('Could not connect to SOLR: %1', $e->getMessage()));
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function addPage($object, $rep, $page_num, $page_content) {
		// get an update query instance
		$update = $this->client->createUpdate();
		
		$object_id = $object->getPrimaryKey();
		$object_identifier = $object->get('ca_objects.idno');
		$rep_id = $rep->getPrimaryKey();
		
		// create a new document for the data
		$doc = $update->createDocument();
		$doc->id = $object_id.'::'.$page_num;
		$doc->name = $object_identifier.'::'.$page_num;
		$doc->object_id = $object_id;
		$doc->object_identifier = $object_identifier;
		$doc->representation_id = $rep_id;
		$doc->page = $page_num;
		$doc->content = $page_content;
		
		// add the documents and a commit command to the update query
		$update->addDocuments([$doc]);
		$update->addCommit();
		
		// this executes the query and returns the result
		$result = $this->client->update($update);
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function search($search) {
		$query = $this->client->createSelect();
		
		$query->setQuery($search);
		$query->setStart(0)->setRows(1000);
		$query->setFields(['id', 'name', 'page', 'content']);
		
		$query->addSort('page', $query::SORT_ASC);
		$resultset = $this->client->select($query);
		
		$num_found = $resultset->getNumFound();
		$max_score = $resultset->getMaxScore();
		
		$ids = [];
		// show documents using the resultset iterator
		foreach($resultset as $document) {
			if($document['id'] ?? null) {
				$ids[(int)$document['id']] = ['index_id' => 0, 'boost' => 100, 'page' => $document['page'] ?? null];
			}
		}
		return $ids;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getSolrClient(string $core, ?array $options=null) : ?Solarium\Client {
		$adapter = new Solarium\Core\Client\Adapter\Curl();
		$adapter->setTimeout(7200);
		$eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
		$config = [ 'endpoint' => [
			'solr' => [
				'host' => caGetOption('host', $options, '127.0.0.1'),
				'port' => caGetOption('port', $options, 8983),
				'core' => $core,
				'path' => caGetOption('path', $options, ''),
				'username' => caGetOption('username', $options, null),
				'password' => caGetOption('password', $options, null)
			]
		]];
		$client = new Solarium\Client($adapter, $eventDispatcher, $config);
		
		return $client;
	}
	# -------------------------------------------------------
}
