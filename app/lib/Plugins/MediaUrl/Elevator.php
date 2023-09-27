<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/MediaUrl/Elevator.php :
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
 * @package CollectiveAccess
 * @subpackage MediaUrlParser
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\MediaUrl\Plugins;
 
 /**
  *
  */
  require_once(__CA_LIB_DIR__.'/Plugins/MediaUrl/BaseMediaUrlPlugin.php');
 
class Elevator Extends BaseMediaUrlPlugin {	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->description = _t('Parses Elevator URLs (https://it.umn.edu/services-technologies/elevator)');
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function register() {
		$this->info["INSTANCE"] = $this;
		return $this->info;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	public function checkStatus() {
		$status = parent::checkStatus();
		$status['available'] = is_array($this->register()); 
		return $status;
	}
	# ------------------------------------------------
	/**
	 * Attempt to parse Elevator URL. If valid, grab image for thumbnails
	 *
	 * @param string $url
	 * @param array $options No options are currently supported.
	 *
	 * @return bool|array False if url is not valid, array with information about the url if valid.
	 */
	public function parse(string $url, array $options=null) {
		if(!defined('__ELEVATOR_API_URL__') && __ELEVATOR_API_URL__) { return null; }
		if(!defined('__ELEVATOR_KEY__')) { return null; }
		if(!defined('__ELEVATOR_SECRET__')) { return null; }
		
		if (!is_array($parsed_url = parse_url(urldecode($url)))) { return null; }
		
		
		$media_url = null;
		if(($url && preg_match("!viewAsset/([a-z0-9]{24})[/]?$!", $url, $m))) {
			$elevator_id = $m[1];
			$e = new ElevatorAPI(__ELEVATOR_API_URL__, __ELEVATOR_KEY__, __ELEVATOR_SECRET__);
			$children = $e->getAssetChildren($elevator_id);
			if(is_array($children) && is_array($children['matches'])  && is_array($children['matches'][0])) {
				$media_url = $children['matches'][0]['primaryHandlerThumbnail2x'];
			}
		}
		
		if($media_url) {
			$media_url = preg_replace("!^//!", "https://", $media_url);
			return ['url' => $media_url, 'originalUrl' => $url, 'plugin' => 'Elevator', 'originalFilename' => pathInfo($m[1], PATHINFO_BASENAME)];
		}
		

		return false;
	}
	# ------------------------------------------------
	/**
	 * Attempt to fetch content from a Elevator URL
	 *
	 * @param string $url
	 * @param array $options Options include:
	 *		filename = File name to use for fetched file. If omitted a random name is generated. [Default is null]
	 *		extension = Extension to use for fetched file. If omitted ".bin" is used as the extension. [Default is null]
	 *		returnAsString = Return fetched content as string rather than in a file. [Default is false]
	 *
	 * @throws UrlFetchException Thrown if fetch URL fails.
	 * @return bool|array|string False if url is not valid, array with path to file with content and format if successful, string with content if returnAsString option is set.
	 */
	public function fetch(string $url, array $options=null) {
		if ($p = $this->parse($url, $options)) {
			if($dest = caGetOption('filename', $options, null)) {
				$dest .= '.'.caGetOption('extension', $options, '.bin');
			}
			
			$tmp_file = caFetchFileFromUrl($p['url'], $dest);
			
			if (caGetOption('returnAsString', $options, false)) {
				$content = file_get_contents($tmp_file);
				unlink($tmp_file);
				return $content;
			}
			
			if(!$dest) { rename($tmp_file, $tmp_file .= '.'.$format); }
			
			return array_merge($p, ['file' => $tmp_file]);
		}
		return false;
	}
	# ------------------------------------------------
}

/**
* Elevator API access class
* How much do we hate curl?
* But I don't know if the target moodle environment has modern libraries, so this seems like a safe bet.
*/
class ElevatorAPI
{

    private $userId = null;
    private $apiKey = null;
    private $baseURL = null;
    public $fileTypes = null;

