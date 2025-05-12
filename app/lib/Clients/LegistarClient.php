<?php
/** ---------------------------------------------------------------------
 * app/lib/Clients/legistarClient.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2010 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace Clients\Legistar;
use GuzzleHttp\Client;

class LegistarClient {
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private $client = null;
	
	/**
	 *
	 */
	private $base_url = null;
	
	/**
	 *
	 */
	static private $s_history_cache = [];
	
	/**
	 *
	 */
	static private $s_body_cache = [];
	
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct(string $client) {
		if(!$this->setClient($client)) {
			throw new \ApplicationException(_t('Invalid Legistar client value'));
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function setClient(string $client) : bool {
		if(!strlen($client)) { return false; }
		$this->client = $client;
		$this->base_url = "https://webapi.legistar.com/v1/{$client}/";
		return true;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getResponse($type, $options=null) {
		$logger = caGetLogger(['logDirectory' => './log']);
		
		$retry_count = 0;
		
		while($retry_count < 5) {
			try {
				$client = new \GuzzleHttp\Client(['base_uri' => $this->base_url]);
				$response = $client->request('GET', $type);
				
				break;
				
			} catch(Exception $e) {
				print "[WARNING] Network connection to ".$this->base_url." FAILED; retrying...\n";	
				$logger->logWarn("When calling {$type} connection FAILED; retry {$retry_count}: {$e}");
				$retry_count++;
				sleep(30);
			}
		}
		if($retry_count >= 5) {
			print "[ERROR] Network connection to ".$this->base_url." FAILED AFTER 5 RETRIES\n";	
			$logger->logError("When calling {$type} connection FAILED; {$e}");
			return null;
		}
		
		if($options['returnBody'] ?? false) {
	
			return json_decode($response->getBody(), true);		
		}
		
		return $response;
	}
	# ------------------------------------------------------------------
	//
	//
	//
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getMatters(?array $options=[]) {
		$filter = [];
		
		$count = (string)caGetOption('count', $options, 1000);
		$skip = (string)caGetOption('skip', $options, 0);
		$after_date = caGetOption('afterDate', $options, null);
		
		if($after_date) {
			$ts = caDateToUnixTimestamp($after_date);
			
			//$filter=EventDate+ge+datetime%272014-09-01%27
			$filter[] = "MatterLastModifiedUtc ge datetime'".date('Y-m-d', $ts)."'";
		}
	
		#https://webapi.legistar.com/v1/seattle/matters/?$top=1000&$skip=0
		#https://webapi.legistar.com/v1/seattle/matters/?$top=1000&$skip=1000
		#https://webapi.legistar.com/v1/seattle/matters/?$top=1000&$skip=2000
		
		$type = "matters/?\$top={$count}&\$skip={$skip}";
		if(sizeof($filter)) {
			$type .= "&\$filter=".urlencode(join(" and ", $filter));
		}
		return $this->getResponse($type, ['returnBody' => true]);
	
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getMatterByID($matterID) {
		#https://webapi.legistar.com/v1/seattle/matters/1785
		
		$type = 'matters/'.$matterID;
		return $this->getResponse($type, ['returnBody' => true]);
	
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getSponsor($matterID) {
		#/Matters/{MatterId}/Sponsors
		
		$type = 'matters/'.$matterID.'/Sponsors';
		$body = $this->getResponse($type, ['returnBody' => true]);
		
		$sponsor=[];
		for($i=0; $i<sizeof($body); $i++){
			$sponsor[]= $body[$i]['MatterSponsorName'];
		}
			
		return $sponsor;
	
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getAttachment($matterID) {
		#/Matters/{MatterId}/Attachments   //all available attachment  ca_objects.attachments.att_name
		#/Matters/{MatterId}/Attachments/{MatterAttachmentId}/File   ca_objects.attachments.att_url
		
		return $this->getResponse("matters/{$matterID}/Attachments", ['returnBody' => true]);
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getMattersText($matterID) {
		#https://webapi.legistar.com/v1/seattle/matters/9194/Versions
		#https://webapi.legistar.com/v1/seattle/matters/9194/Texts/10653
		
		$body = $this->getResponse("matters/{$matterID}/Versions", ['returnBody' => true]);
		
		$i=sizeof($body)-1;
		$textID = $body[$i]['Key'];
		
		$txt = $this->getResponse("matters/{$matterID}/Texts/{$textID}", ['returnBody' => true]);
		//MatterTextRtf tag for rich text
		
		return nl2br($txt['MatterTextPlain']);
		//return $txt['MatterTextRtf'];
	
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getActions() {
		$d = $this->getResponse("Actions", ['returnBody' => true]);
		if(!is_array($d)) { return null; }
		
		$acc = [];
		foreach($d as $id => $info) {
			$acc[$info['ActionId']] = $info['ActionName'];
		}
		return $acc;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getCommittee($matterID) {
		$entries = $this->getHistories($matterID, ['exclude' => ["City Council", "Full Council"]], ["1", "0"]);
		foreach($entries as $i => $h) {
			$c = preg_replace("![ ]*Committee$!i", "", $h['MatterHistoryActionBodyName']);
			$c = preg_replace("!^Committee[ ]+on[ ]+!i", "", $c);
			
			$ei = [];
			$body_id = null;
			if($eventID = $h['MatterHistoryEventId']){
				$event = $this->getResponse("events/{$eventID}?EventItems=1&AgendaNote=1&MinutesNote=1&Attachments=1", ['returnBody' => true]);
				
				$eis = array_filter($event['EventItems'] ?? [], function($v) use ($matterID) {
					return ($v['EventItemMatterId'] == $matterID);
				});
				$ei = array_shift($eis);
				// Get committee votes (CMV)
				$vote_counts = [];
				$vote_str = null;
				
				$vote_info = $this->processVotes($ei['EventItemId']);	
				
				$body_id = $event['EventBodyId'] ?? null;	
			}
			$ret = [
				'committee' => $c,
				'action' => $h['MatterHistoryActionName'] ?? '', 
				'date' => $h['MatterHistoryActionDate'] ?? '', 
				'description' => $ei['EventItemActionText'] ?? '', 
				'vote_count' => $vote_info['counts'] ?? null, 
				'vote' => $vote_info['display'] ?? null,
				'passed' => false,
				'committee_recommendation' => null,
				'bodyID' => $body_id,
				'body' => $body_id ? $this->getBodyByID($body_id) : null
			];
			
			if(($ei['EventItemPassedFlag'] ?? null) && in_array((int)$ei['EventItemPassedFlag'], [0, 1], true)) {
				if((int)$ei['EventItemPassedFlag'] === 1) {
					$ret['passed'] = true;
				}
				if(isset($ei['EventItemActionName'])) {
					$ret['committee_recommendation'] = $ei['EventItemActionName'];
				}
			}
	
			return $ret;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getVote($matterID) {
		$entries = $this->getHistories($matterID, ['include' => ["City Council", "Full Council"]], ["1", "0"]);
		
		$vote_info = null;
		foreach($entries as $i => $h) {
			if(!in_array((string)$h['MatterHistoryPassedFlag'], ["1", "0"], true)) { continue; }
			
			if($eventID = $h['MatterHistoryEventId']){
				$event = $this->getResponse("events/{$eventID}?EventItems=1&AgendaNote=1&MinutesNote=1&Attachments=1", ['returnBody' => true]);
				
				$eis = array_filter($event['EventItems'] ?? [], function($v) use ($matterID) {
					return (($v['EventItemMatterId'] == $matterID) && (in_array((string)$v['EventItemPassedFlag'], ["1", "0"], true)));
				});
				$ei = array_shift($eis);
				
				// Get council votes 
				$vote_counts = [];
				$vote_str = null;
				
				$vote_info = $this->processVotes($ei['EventItemId']);		
			}
		}
		return $vote_info;
	}
	
	
	// -------------------------------------------------------------------------------------
	// Utilities
	// -------------------------------------------------------------------------------------
	/**
	 * Get history entries for matter, filtering on gov't bodies (committed, full council, city council etc.) and passed flags
	 */	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getHistories(int $matterID, array $bodies, array $flags) {
		if(!is_array(self::$s_history_cache)) { self::$s_history_cache = []; }
		
		if(!(self::$s_history_cache[$matterID] ?? null)) {
			if(sizeof(self::$s_history_cache) > 100) { self::$s_history_cache = []; }
			$body = $this->getResponse("Matters/{$matterID}/Histories", ['returnBody' => true]);
			self::$s_history_cache[$matterID] = $body;
		} else {
			$body = self::$s_history_cache[$matterID];
		}
		
		$acc = [];
		foreach($body as $i => $h) {
			if(is_array($bodies['exclude']) && sizeof($bodies['exclude']) && in_array($h['MatterHistoryActionBodyName'], $bodies['exclude'], true)) { continue; }
			if(is_array($bodies['include']) && sizeof($bodies['include']) && !in_array($h['MatterHistoryActionBodyName'], $bodies['include'], true)) { continue; }
			if(is_array($flags) && sizeof($flags) && !in_array((string)$h['MatterHistoryPassedFlag'], $flags)) { continue; }
			
			$acc[] = $h;
		}
		
		return $acc;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getIntroDate($matterID) {
		$body = $this->getHistories($matterID, [], []);
		if(is_array($body) && sizeof($body)) {
			foreach($body as $h) {
				if(strlen($h['MatterHistoryActionDate'] ?? null)) { return $h['MatterHistoryActionDate']; }
			}
		}
		return null;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function processVotes(int $eventItemID) {
		$vote_counts = [];
		$vote_str = null;
		if($vote_body = $this->getResponse("EventItems/{$eventItemID}/Votes", ['returnBody' => true])){
			foreach($vote_body as $v) {
				$vote_counts[$v['VoteValueName']][] = $v['VotePersonName'];
			}
			
			$in_favor = is_array($vote_counts['In Favor']) ? sizeof($vote_counts['In Favor']) : 0;
			$opposed = is_array($vote_counts['Opposed']) ? sizeof($vote_counts['Opposed']) : 0;
			
			$vote_str = "{$in_favor}-{$opposed}";
			if(sizeof($vote_counts)) {
				$acc = [];
				foreach($vote_counts as $k => $vc) {
					if(in_array($k, ['In Favor', 'Opposed'], true)) { continue; }
					$acc[] = "{$k}: ".sizeof($vote_counts[$k]);
				}
				if(sizeof($acc)) { $vote_str .= " (".join("; ", $acc).")"; }
			}
		}
		return ['counts' => $vote_counts, 'display' => $vote_str];
	}
	# ------------------------------------------------------------------
	//
	//
	//
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getEvents(?array $options=[]) {
		$filter = [];
		
		$count = (string)caGetOption('count', $options, 1000);
		$skip = (string)caGetOption('skip', $options, 0);
		$after_date = caGetOption('afterDate', $options, null);
		
		if($after_date) {
			$ts = caDateToUnixTimestamp($after_date);
			
			//$filter=EventDate+ge+datetime%272014-09-01%27
			$filter[] = "EventLastModifiedUtc ge datetime'".date('Y-m-d', $ts)."'";
		}
	
		
		$type = "events/?\$top={$count}&\$skip={$skip}";
		if(sizeof($filter)) {
			$type .= "&\$filter=".urlencode(join(" and ", $filter));
		}
		return $this->getResponse($type, ['returnBody' => true]);
	
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */	
	function getBodyByID($bodyID) {
		#/Matters/{MatterId}/Sponsors
		if(isset(self::$s_body_cache[$bodyID])) {
			return self::$s_body_cache[$bodyID];
		}
		$type = 'bodies/'.$bodyID;
		$data =  $this->getResponse($type, ['returnBody' => true]);
		self::$s_body_cache[$bodyID] = $data;
		return $data;
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	function getMinutesForMeeting(array $options) : array {
		$filter = [
			"MatterTypeId eq 66"
		];
		
		$date = (string)caGetOption('date', $options, null);
		$body = (string)caGetOption('body', $options, null);
		$body_id = (string)caGetOption('body_id', $options, null);
		
		if($date) {
			$ts = caDateToUnixTimestamp($date);
			$filter[] = "MatterIntroDate eq datetime'".date('Y-m-d', $ts)."'";
		}
		if($body_id) {
			$filter[] = "MatterBodyId eq {$body_id}";
		} elseif($body) {
			$filter[] = "MatterBodyName eq \"{$body}\"";
		}
		
		$type = "matters/?\$top=10";
		if(sizeof($filter)) {
			$type .= "&\$filter=".urlencode(join(" and ", $filter));
		}
		$data = $this->getResponse($type, ['returnBody' => true]);
		
		return (is_array($data) && sizeof($data)) ? array_shift($data) : [];
	}
	# ------------------------------------------------------------------
}