    function __construct($elevatorURL, $apiKey, $apiSecret, $userId=null)
    {
        $this->baseURL = $elevatorURL;
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function getLoginURL($callback) {
        $now = time();
        return $this->baseURL . "login/loginAndRedirect/" . $this->apiKey . "/" . $now . "/" . sha1($now . $this->apiSecret) . "/?" . $callback;
    }

    public function getManageURL() {
        return $this->baseURL . "login/editUser/" . $this->userId;
    }


    /**
     * isCurrentUserLogged in allows us to check if the user's session is currently cached on elevator.
     * This is because elevator is currently reliant entirely on shib sessions for getting things like course enrollment data.
     * If this becomes a big issue, we may want to move to using ldap on the elevator side for that, but I'm hoping to avoid that
     * dependency for now.
     */
    function isCurrentUserLoggedIn($userId) {
        $request = "hello";
        return $this->execute($request, null, $userId);
    }

    function getAssetsFromDrawer($drawerId, $pageNumber=0) {
        $request = "api_drawers/getContentsOfDrawer/" . $drawerId . "/" . ($pageNumber-1) . "/" . $this->fileTypes;
        $assetList = array();
        $result = $this->execute($request);
        if ($result) {
            error_log($result);
            $assetList = json_decode($result, true);
        }

        return $assetList;
    }

    function getAssetsFromCollection($collectionId, $pageNumber=0) {
        $request = "collections/getContentsOfCollection/" . $collectionId . "/" . ($pageNumber-1) . "/" . $this->fileTypes;
        $assetList = array();
        $result = $this->execute($request);
        if ($result) {
            $assetList = json_decode($result, true);
        }

        return $assetList;
    }

    /**
     * In Elevator, a single asset may contain many files.  Moodle doesn't have a great way to display that, so instead
     * in those cases we display the "parent" asset as a folder and place all the files inside it.
     */
    function getAssetChildren($objectId) {
        $request = "asset/getAssetChildren/" . $objectId . "/" . $this->fileTypes;
        $assetList = array();
        $result = $this->execute($request);
        if ($result) {
            $assetList = json_decode($result, true);
        }

        return $assetList;
    }

    function getEmbedContent($objectId, $instance, $excerpt=null) {
        if ($excerpt) {
            $request = "asset/getExcerptLink/" . $excerpt . "/" . $instance;
        }
        else {
            $request = "asset/getEmbedLink/" . $objectId . "/" . $instance;
        }

        $assetLink = "";

        $result = $this->execute($request);
        if ($result) {
            $assetLink = $result;
        }

        return $assetLink;

    }


    function search($searchTerm, $pageNumber = 0) {

        $request = "search/performSearch/";

        $postArray['searchTerm'] = $searchTerm;
        $postArray['pageNumber'] = ($pageNumber - 1); // we keep moodle 1 indexed and fix here

        $assetList = array();

        $result = $this->execute($request, $postArray);
        if ($result) {
            $assetList = json_decode($result, true);
        }

        return $assetList;

    }

    function assetLookup($assetId) {
        $request = "asset/assetLookup/" . $assetId;

        $assetInfo = array();

        $result = $this->execute($request);
        if ($result) {
            $assetInfo = json_decode($result, true);
        }

        return $assetInfo;

    }

    function assetPreview($assetId) {
        $request = "asset/assetPreview/" . $assetId;

        $assetInfo = array();

        $result = $this->execute($request);
        if ($result) {
            $assetInfo = json_decode($result, true);
        }

        return $assetInfo;

    }

    function fileLookup($assetId) {
        $request = "asset/fileLookup/" . $assetId;

        $assetInfo = array();

        $result = $this->execute($request);
        if ($result) {
            $assetInfo = json_decode($result, true);
        }

        return $assetInfo;

    }


    function getDrawers() {
        $request = "api_drawers/listDrawers";
        $drawerList = array();
        $result = $this->execute($request);
        if ($result) {
            $drawerList = json_decode($result, true);
        }

        return $drawerList;
    }

    function getCollections() {
        $request = "collections/listCollections";
        $collectionList = array();
        $result = $this->execute($request);
        if ($result) {
            $collectionList = json_decode($result, true);
        }

        return $collectionList;

    }

    function importAsset($assetBlock, $collectionId, $templateId) {

        $post = [];
        $post['collectionId'] = $collectionId;
        $post['templateId'] = $templateId;
        $post['assets'] = json_encode($assetBlock);

        $request = "import/importAssets/";
        $result = $this->execute($request, $post);
        if ($result) {
            $assetList = json_decode($result, true);
            return $assetList;
        }
    }


    private function execute($targetURL=null, $postArray=null, $userId=null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . $targetURL);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        if (!$userId) {
            $userId = $this->userId;
        }
        if(!$targetURL) {
            return false;
        }

        $now = time();
        $header[] = "Authorization-User: " . $userId;
        $header[] = "Authorization-Key: " . $this->apiKey;
        $header[] = "Authorization-Timestamp: " . $now;
        $header[] = "Authorization-Hash: " . sha1($now . $this->apiSecret);

        if ($postArray) {
            curl_setopt($ch,CURLOPT_POST, count($postArray));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $postArray);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        try {
            $data = curl_exec($ch);
            $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response == 200) {
                return $data;
            }
            else {
                return false;
            }
        }
        catch (Exception $ex) {
            // echo $ex;
            return false;
        }

    }
}
