<?php
/* phpFlickr
 * Written by Dan Coulter (dan@dancoulter.com)
 * Project Home Page: http://github.com/dancoulter/phpflickr
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

	class phpFlickr {
		var $api_key;
		var $secret;

		var $rest_endpoint = 'https://api.flickr.com/services/rest/';
		var $upload_endpoint = 'https://up.flickr.com/services/upload/';
		var $replace_endpoint = 'https://up.flickr.com/services/replace/';
		var $req;
		var $response;
		var $parsed_response;
		var $cache = false;
		var $cache_db = null;
		var $cache_table = null;
		var $cache_dir = null;
		var $cache_expire = null;
		var $cache_key = null;
		var $last_request = null;
		var $die_on_error;
		var $error_code;
		Var $error_msg;
		var $token;
		var $php_version;
		var $custom_post = null, $custom_cache_get = null, $custom_cache_set = null;

		/*
		 * When your database cache table hits this many rows, a cleanup
		 * will occur to get rid of all of the old rows and cleanup the
		 * garbage in the table.  For most personal apps, 1000 rows should
		 * be more than enough.  If your site gets hit by a lot of traffic
		 * or you have a lot of disk space to spare, bump this number up.
		 * You should try to set it high enough that the cleanup only
		 * happens every once in a while, so this will depend on the growth
		 * of your table.
		 */
		var $max_cache_rows = 1000;

		function __construct($api_key, $secret = NULL, $die_on_error = false) {
			//The API Key must be set before any calls can be made.  You can
			//get your own at https://www.flickr.com/services/api/misc.api_keys.html
			$this->api_key = $api_key;
			$this->secret = $secret;
			$this->die_on_error = $die_on_error;
			$this->service = "flickr";

			//Find the PHP version and store it for future reference
			$this->php_version = explode("-", phpversion());
			$this->php_version = explode(".", $this->php_version[0]);
		}

		function enableCache ($type, $connection, $cache_expire = 600, $table = 'flickr_cache') {
			// Turns on caching.  $type must be either "db" (for database caching) or "fs" (for filesystem).
			// When using db, $connection must be a PEAR::DB connection string. Example:
			//	  "mysql://user:password@server/database"
			// If the $table, doesn't exist, it will attempt to create it.
			// When using file system, caching, the $connection is the folder that the web server has write
			// access to. Use absolute paths for best results.  Relative paths may have unexpected behavior
			// when you include this.  They'll usually work, you'll just want to test them.
			if ($type == 'db') {
				if ( preg_match('|mysql://([^:]*):([^@]*)@([^/]*)/(.*)|', $connection, $matches) ) {
					//Array ( [0] => mysql://user:password@server/database [1] => user [2] => password [3] => server [4] => database )
					$db = mysqli_connect($matches[3],  $matches[1],  $matches[2]);
					mysqli_query($db, "USE $matches[4]");

					/*
					 * If high performance is crucial, you can easily comment
					 * out this query once you've created your database table.
					 */
					mysqli_query($db, "
						CREATE TABLE IF NOT EXISTS `$table` (
							`request` varchar(128) NOT NULL,
							`response` mediumtext NOT NULL,
							`expiration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
							UNIQUE KEY `request` (`request`)
						)
					");

					$result = mysqli_query($db, "SELECT COUNT(*) 'count' FROM $table");
					if( $result ) {
						$result = mysqli_fetch_assoc($result);						
					}
					
					if ( $result && $result['count'] > $this->max_cache_rows ) {
						mysqli_query($db, "DELETE FROM $table WHERE CURRENT_TIMESTAMP > expiration");
						mysqli_query($db, 'OPTIMIZE TABLE ' . $this->cache_table);
					}
					$this->cache = 'db';
					$this->cache_db = $db;
					$this->cache_table = $table;
				}
			} elseif ($type == 'fs') {
				$this->cache = 'fs';
				$connection = realpath($connection);
				$this->cache_dir = $connection;
				if ($dir = opendir($this->cache_dir)) {
					while ($file = readdir($dir)) {
						if (substr($file, -6) == '.cache' && ((filemtime($this->cache_dir . '/' . $file) + $cache_expire) < time()) ) {
							unlink($this->cache_dir . '/' . $file);
						}
					}
				}
			} elseif ( $type == 'custom' ) {
				$this->cache = "custom";
				$this->custom_cache_get = $connection[0];
				$this->custom_cache_set = $connection[1];
			}
			$this->cache_expire = $cache_expire;
		}

		function getCached ($request)
		{
			//Checks the database or filesystem for a cached result to the request.
			//If there is no cache result, it returns a value of false. If it finds one,
			//it returns the unparsed XML.
			unset($request['api_sig']);
			foreach ( $request as $key => $value ) {
				if ( empty($value) ) unset($request[$key]);
				else $request[$key] = (string) $request[$key];
			}
			//if ( is_user_logged_in() ) print_r($request);
			$reqhash = md5(serialize($request));
			$this->cache_key = $reqhash;
			$this->cache_request = $request;
			if ($this->cache == 'db') {
				$result = mysqli_query($this->cache_db, "SELECT response FROM " . $this->cache_table . " WHERE request = '" . $reqhash . "' AND CURRENT_TIMESTAMP < expiration");
				if ( $result && mysqli_num_rows($result) ) {
					$result = mysqli_fetch_assoc($result);
					return urldecode($result['response']);
				} else {
					return false;
				}
			} elseif ($this->cache == 'fs') {
				$file = $this->cache_dir . '/' . $reqhash . '.cache';
				if (file_exists($file)) {
					if ($this->php_version[0] > 4 || ($this->php_version[0] == 4 && $this->php_version[1] >= 3)) {
						return file_get_contents($file);
					} else {
						return implode('', file($file));
					}
				}
			} elseif ( $this->cache == 'custom' ) {
				return call_user_func_array($this->custom_cache_get, array($reqhash));
			}
			return false;
		}

		function cache ($request, $response)
		{
			//Caches the unparsed response of a request.
			unset($request['api_sig']);
			foreach ( $request as $key => $value ) {
				if ( empty($value) ) unset($request[$key]);
				else $request[$key] = (string) $request[$key];
			}
			$reqhash = md5(serialize($request));
			if ($this->cache == 'db') {
				//$this->cache_db->query("DELETE FROM $this->cache_table WHERE request = '$reqhash'");
				$response = urlencode($response);
				$sql = 'INSERT INTO '.$this->cache_table.' (request, response, expiration) 
						VALUES (\''.$reqhash.'\', \''.$response.'\', TIMESTAMPADD(SECOND,'.$this->cache_expire.',CURRENT_TIMESTAMP))
						ON DUPLICATE KEY UPDATE response=\''.$response.'\', 
						expiration=TIMESTAMPADD(SECOND,'.$this->cache_expire.',CURRENT_TIMESTAMP) ';

				$result = mysqli_query($this->cache_db, $sql);
				if(!$result) {
					echo mysqli_error($this->cache_db);
				}
					
				return $result;
			} elseif ($this->cache == "fs") {
				$file = $this->cache_dir . "/" . $reqhash . ".cache";
				$fstream = fopen($file, "w");
				$result = fwrite($fstream,$response);
				fclose($fstream);
				return $result;
			} elseif ( $this->cache == "custom" ) {
				return call_user_func_array($this->custom_cache_set, array($reqhash, $response, $this->cache_expire));
			}
			return false;
		}

		function setCustomPost ( $function ) {
			$this->custom_post = $function;
		}

		function post ($data, $type = null) {
			if ( is_null($type) ) {
				$url = $this->rest_endpoint;
			}

			if ( !is_null($this->custom_post) ) {
				return call_user_func($this->custom_post, $url, $data);
			}

			if ( !preg_match("|https://(.*?)(/.*)|", $url, $matches) ) {
				die('There was some problem figuring out your endpoint');
			}

			if ( function_exists('curl_init') ) {
				// Has curl. Use it!
				$curl = curl_init($this->rest_endpoint);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				curl_close($curl);
			} else {
				// Use sockets.
				foreach ( $data as $key => $value ) {
					$data[$key] = $key . '=' . urlencode($value);
				}
				$data = implode('&', $data);

				$fp = @pfsockopen('ssl://'.$matches[1], 443);
				if (!$fp) {
					die('Could not connect to the web service');
				}
				fputs ($fp,'POST ' . $matches[2] . " HTTP/1.1\n");
				fputs ($fp,'Host: ' . $matches[1] . "\n");
				fputs ($fp,"Content-type: application/x-www-form-urlencoded\n");
				fputs ($fp,"Content-length: ".strlen($data)."\n");
				fputs ($fp,"Connection: close\r\n\r\n");
				fputs ($fp,$data . "\n\n");
				$response = "";
				while(!feof($fp)) {
					$response .= fgets($fp, 1024);
				}
				fclose ($fp);
				$chunked = false;
				$http_status = trim(substr($response, 0, strpos($response, "\n")));
				if ( $http_status != 'HTTP/1.1 200 OK' ) {
					die('The web service endpoint returned a "' . $http_status . '" response');
				}
				if ( strpos($response, 'Transfer-Encoding: chunked') !== false ) {
					$temp = trim(strstr($response, "\r\n\r\n"));
					$response = '';
					$length = trim(substr($temp, 0, strpos($temp, "\r")));
					while ( trim($temp) != "0" && ($length = trim(substr($temp, 0, strpos($temp, "\r")))) != "0" ) {
						$response .= trim(substr($temp, strlen($length)+2, hexdec($length)));
						$temp = trim(substr($temp, strlen($length) + 2 + hexdec($length)));
					}
				} elseif ( strpos($response, 'HTTP/1.1 200 OK') !== false ) {
					$response = trim(strstr($response, "\r\n\r\n"));
				}
			}
			return $response;
		}

		function request ($command, $args = array(), $nocache = false)
		{
			//Sends a request to Flickr's REST endpoint via POST.
			if (substr($command,0,7) != "flickr.") {
				$command = "flickr." . $command;
			}

			//Process arguments, including method and login data.
			$args = array_merge(array("method" => $command, "format" => "json", "nojsoncallback" => "1", "api_key" => $this->api_key), $args);
			if (!empty($this->token)) {
				$args = array_merge($args, array("auth_token" => $this->token));
			} elseif (!empty($_SESSION['phpFlickr_auth_token'])) {
				$args = array_merge($args, array("auth_token" => $_SESSION['phpFlickr_auth_token']));
			}
			ksort($args);
			$auth_sig = "";
			$this->last_request = $args;
			$this->response = $this->getCached($args);
			if (!($this->response) || $nocache) {
				foreach ($args as $key => $data) {
					if ( is_null($data) ) {
						unset($args[$key]);
						continue;
					}
					$auth_sig .= $key . $data;
				}
				if (!empty($this->secret)) {
					$api_sig = md5($this->secret . $auth_sig);
					$args['api_sig'] = $api_sig;
				}
				$this->response = $this->post($args);
				$this->cache($args, $this->response);
			}


			/*
			 * Uncomment this line (and comment out the next one) if you're doing large queries
			 * and you're concerned about time.  This will, however, change the structure of
			 * the result, so be sure that you look at the results.
			 */
			$this->parsed_response = json_decode($this->response, TRUE);
/* 			$this->parsed_response = $this->clean_text_nodes(json_decode($this->response, TRUE)); */
			if ($this->parsed_response['stat'] == 'fail') {
				if ($this->die_on_error) die("The Flickr API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
				else {
					$this->error_code = $this->parsed_response['code'];
					$this->error_msg = $this->parsed_response['message'];
					$this->parsed_response = false;
				}
			} else {
				$this->error_code = false;
				$this->error_msg = false;
			}
			return $this->response;
		}

		function clean_text_nodes ($arr) {
			if (!is_array($arr)) {
				return $arr;
			} elseif (count($arr) == 0) {
				return $arr;
			} elseif (count($arr) == 1 && array_key_exists('_content', $arr)) {
				return $arr['_content'];
			} else {
				foreach ($arr as $key => $element) {
					$arr[$key] = $this->clean_text_nodes($element);
				}
				return($arr);
			}
		}

		function setToken ($token) {
			// Sets an authentication token to use instead of the session variable
			$this->token = $token;
		}

		function setProxy ($server, $port) {
			// Sets the proxy for all phpFlickr calls.
			$this->req->setProxy($server, $port);
		}

		function getErrorCode () {
			// Returns the error code of the last call.  If the last call did not
			// return an error. This will return a false boolean.
			return $this->error_code;
		}

		function getErrorMsg () {
			// Returns the error message of the last call.  If the last call did not
			// return an error. This will return a false boolean.
			return $this->error_msg;
		}

		/* These functions are front ends for the flickr calls */

		function buildPhotoURL ($photo, $size = "Medium") {
			//receives an array (can use the individual photo data returned
			//from an API call) and returns a URL (doesn't mean that the
			//file size exists)
			$sizes = array(
				"square" => "_s",
				"square_75" => "_s",
				"square_150" => "_q",
				"thumbnail" => "_t",
				"small" => "_m",
				"small_240" => "_m",
				"small_320" => "_n",
				"medium" => "",
				"medium_500" => "",
				"medium_640" => "_z",
				"medium_800" => "_c",
				"large" => "_b",
				"large_1024" => "_b",
				"large_1600" => "_h",
				"large_2048" => "_k",
				"original" => "_o",
			);

			$size = strtolower($size);
			if (!array_key_exists($size, $sizes)) {
				$size = "medium";
			}

			if ($size == "original") {
				$url = "https://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['originalsecret'] . "_o" . "." . $photo['originalformat'];
			} else {
				$url = "https://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . $sizes[$size] . ".jpg";
			}
			return $url;
		}

		function sync_upload ($photo, $title = null, $description = null, $tags = null, $is_public = null, $is_friend = null, $is_family = null) {
			if ( function_exists('curl_init') ) {
				// Has curl. Use it!

				//Process arguments, including method and login data.
				$args = array("api_key" => $this->api_key, "title" => $title, "description" => $description, "tags" => $tags, "is_public" => $is_public, "is_friend" => $is_friend, "is_family" => $is_family);
				if (!empty($this->token)) {
					$args = array_merge($args, array("auth_token" => $this->token));
				} elseif (!empty($_SESSION['phpFlickr_auth_token'])) {
					$args = array_merge($args, array("auth_token" => $_SESSION['phpFlickr_auth_token']));
				}

				ksort($args);
				$auth_sig = "";
				foreach ($args as $key => $data) {
					if ( is_null($data) ) {
						unset($args[$key]);
					} else {
						$auth_sig .= $key . $data;
					}
				}
				if (!empty($this->secret)) {
					$api_sig = md5($this->secret . $auth_sig);
					$args["api_sig"] = $api_sig;
				}

				$photo = realpath($photo);
				$args['photo'] = '@' . $photo;


				$curl = curl_init($this->upload_endpoint);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				$this->response = $response;
				curl_close($curl);

				$rsp = explode("\n", $response);
				foreach ($rsp as $line) {
					if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
						if ($this->die_on_error)
							die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
						else {
							$this->error_code = $match[1];
							$this->error_msg = $match[2];
							$this->parsed_response = false;
							return false;
						}
					} elseif (preg_match("|<photoid>(.*)</photoid>|", $line, $match)) {
						$this->error_code = false;
						$this->error_msg = false;
						return $match[1];
					}
				}

			} else {
				die("Sorry, your server must support CURL in order to upload files");
			}

		}

		function async_upload ($photo, $title = null, $description = null, $tags = null, $is_public = null, $is_friend = null, $is_family = null) {
			if ( function_exists('curl_init') ) {
				// Has curl. Use it!

				//Process arguments, including method and login data.
				$args = array("async" => 1, "api_key" => $this->api_key, "title" => $title, "description" => $description, "tags" => $tags, "is_public" => $is_public, "is_friend" => $is_friend, "is_family" => $is_family);
				if (!empty($this->token)) {
					$args = array_merge($args, array("auth_token" => $this->token));
				} elseif (!empty($_SESSION['phpFlickr_auth_token'])) {
					$args = array_merge($args, array("auth_token" => $_SESSION['phpFlickr_auth_token']));
				}

				ksort($args);
				$auth_sig = "";
				foreach ($args as $key => $data) {
					if ( is_null($data) ) {
						unset($args[$key]);
					} else {
						$auth_sig .= $key . $data;
					}
				}
				if (!empty($this->secret)) {
					$api_sig = md5($this->secret . $auth_sig);
					$args["api_sig"] = $api_sig;
				}

				$photo = realpath($photo);
				$args['photo'] = '@' . $photo;


				$curl = curl_init($this->upload_endpoint);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				$this->response = $response;
				curl_close($curl);

				$rsp = explode("\n", $response);
				foreach ($rsp as $line) {
					if (preg_match('/<err code="([0-9]+)" msg="(.*)"/', $line, $match)) {
						if ($this->die_on_error)
							die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
						else {
							$this->error_code = $match[1];
							$this->error_msg = $match[2];
							$this->parsed_response = false;
							return false;
						}
					} elseif (preg_match("/<ticketid>(.*)</", $line, $match)) {
						$this->error_code = false;
						$this->error_msg = false;
						return $match[1];
					}
				}
			} else {
				die("Sorry, your server must support CURL in order to upload files");
			}
		}

		// Interface for new replace API method.
		function replace ($photo, $photo_id, $async = null) {
			if ( function_exists('curl_init') ) {
				// Has curl. Use it!

				//Process arguments, including method and login data.
				$args = array("api_key" => $this->api_key, "photo_id" => $photo_id, "async" => $async);
				if (!empty($this->token)) {
					$args = array_merge($args, array("auth_token" => $this->token));
				} elseif (!empty($_SESSION['phpFlickr_auth_token'])) {
					$args = array_merge($args, array("auth_token" => $_SESSION['phpFlickr_auth_token']));
				}

				ksort($args);
				$auth_sig = "";
				foreach ($args as $key => $data) {
					if ( is_null($data) ) {
						unset($args[$key]);
					} else {
						$auth_sig .= $key . $data;
					}
				}
				if (!empty($this->secret)) {
					$api_sig = md5($this->secret . $auth_sig);
					$args["api_sig"] = $api_sig;
				}

				$photo = realpath($photo);
				$args['photo'] = '@' . $photo;


				$curl = curl_init($this->replace_endpoint);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				$this->response = $response;
				curl_close($curl);

				if ($async == 1)
					$find = 'ticketid';
				 else
					$find = 'photoid';

				$rsp = explode("\n", $response);
				foreach ($rsp as $line) {
					if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
						if ($this->die_on_error)
							die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
						else {
							$this->error_code = $match[1];
							$this->error_msg = $match[2];
							$this->parsed_response = false;
							return false;
						}
					} elseif (preg_match("|<" . $find . ">(.*)</|", $line, $match)) {
						$this->error_code = false;
						$this->error_msg = false;
						return $match[1];
					}
				}
			} else {
				die("Sorry, your server must support CURL in order to upload files");
			}
		}

		function auth ($perms = "read", $remember_uri = true) {
			// Redirects to Flickr's authentication piece if there is no valid token.
			// If remember_uri is set to false, the callback script (included) will
			// redirect to its default page.

			if (empty($_SESSION['phpFlickr_auth_token']) && empty($this->token)) {
				if ( $remember_uri === true ) {
					$_SESSION['phpFlickr_auth_redirect'] = $_SERVER['REQUEST_URI'];
				} elseif ( $remember_uri !== false ) {
					$_SESSION['phpFlickr_auth_redirect'] = $remember_uri;
				}
				$api_sig = md5($this->secret . "api_key" . $this->api_key . "perms" . $perms);

				if ($this->service == "23") {
					header("Location: http://www.23hq.com/services/auth/?api_key=" . $this->api_key . "&perms=" . $perms . "&api_sig=". $api_sig);
				} else {
					header("Location: https://www.flickr.com/services/auth/?api_key=" . $this->api_key . "&perms=" . $perms . "&api_sig=". $api_sig);
				}
				exit;
			} else {
				$tmp = $this->die_on_error;
				$this->die_on_error = false;
				$rsp = $this->auth_checkToken();
				if ($this->error_code !== false) {
					unset($_SESSION['phpFlickr_auth_token']);
					$this->auth($perms, $remember_uri);
				}
				$this->die_on_error = $tmp;
				return $rsp['perms'];
			}
		}

		function auth_url($frob, $perms = 'read') {
			$sig = md5(sprintf('%sapi_key%sfrob%sperms%s', $this->secret, $this->api_key, $frob, $perms));
			return sprintf('https://flickr.com/services/auth/?api_key=%s&perms=%s&frob=%s&api_sig=%s', $this->api_key, $perms, $frob, $sig);
		}

		/*******************************

		To use the phpFlickr::call method, pass a string containing the API method you want
		to use and an associative array of arguments.  For example:
			$result = $f->call("flickr.photos.comments.getList", array("photo_id"=>'34952612'));
		This method will allow you to make calls to arbitrary methods that haven't been
		implemented in phpFlickr yet.

		*******************************/

		function call ($method, $arguments) {
			foreach ( $arguments as $key => $value ) {
				if ( is_null($value) ) unset($arguments[$key]);
			}
			$this->request($method, $arguments);
			return $this->parsed_response ? $this->parsed_response : false;
		}

		/*
			These functions are the direct implementations of flickr calls.
			For method documentation, including arguments, visit the address
			included in a comment in the function.
		*/

		/* Activity methods */
		function activity_userComments ($per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.activity.userComments.html */
			$this->request('flickr.activity.userComments', array("per_page" => $per_page, "page" => $page));
			return $this->parsed_response ? $this->parsed_response['items']['item'] : false;
		}

		function activity_userPhotos ($timeframe = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.activity.userPhotos.html */
			$this->request('flickr.activity.userPhotos', array("timeframe" => $timeframe, "per_page" => $per_page, "page" => $page));
			return $this->parsed_response ? $this->parsed_response['items']['item'] : false;
		}

		/* Authentication methods */
		function auth_checkToken () {
			/* https://www.flickr.com/services/api/flickr.auth.checkToken.html */
			$this->request('flickr.auth.checkToken');
			return $this->parsed_response ? $this->parsed_response['auth'] : false;
		}

		function auth_getFrob () {
			/* https://www.flickr.com/services/api/flickr.auth.getFrob.html */
			$this->request('flickr.auth.getFrob');
			return $this->parsed_response ? $this->parsed_response['frob'] : false;
		}

		function auth_getFullToken ($mini_token) {
			/* https://www.flickr.com/services/api/flickr.auth.getFullToken.html */
			$this->request('flickr.auth.getFullToken', array('mini_token'=>$mini_token));
			return $this->parsed_response ? $this->parsed_response['auth'] : false;
		}

		function auth_getToken ($frob) {
			/* https://www.flickr.com/services/api/flickr.auth.getToken.html */
			$this->request('flickr.auth.getToken', array('frob'=>$frob));
			$_SESSION['phpFlickr_auth_token'] = $this->parsed_response['auth']['token'];
			return $this->parsed_response ? $this->parsed_response['auth'] : false;
		}

		/* Blogs methods */
		function blogs_getList ($service = NULL) {
			/* https://www.flickr.com/services/api/flickr.blogs.getList.html */
			$rsp = $this->call('flickr.blogs.getList', array('service' => $service));
			return $rsp['blogs']['blog'];
		}

		function blogs_getServices () {
			/* https://www.flickr.com/services/api/flickr.blogs.getServices.html */
			return $this->call('flickr.blogs.getServices', array());
		}

		function blogs_postPhoto ($blog_id = NULL, $photo_id, $title, $description, $blog_password = NULL, $service = NULL) {
			/* https://www.flickr.com/services/api/flickr.blogs.postPhoto.html */
			return $this->call('flickr.blogs.postPhoto', array('blog_id' => $blog_id, 'photo_id' => $photo_id, 'title' => $title, 'description' => $description, 'blog_password' => $blog_password, 'service' => $service));
		}

		/* Collections Methods */
		function collections_getInfo ($collection_id) {
			/* https://www.flickr.com/services/api/flickr.collections.getInfo.html */
			return $this->call('flickr.collections.getInfo', array('collection_id' => $collection_id));
		}

		function collections_getTree ($collection_id = NULL, $user_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.collections.getTree.html */
			return $this->call('flickr.collections.getTree', array('collection_id' => $collection_id, 'user_id' => $user_id));
		}

		/* Commons Methods */
		function commons_getInstitutions () {
			/* https://www.flickr.com/services/api/flickr.commons.getInstitutions.html */
			return $this->call('flickr.commons.getInstitutions', array());
		}

		/* Contacts Methods */
		function contacts_getList ($filter = NULL, $page = NULL, $per_page = NULL) {
			/* https://www.flickr.com/services/api/flickr.contacts.getList.html */
			$this->request('flickr.contacts.getList', array('filter'=>$filter, 'page'=>$page, 'per_page'=>$per_page));
			return $this->parsed_response ? $this->parsed_response['contacts'] : false;
		}

		function contacts_getPublicList ($user_id, $page = NULL, $per_page = NULL) {
			/* https://www.flickr.com/services/api/flickr.contacts.getPublicList.html */
			$this->request('flickr.contacts.getPublicList', array('user_id'=>$user_id, 'page'=>$page, 'per_page'=>$per_page));
			return $this->parsed_response ? $this->parsed_response['contacts'] : false;
		}

		function contacts_getListRecentlyUploaded ($date_lastupload = NULL, $filter = NULL) {
			/* https://www.flickr.com/services/api/flickr.contacts.getListRecentlyUploaded.html */
			return $this->call('flickr.contacts.getListRecentlyUploaded', array('date_lastupload' => $date_lastupload, 'filter' => $filter));
		}

		/* Favorites Methods */
		function favorites_add ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.favorites.add.html */
			$this->request('flickr.favorites.add', array('photo_id'=>$photo_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function favorites_getList ($user_id = NULL, $jump_to = NULL, $min_fave_date = NULL, $max_fave_date = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.favorites.getList.html */
			return $this->call('flickr.favorites.getList', array('user_id' => $user_id, 'jump_to' => $jump_to, 'min_fave_date' => $min_fave_date, 'max_fave_date' => $max_fave_date, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function favorites_getPublicList ($user_id, $jump_to = NULL, $min_fave_date = NULL, $max_fave_date = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.favorites.getPublicList.html */
			return $this->call('flickr.favorites.getPublicList', array('user_id' => $user_id, 'jump_to' => $jump_to, 'min_fave_date' => $min_fave_date, 'max_fave_date' => $max_fave_date, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function favorites_remove ($photo_id, $user_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.favorites.remove.html */
			$this->request("flickr.favorites.remove", array('photo_id' => $photo_id, 'user_id' => $user_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		/* Galleries Methods */
		function galleries_addPhoto ($gallery_id, $photo_id, $comment = NULL) {
			/* https://www.flickr.com/services/api/flickr.galleries.addPhoto.html */
			return $this->call('flickr.galleries.addPhoto', array('gallery_id' => $gallery_id, 'photo_id' => $photo_id, 'comment' => $comment));
		}

		function galleries_create ($title, $description, $primary_photo_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.galleries.create.html */
			return $this->call('flickr.galleries.create', array('title' => $title, 'description' => $description, 'primary_photo_id' => $primary_photo_id));
		}

		function galleries_editMeta ($gallery_id, $title, $description = NULL) {
			/* https://www.flickr.com/services/api/flickr.galleries.editMeta.html */
			return $this->call('flickr.galleries.editMeta', array('gallery_id' => $gallery_id, 'title' => $title, 'description' => $description));
		}

		function galleries_editPhoto ($gallery_id, $photo_id, $comment) {
			/* https://www.flickr.com/services/api/flickr.galleries.editPhoto.html */
			return $this->call('flickr.galleries.editPhoto', array('gallery_id' => $gallery_id, 'photo_id' => $photo_id, 'comment' => $comment));
		}

		function galleries_editPhotos ($gallery_id, $primary_photo_id, $photo_ids) {
			/* https://www.flickr.com/services/api/flickr.galleries.editPhotos.html */
			return $this->call('flickr.galleries.editPhotos', array('gallery_id' => $gallery_id, 'primary_photo_id' => $primary_photo_id, 'photo_ids' => $photo_ids));
		}

		function galleries_getInfo ($gallery_id) {
			/* https://www.flickr.com/services/api/flickr.galleries.getInfo.html */
			return $this->call('flickr.galleries.getInfo', array('gallery_id' => $gallery_id));
		}

		function galleries_getList ($user_id, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.galleries.getList.html */
			return $this->call('flickr.galleries.getList', array('user_id' => $user_id, 'per_page' => $per_page, 'page' => $page));
		}

		function galleries_getListForPhoto ($photo_id, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.galleries.getListForPhoto.html */
			return $this->call('flickr.galleries.getListForPhoto', array('photo_id' => $photo_id, 'per_page' => $per_page, 'page' => $page));
		}

		function galleries_getPhotos ($gallery_id, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.galleries.getPhotos.html */
			return $this->call('flickr.galleries.getPhotos', array('gallery_id' => $gallery_id, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		/* Groups Methods */
		function groups_browse ($cat_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.browse.html */
			$this->request("flickr.groups.browse", array("cat_id"=>$cat_id));
			return $this->parsed_response ? $this->parsed_response['category'] : false;
		}

		function groups_getInfo ($group_id, $lang = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.getInfo.html */
			return $this->call('flickr.groups.getInfo', array('group_id' => $group_id, 'lang' => $lang));
		}

		function groups_search ($text, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.search.html */
			$this->request("flickr.groups.search", array("text"=>$text,"per_page"=>$per_page,"page"=>$page));
			return $this->parsed_response ? $this->parsed_response['groups'] : false;
		}

		/* Groups Members Methods */
		function groups_members_getList ($group_id, $membertypes = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.members.getList.html */
			return $this->call('flickr.groups.members.getList', array('group_id' => $group_id, 'membertypes' => $membertypes, 'per_page' => $per_page, 'page' => $page));
		}

		/* Groups Pools Methods */
		function groups_pools_add ($photo_id, $group_id) {
			/* https://www.flickr.com/services/api/flickr.groups.pools.add.html */
			$this->request("flickr.groups.pools.add", array("photo_id"=>$photo_id, "group_id"=>$group_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function groups_pools_getContext ($photo_id, $group_id, $num_prev = NULL, $num_next = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.pools.getContext.html */
			return $this->call('flickr.groups.pools.getContext', array('photo_id' => $photo_id, 'group_id' => $group_id, 'num_prev' => $num_prev, 'num_next' => $num_next));
		}

		function groups_pools_getGroups ($page = NULL, $per_page = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.pools.getGroups.html */
			$this->request("flickr.groups.pools.getGroups", array('page'=>$page, 'per_page'=>$per_page));
			return $this->parsed_response ? $this->parsed_response['groups'] : false;
		}

		function groups_pools_getPhotos ($group_id, $tags = NULL, $user_id = NULL, $jump_to = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.groups.pools.getPhotos.html */
			if (is_array($extras)) {
				$extras = implode(",", $extras);
			}
			return $this->call('flickr.groups.pools.getPhotos', array('group_id' => $group_id, 'tags' => $tags, 'user_id' => $user_id, 'jump_to' => $jump_to, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function groups_pools_remove ($photo_id, $group_id) {
			/* https://www.flickr.com/services/api/flickr.groups.pools.remove.html */
			$this->request("flickr.groups.pools.remove", array("photo_id"=>$photo_id, "group_id"=>$group_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		/* Interestingness methods */
		function interestingness_getList ($date = NULL, $use_panda = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.interestingness.getList.html */
			if (is_array($extras)) {
				$extras = implode(",", $extras);
			}

			return $this->call('flickr.interestingness.getList', array('date' => $date, 'use_panda' => $use_panda, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		/* Machine Tag methods */
		function machinetags_getNamespaces ($predicate = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.machinetags.getNamespaces.html */
			return $this->call('flickr.machinetags.getNamespaces', array('predicate' => $predicate, 'per_page' => $per_page, 'page' => $page));
		}

		function machinetags_getPairs ($namespace = NULL, $predicate = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.machinetags.getPairs.html */
			return $this->call('flickr.machinetags.getPairs', array('namespace' => $namespace, 'predicate' => $predicate, 'per_page' => $per_page, 'page' => $page));
		}

		function machinetags_getPredicates ($namespace = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.machinetags.getPredicates.html */
			return $this->call('flickr.machinetags.getPredicates', array('namespace' => $namespace, 'per_page' => $per_page, 'page' => $page));
		}

		function machinetags_getRecentValues ($namespace = NULL, $predicate = NULL, $added_since = NULL) {
			/* https://www.flickr.com/services/api/flickr.machinetags.getRecentValues.html */
			return $this->call('flickr.machinetags.getRecentValues', array('namespace' => $namespace, 'predicate' => $predicate, 'added_since' => $added_since));
		}

		function machinetags_getValues ($namespace, $predicate, $per_page = NULL, $page = NULL, $usage = NULL) {
			/* https://www.flickr.com/services/api/flickr.machinetags.getValues.html */
			return $this->call('flickr.machinetags.getValues', array('namespace' => $namespace, 'predicate' => $predicate, 'per_page' => $per_page, 'page' => $page, 'usage' => $usage));
		}

		/* Panda methods */
		function panda_getList () {
			/* https://www.flickr.com/services/api/flickr.panda.getList.html */
			return $this->call('flickr.panda.getList', array());
		}

		function panda_getPhotos ($panda_name, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.panda.getPhotos.html */
			return $this->call('flickr.panda.getPhotos', array('panda_name' => $panda_name, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		/* People methods */
		function people_findByEmail ($find_email) {
			/* https://www.flickr.com/services/api/flickr.people.findByEmail.html */
			$this->request("flickr.people.findByEmail", array("find_email"=>$find_email));
			return $this->parsed_response ? $this->parsed_response['user'] : false;
		}

		function people_findByUsername ($username) {
			/* https://www.flickr.com/services/api/flickr.people.findByUsername.html */
			$this->request("flickr.people.findByUsername", array("username"=>$username));
			return $this->parsed_response ? $this->parsed_response['user'] : false;
		}

		function people_getInfo ($user_id) {
			/* https://www.flickr.com/services/api/flickr.people.getInfo.html */
			$this->request("flickr.people.getInfo", array("user_id"=>$user_id));
			return $this->parsed_response ? $this->parsed_response['person'] : false;
		}

		function people_getPhotos ($user_id, $args = array()) {
			/* This function strays from the method of arguments that I've
			 * used in the other functions for the fact that there are just
			 * so many arguments to this API method. What you'll need to do
			 * is pass an associative array to the function containing the
			 * arguments you want to pass to the API.  For example:
			 *   $photos = $f->photos_search(array("tags"=>"brown,cow", "tag_mode"=>"any"));
			 * This will return photos tagged with either "brown" or "cow"
			 * or both. See the API documentation (link below) for a full
			 * list of arguments.
			 */

			 /* https://www.flickr.com/services/api/flickr.people.getPhotos.html */
			return $this->call('flickr.people.getPhotos', array_merge(array('user_id' => $user_id), $args));
		}

		function people_getPhotosOf ($user_id, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.people.getPhotosOf.html */
			return $this->call('flickr.people.getPhotosOf', array('user_id' => $user_id, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function people_getPublicGroups ($user_id) {
			/* https://www.flickr.com/services/api/flickr.people.getPublicGroups.html */
			$this->request("flickr.people.getPublicGroups", array("user_id"=>$user_id));
			return $this->parsed_response ? $this->parsed_response['groups']['group'] : false;
		}

		function people_getPublicPhotos ($user_id, $safe_search = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.people.getPublicPhotos.html */
			return $this->call('flickr.people.getPublicPhotos', array('user_id' => $user_id, 'safe_search' => $safe_search, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function people_getUploadStatus () {
			/* https://www.flickr.com/services/api/flickr.people.getUploadStatus.html */
			/* Requires Authentication */
			$this->request("flickr.people.getUploadStatus");
			return $this->parsed_response ? $this->parsed_response['user'] : false;
		}


		/* Photos Methods */
		function photos_addTags ($photo_id, $tags) {
			/* https://www.flickr.com/services/api/flickr.photos.addTags.html */
			$this->request("flickr.photos.addTags", array("photo_id"=>$photo_id, "tags"=>$tags), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_delete ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.delete.html */
			$this->request("flickr.photos.delete", array("photo_id"=>$photo_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_getAllContexts ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.getAllContexts.html */
			$this->request("flickr.photos.getAllContexts", array("photo_id"=>$photo_id));
			return $this->parsed_response ? $this->parsed_response : false;
		}

		function photos_getContactsPhotos ($count = NULL, $just_friends = NULL, $single_photo = NULL, $include_self = NULL, $extras = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getContactsPhotos.html */
			$this->request("flickr.photos.getContactsPhotos", array("count"=>$count, "just_friends"=>$just_friends, "single_photo"=>$single_photo, "include_self"=>$include_self, "extras"=>$extras));
			return $this->parsed_response ? $this->parsed_response['photos']['photo'] : false;
		}

		function photos_getContactsPublicPhotos ($user_id, $count = NULL, $just_friends = NULL, $single_photo = NULL, $include_self = NULL, $extras = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getContactsPublicPhotos.html */
			$this->request("flickr.photos.getContactsPublicPhotos", array("user_id"=>$user_id, "count"=>$count, "just_friends"=>$just_friends, "single_photo"=>$single_photo, "include_self"=>$include_self, "extras"=>$extras));
			return $this->parsed_response ? $this->parsed_response['photos']['photo'] : false;
		}

		function photos_getContext ($photo_id, $num_prev = NULL, $num_next = NULL, $extras = NULL, $order_by = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getContext.html */
			return $this->call('flickr.photos.getContext', array('photo_id' => $photo_id, 'num_prev' => $num_prev, 'num_next' => $num_next, 'extras' => $extras, 'order_by' => $order_by));
		}

		function photos_getCounts ($dates = NULL, $taken_dates = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getCounts.html */
			$this->request("flickr.photos.getCounts", array("dates"=>$dates, "taken_dates"=>$taken_dates));
			return $this->parsed_response ? $this->parsed_response['photocounts']['photocount'] : false;
		}

		function photos_getExif ($photo_id, $secret = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getExif.html */
			$this->request("flickr.photos.getExif", array("photo_id"=>$photo_id, "secret"=>$secret));
			return $this->parsed_response ? $this->parsed_response['photo'] : false;
		}

		function photos_getFavorites ($photo_id, $page = NULL, $per_page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getFavorites.html */
			$this->request("flickr.photos.getFavorites", array("photo_id"=>$photo_id, "page"=>$page, "per_page"=>$per_page));
			return $this->parsed_response ? $this->parsed_response['photo'] : false;
		}

		function photos_getInfo ($photo_id, $secret = NULL, $humandates = NULL, $privacy_filter = NULL, $get_contexts = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getInfo.html */
			return $this->call('flickr.photos.getInfo', array('photo_id' => $photo_id, 'secret' => $secret, 'humandates' => $humandates, 'privacy_filter' => $privacy_filter, 'get_contexts' => $get_contexts));
		}

		function photos_getNotInSet ($max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL, $privacy_filter = NULL, $media = NULL, $min_upload_date = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getNotInSet.html */
			return $this->call('flickr.photos.getNotInSet', array('max_upload_date' => $max_upload_date, 'min_taken_date' => $min_taken_date, 'max_taken_date' => $max_taken_date, 'privacy_filter' => $privacy_filter, 'media' => $media, 'min_upload_date' => $min_upload_date, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function photos_getPerms ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.getPerms.html */
			$this->request("flickr.photos.getPerms", array("photo_id"=>$photo_id));
			return $this->parsed_response ? $this->parsed_response['perms'] : false;
		}

		function photos_getRecent ($jump_to = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getRecent.html */
			if (is_array($extras)) {
				$extras = implode(",", $extras);
			}
			return $this->call('flickr.photos.getRecent', array('jump_to' => $jump_to, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function photos_getSizes ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.getSizes.html */
			$this->request("flickr.photos.getSizes", array("photo_id"=>$photo_id));
			return $this->parsed_response ? $this->parsed_response['sizes']['size'] : false;
		}

		function photos_getUntagged ($min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL, $privacy_filter = NULL, $media = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.getUntagged.html */
			return $this->call('flickr.photos.getUntagged', array('min_upload_date' => $min_upload_date, 'max_upload_date' => $max_upload_date, 'min_taken_date' => $min_taken_date, 'max_taken_date' => $max_taken_date, 'privacy_filter' => $privacy_filter, 'media' => $media, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function photos_getWithGeoData ($args = array()) {
			/* See the documentation included with the photos_search() function.
			 * I'm using the same style of arguments for this function. The only
			 * difference here is that this doesn't require any arguments. The
			 * flickr.photos.search method requires at least one search parameter.
			 */
			/* https://www.flickr.com/services/api/flickr.photos.getWithGeoData.html */
			$this->request("flickr.photos.getWithGeoData", $args);
			return $this->parsed_response ? $this->parsed_response['photos'] : false;
		}

		function photos_getWithoutGeoData ($args = array()) {
			/* See the documentation included with the photos_search() function.
			 * I'm using the same style of arguments for this function. The only
			 * difference here is that this doesn't require any arguments. The
			 * flickr.photos.search method requires at least one search parameter.
			 */
			/* https://www.flickr.com/services/api/flickr.photos.getWithoutGeoData.html */
			$this->request("flickr.photos.getWithoutGeoData", $args);
			return $this->parsed_response ? $this->parsed_response['photos'] : false;
		}

		function photos_recentlyUpdated ($min_date, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.recentlyUpdated.html */
			return $this->call('flickr.photos.recentlyUpdated', array('min_date' => $min_date, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function photos_removeTag ($tag_id) {
			/* https://www.flickr.com/services/api/flickr.photos.removeTag.html */
			$this->request("flickr.photos.removeTag", array("tag_id"=>$tag_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_search ($args = array()) {
			/* This function strays from the method of arguments that I've
			 * used in the other functions for the fact that there are just
			 * so many arguments to this API method. What you'll need to do
			 * is pass an associative array to the function containing the
			 * arguments you want to pass to the API.  For example:
			 *   $photos = $f->photos_search(array("tags"=>"brown,cow", "tag_mode"=>"any"));
			 * This will return photos tagged with either "brown" or "cow"
			 * or both. See the API documentation (link below) for a full
			 * list of arguments.
			 */

			/* https://www.flickr.com/services/api/flickr.photos.search.html */
			$result = $this->request("flickr.photos.search", $args);
			return ($this->parsed_response) ? $this->parsed_response['photos'] : false;
		}

		function photos_setContentType ($photo_id, $content_type) {
			/* https://www.flickr.com/services/api/flickr.photos.setContentType.html */
			return $this->call('flickr.photos.setContentType', array('photo_id' => $photo_id, 'content_type' => $content_type));
		}

		function photos_setDates ($photo_id, $date_posted = NULL, $date_taken = NULL, $date_taken_granularity = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.setDates.html */
			$this->request("flickr.photos.setDates", array("photo_id"=>$photo_id, "date_posted"=>$date_posted, "date_taken"=>$date_taken, "date_taken_granularity"=>$date_taken_granularity), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_setMeta ($photo_id, $title, $description) {
			/* https://www.flickr.com/services/api/flickr.photos.setMeta.html */
			$this->request("flickr.photos.setMeta", array("photo_id"=>$photo_id, "title"=>$title, "description"=>$description), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_setPerms ($photo_id, $is_public, $is_friend, $is_family, $perm_comment, $perm_addmeta) {
			/* https://www.flickr.com/services/api/flickr.photos.setPerms.html */
			$this->request("flickr.photos.setPerms", array("photo_id"=>$photo_id, "is_public"=>$is_public, "is_friend"=>$is_friend, "is_family"=>$is_family, "perm_comment"=>$perm_comment, "perm_addmeta"=>$perm_addmeta), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_setSafetyLevel ($photo_id, $safety_level = NULL, $hidden = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.setSafetyLevel.html */
			return $this->call('flickr.photos.setSafetyLevel', array('photo_id' => $photo_id, 'safety_level' => $safety_level, 'hidden' => $hidden));
		}

		function photos_setTags ($photo_id, $tags) {
			/* https://www.flickr.com/services/api/flickr.photos.setTags.html */
			$this->request("flickr.photos.setTags", array("photo_id"=>$photo_id, "tags"=>$tags), TRUE);
			return $this->parsed_response ? true : false;
		}

		/* Photos - Comments Methods */
		function photos_comments_addComment ($photo_id, $comment_text) {
			/* https://www.flickr.com/services/api/flickr.photos.comments.addComment.html */
			$this->request("flickr.photos.comments.addComment", array("photo_id" => $photo_id, "comment_text"=>$comment_text), TRUE);
			return $this->parsed_response ? $this->parsed_response['comment'] : false;
		}

		function photos_comments_deleteComment ($comment_id) {
			/* https://www.flickr.com/services/api/flickr.photos.comments.deleteComment.html */
			$this->request("flickr.photos.comments.deleteComment", array("comment_id" => $comment_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_comments_editComment ($comment_id, $comment_text) {
			/* https://www.flickr.com/services/api/flickr.photos.comments.editComment.html */
			$this->request("flickr.photos.comments.editComment", array("comment_id" => $comment_id, "comment_text"=>$comment_text), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_comments_getList ($photo_id, $min_comment_date = NULL, $max_comment_date = NULL, $page = NULL, $per_page = NULL, $include_faves = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.comments.getList.html */
			return $this->call('flickr.photos.comments.getList', array('photo_id' => $photo_id, 'min_comment_date' => $min_comment_date, 'max_comment_date' => $max_comment_date, 'page' => $page, 'per_page' => $per_page, 'include_faves' => $include_faves));
		}

		function photos_comments_getRecentForContacts ($date_lastcomment = NULL, $contacts_filter = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.comments.getRecentForContacts.html */
			return $this->call('flickr.photos.comments.getRecentForContacts', array('date_lastcomment' => $date_lastcomment, 'contacts_filter' => $contacts_filter, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		/* Photos - Geo Methods */
		function photos_geo_batchCorrectLocation ($lat, $lon, $accuracy, $place_id = NULL, $woe_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.batchCorrectLocation.html */
			return $this->call('flickr.photos.geo.batchCorrectLocation', array('lat' => $lat, 'lon' => $lon, 'accuracy' => $accuracy, 'place_id' => $place_id, 'woe_id' => $woe_id));
		}

		function photos_geo_correctLocation ($photo_id, $place_id = NULL, $woe_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.correctLocation.html */
			return $this->call('flickr.photos.geo.correctLocation', array('photo_id' => $photo_id, 'place_id' => $place_id, 'woe_id' => $woe_id));
		}

		function photos_geo_getLocation ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.getLocation.html */
			$this->request("flickr.photos.geo.getLocation", array("photo_id"=>$photo_id));
			return $this->parsed_response ? $this->parsed_response['photo'] : false;
		}

		function photos_geo_getPerms ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.getPerms.html */
			$this->request("flickr.photos.geo.getPerms", array("photo_id"=>$photo_id));
			return $this->parsed_response ? $this->parsed_response['perms'] : false;
		}

		function photos_geo_photosForLocation ($lat, $lon, $accuracy = NULL, $extras = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.photosForLocation.html */
			return $this->call('flickr.photos.geo.photosForLocation', array('lat' => $lat, 'lon' => $lon, 'accuracy' => $accuracy, 'extras' => $extras, 'per_page' => $per_page, 'page' => $page));
		}

		function photos_geo_removeLocation ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.removeLocation.html */
			$this->request("flickr.photos.geo.removeLocation", array("photo_id"=>$photo_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_geo_setContext ($photo_id, $context) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.setContext.html */
			return $this->call('flickr.photos.geo.setContext', array('photo_id' => $photo_id, 'context' => $context));
		}

		function photos_geo_setLocation ($photo_id, $lat, $lon, $accuracy = NULL, $context = NULL, $bookmark_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.setLocation.html */
			return $this->call('flickr.photos.geo.setLocation', array('photo_id' => $photo_id, 'lat' => $lat, 'lon' => $lon, 'accuracy' => $accuracy, 'context' => $context, 'bookmark_id' => $bookmark_id));
		}

		function photos_geo_setPerms ($is_public, $is_contact, $is_friend, $is_family, $photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.geo.setPerms.html */
			return $this->call('flickr.photos.geo.setPerms', array('is_public' => $is_public, 'is_contact' => $is_contact, 'is_friend' => $is_friend, 'is_family' => $is_family, 'photo_id' => $photo_id));
		}

		/* Photos - Licenses Methods */
		function photos_licenses_getInfo () {
			/* https://www.flickr.com/services/api/flickr.photos.licenses.getInfo.html */
			$this->request("flickr.photos.licenses.getInfo");
			return $this->parsed_response ? $this->parsed_response['licenses']['license'] : false;
		}

		function photos_licenses_setLicense ($photo_id, $license_id) {
			/* https://www.flickr.com/services/api/flickr.photos.licenses.setLicense.html */
			/* Requires Authentication */
			$this->request("flickr.photos.licenses.setLicense", array("photo_id"=>$photo_id, "license_id"=>$license_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		/* Photos - Notes Methods */
		function photos_notes_add ($photo_id, $note_x, $note_y, $note_w, $note_h, $note_text) {
			/* https://www.flickr.com/services/api/flickr.photos.notes.add.html */
			$this->request("flickr.photos.notes.add", array("photo_id" => $photo_id, "note_x" => $note_x, "note_y" => $note_y, "note_w" => $note_w, "note_h" => $note_h, "note_text" => $note_text), TRUE);
			return $this->parsed_response ? $this->parsed_response['note'] : false;
		}

		function photos_notes_delete ($note_id) {
			/* https://www.flickr.com/services/api/flickr.photos.notes.delete.html */
			$this->request("flickr.photos.notes.delete", array("note_id" => $note_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photos_notes_edit ($note_id, $note_x, $note_y, $note_w, $note_h, $note_text) {
			/* https://www.flickr.com/services/api/flickr.photos.notes.edit.html */
			$this->request("flickr.photos.notes.edit", array("note_id" => $note_id, "note_x" => $note_x, "note_y" => $note_y, "note_w" => $note_w, "note_h" => $note_h, "note_text" => $note_text), TRUE);
			return $this->parsed_response ? true : false;
		}

		/* Photos - Transform Methods */
		function photos_transform_rotate ($photo_id, $degrees) {
			/* https://www.flickr.com/services/api/flickr.photos.transform.rotate.html */
			$this->request("flickr.photos.transform.rotate", array("photo_id" => $photo_id, "degrees" => $degrees), TRUE);
			return $this->parsed_response ? true : false;
		}

		/* Photos - People Methods */
		function photos_people_add ($photo_id, $user_id, $person_x = NULL, $person_y = NULL, $person_w = NULL, $person_h = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.people.add.html */
			return $this->call('flickr.photos.people.add', array('photo_id' => $photo_id, 'user_id' => $user_id, 'person_x' => $person_x, 'person_y' => $person_y, 'person_w' => $person_w, 'person_h' => $person_h));
		}

		function photos_people_delete ($photo_id, $user_id, $email = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.people.delete.html */
			return $this->call('flickr.photos.people.delete', array('photo_id' => $photo_id, 'user_id' => $user_id, 'email' => $email));
		}

		function photos_people_deleteCoords ($photo_id, $user_id) {
			/* https://www.flickr.com/services/api/flickr.photos.people.deleteCoords.html */
			return $this->call('flickr.photos.people.deleteCoords', array('photo_id' => $photo_id, 'user_id' => $user_id));
		}

		function photos_people_editCoords ($photo_id, $user_id, $person_x, $person_y, $person_w, $person_h, $email = NULL) {
			/* https://www.flickr.com/services/api/flickr.photos.people.editCoords.html */
			return $this->call('flickr.photos.people.editCoords', array('photo_id' => $photo_id, 'user_id' => $user_id, 'person_x' => $person_x, 'person_y' => $person_y, 'person_w' => $person_w, 'person_h' => $person_h, 'email' => $email));
		}

		function photos_people_getList ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.photos.people.getList.html */
			return $this->call('flickr.photos.people.getList', array('photo_id' => $photo_id));
		}

		/* Photos - Upload Methods */
		function photos_upload_checkTickets ($tickets) {
			/* https://www.flickr.com/services/api/flickr.photos.upload.checkTickets.html */
			if (is_array($tickets)) {
				$tickets = implode(",", $tickets);
			}
			$this->request("flickr.photos.upload.checkTickets", array("tickets" => $tickets), TRUE);
			return $this->parsed_response ? $this->parsed_response['uploader']['ticket'] : false;
		}

		/* Photosets Methods */
		function photosets_addPhoto ($photoset_id, $photo_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.addPhoto.html */
			$this->request("flickr.photosets.addPhoto", array("photoset_id" => $photoset_id, "photo_id" => $photo_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_create ($title, $description, $primary_photo_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.create.html */
			$this->request("flickr.photosets.create", array("title" => $title, "primary_photo_id" => $primary_photo_id, "description" => $description), TRUE);
			return $this->parsed_response ? $this->parsed_response['photoset'] : false;
		}

		function photosets_delete ($photoset_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.delete.html */
			$this->request("flickr.photosets.delete", array("photoset_id" => $photoset_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_editMeta ($photoset_id, $title, $description = NULL) {
			/* https://www.flickr.com/services/api/flickr.photosets.editMeta.html */
			$this->request("flickr.photosets.editMeta", array("photoset_id" => $photoset_id, "title" => $title, "description" => $description), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_editPhotos ($photoset_id, $primary_photo_id, $photo_ids) {
			/* https://www.flickr.com/services/api/flickr.photosets.editPhotos.html */
			$this->request("flickr.photosets.editPhotos", array("photoset_id" => $photoset_id, "primary_photo_id" => $primary_photo_id, "photo_ids" => $photo_ids), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_getContext ($photo_id, $photoset_id, $num_prev = NULL, $num_next = NULL) {
			/* https://www.flickr.com/services/api/flickr.photosets.getContext.html */
			return $this->call('flickr.photosets.getContext', array('photo_id' => $photo_id, 'photoset_id' => $photoset_id, 'num_prev' => $num_prev, 'num_next' => $num_next));
		}

		function photosets_getInfo ($photoset_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.getInfo.html */
			$this->request("flickr.photosets.getInfo", array("photoset_id" => $photoset_id));
			return $this->parsed_response ? $this->parsed_response['photoset'] : false;
		}

		function photosets_getList ($user_id = NULL, $page = NULL, $per_page = NULL, $primary_photo_extras = NULL) {
			/* https://www.flickr.com/services/api/flickr.photosets.getList.html */
			$this->request("flickr.photosets.getList", array("user_id" => $user_id, 'page' => $page, 'per_page' => $per_page, 'primary_photo_extras' => $primary_photo_extras));
			return $this->parsed_response ? $this->parsed_response['photosets'] : false;
		}

		function photosets_getPhotos ($photoset_id, $extras = NULL, $privacy_filter = NULL, $per_page = NULL, $page = NULL, $media = NULL) {
			/* https://www.flickr.com/services/api/flickr.photosets.getPhotos.html */
			return $this->call('flickr.photosets.getPhotos', array('photoset_id' => $photoset_id, 'extras' => $extras, 'privacy_filter' => $privacy_filter, 'per_page' => $per_page, 'page' => $page, 'media' => $media));
		}

		function photosets_orderSets ($photoset_ids) {
			/* https://www.flickr.com/services/api/flickr.photosets.orderSets.html */
			if (is_array($photoset_ids)) {
				$photoset_ids = implode(",", $photoset_ids);
			}
			$this->request("flickr.photosets.orderSets", array("photoset_ids" => $photoset_ids), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_removePhoto ($photoset_id, $photo_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.removePhoto.html */
			$this->request("flickr.photosets.removePhoto", array("photoset_id" => $photoset_id, "photo_id" => $photo_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_removePhotos ($photoset_id, $photo_ids) {
			/* https://www.flickr.com/services/api/flickr.photosets.removePhotos.html */
			return $this->call('flickr.photosets.removePhotos', array('photoset_id' => $photoset_id, 'photo_ids' => $photo_ids));
		}

		function photosets_reorderPhotos ($photoset_id, $photo_ids) {
			/* https://www.flickr.com/services/api/flickr.photosets.reorderPhotos.html */
			return $this->call('flickr.photosets.reorderPhotos', array('photoset_id' => $photoset_id, 'photo_ids' => $photo_ids));
		}

		function photosets_setPrimaryPhoto ($photoset_id, $photo_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.setPrimaryPhoto.html */
			return $this->call('flickr.photosets.setPrimaryPhoto', array('photoset_id' => $photoset_id, 'photo_id' => $photo_id));
		}

		/* Photosets Comments Methods */
		function photosets_comments_addComment ($photoset_id, $comment_text) {
			/* https://www.flickr.com/services/api/flickr.photosets.comments.addComment.html */
			$this->request("flickr.photosets.comments.addComment", array("photoset_id" => $photoset_id, "comment_text"=>$comment_text), TRUE);
			return $this->parsed_response ? $this->parsed_response['comment'] : false;
		}

		function photosets_comments_deleteComment ($comment_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.comments.deleteComment.html */
			$this->request("flickr.photosets.comments.deleteComment", array("comment_id" => $comment_id), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_comments_editComment ($comment_id, $comment_text) {
			/* https://www.flickr.com/services/api/flickr.photosets.comments.editComment.html */
			$this->request("flickr.photosets.comments.editComment", array("comment_id" => $comment_id, "comment_text"=>$comment_text), TRUE);
			return $this->parsed_response ? true : false;
		}

		function photosets_comments_getList ($photoset_id) {
			/* https://www.flickr.com/services/api/flickr.photosets.comments.getList.html */
			$this->request("flickr.photosets.comments.getList", array("photoset_id"=>$photoset_id));
			return $this->parsed_response ? $this->parsed_response['comments'] : false;
		}

		/* Places Methods */
		function places_find ($query) {
			/* https://www.flickr.com/services/api/flickr.places.find.html */
			return $this->call('flickr.places.find', array('query' => $query));
		}

		function places_findByLatLon ($lat, $lon, $accuracy = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.findByLatLon.html */
			return $this->call('flickr.places.findByLatLon', array('lat' => $lat, 'lon' => $lon, 'accuracy' => $accuracy));
		}

		function places_getChildrenWithPhotosPublic ($place_id = NULL, $woe_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.getChildrenWithPhotosPublic.html */
			return $this->call('flickr.places.getChildrenWithPhotosPublic', array('place_id' => $place_id, 'woe_id' => $woe_id));
		}

		function places_getInfo ($place_id = NULL, $woe_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.getInfo.html */
			return $this->call('flickr.places.getInfo', array('place_id' => $place_id, 'woe_id' => $woe_id));
		}

		function places_getInfoByUrl ($url) {
			/* https://www.flickr.com/services/api/flickr.places.getInfoByUrl.html */
			return $this->call('flickr.places.getInfoByUrl', array('url' => $url));
		}

		function places_getPlaceTypes () {
			/* https://www.flickr.com/services/api/flickr.places.getPlaceTypes.html */
			return $this->call('flickr.places.getPlaceTypes', array());
		}

		function places_getShapeHistory ($place_id = NULL, $woe_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.getShapeHistory.html */
			return $this->call('flickr.places.getShapeHistory', array('place_id' => $place_id, 'woe_id' => $woe_id));
		}

		function places_getTopPlacesList ($place_type_id, $date = NULL, $woe_id = NULL, $place_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.getTopPlacesList.html */
			return $this->call('flickr.places.getTopPlacesList', array('place_type_id' => $place_type_id, 'date' => $date, 'woe_id' => $woe_id, 'place_id' => $place_id));
		}

		function places_placesForBoundingBox ($bbox, $place_type = NULL, $place_type_id = NULL, $recursive = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.placesForBoundingBox.html */
			return $this->call('flickr.places.placesForBoundingBox', array('bbox' => $bbox, 'place_type' => $place_type, 'place_type_id' => $place_type_id, 'recursive' => $recursive));
		}

		function places_placesForContacts ($place_type = NULL, $place_type_id = NULL, $woe_id = NULL, $place_id = NULL, $threshold = NULL, $contacts = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.placesForContacts.html */
			return $this->call('flickr.places.placesForContacts', array('place_type' => $place_type, 'place_type_id' => $place_type_id, 'woe_id' => $woe_id, 'place_id' => $place_id, 'threshold' => $threshold, 'contacts' => $contacts, 'min_upload_date' => $min_upload_date, 'max_upload_date' => $max_upload_date, 'min_taken_date' => $min_taken_date, 'max_taken_date' => $max_taken_date));
		}

		function places_placesForTags ($place_type_id, $woe_id = NULL, $place_id = NULL, $threshold = NULL, $tags = NULL, $tag_mode = NULL, $machine_tags = NULL, $machine_tag_mode = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.placesForTags.html */
			return $this->call('flickr.places.placesForTags', array('place_type_id' => $place_type_id, 'woe_id' => $woe_id, 'place_id' => $place_id, 'threshold' => $threshold, 'tags' => $tags, 'tag_mode' => $tag_mode, 'machine_tags' => $machine_tags, 'machine_tag_mode' => $machine_tag_mode, 'min_upload_date' => $min_upload_date, 'max_upload_date' => $max_upload_date, 'min_taken_date' => $min_taken_date, 'max_taken_date' => $max_taken_date));
		}

		function places_placesForUser ($place_type_id = NULL, $place_type = NULL, $woe_id = NULL, $place_id = NULL, $threshold = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.placesForUser.html */
			return $this->call('flickr.places.placesForUser', array('place_type_id' => $place_type_id, 'place_type' => $place_type, 'woe_id' => $woe_id, 'place_id' => $place_id, 'threshold' => $threshold, 'min_upload_date' => $min_upload_date, 'max_upload_date' => $max_upload_date, 'min_taken_date' => $min_taken_date, 'max_taken_date' => $max_taken_date));
		}

		function places_resolvePlaceId ($place_id) {
			/* https://www.flickr.com/services/api/flickr.places.resolvePlaceId.html */
			$rsp = $this->call('flickr.places.resolvePlaceId', array('place_id' => $place_id));
			return $rsp ? $rsp['location'] : $rsp;
		}

		function places_resolvePlaceURL ($url) {
			/* https://www.flickr.com/services/api/flickr.places.resolvePlaceURL.html */
			$rsp = $this->call('flickr.places.resolvePlaceURL', array('url' => $url));
			return $rsp ? $rsp['location'] : $rsp;
		}

		function places_tagsForPlace ($woe_id = NULL, $place_id = NULL, $min_upload_date = NULL, $max_upload_date = NULL, $min_taken_date = NULL, $max_taken_date = NULL) {
			/* https://www.flickr.com/services/api/flickr.places.tagsForPlace.html */
			return $this->call('flickr.places.tagsForPlace', array('woe_id' => $woe_id, 'place_id' => $place_id, 'min_upload_date' => $min_upload_date, 'max_upload_date' => $max_upload_date, 'min_taken_date' => $min_taken_date, 'max_taken_date' => $max_taken_date));
		}

		/* Prefs Methods */
		function prefs_getContentType () {
			/* https://www.flickr.com/services/api/flickr.prefs.getContentType.html */
			$rsp = $this->call('flickr.prefs.getContentType', array());
			return $rsp ? $rsp['person'] : $rsp;
		}

		function prefs_getGeoPerms () {
			/* https://www.flickr.com/services/api/flickr.prefs.getGeoPerms.html */
			return $this->call('flickr.prefs.getGeoPerms', array());
		}

		function prefs_getHidden () {
			/* https://www.flickr.com/services/api/flickr.prefs.getHidden.html */
			$rsp = $this->call('flickr.prefs.getHidden', array());
			return $rsp ? $rsp['person'] : $rsp;
		}

		function prefs_getPrivacy () {
			/* https://www.flickr.com/services/api/flickr.prefs.getPrivacy.html */
			$rsp = $this->call('flickr.prefs.getPrivacy', array());
			return $rsp ? $rsp['person'] : $rsp;
		}

		function prefs_getSafetyLevel () {
			/* https://www.flickr.com/services/api/flickr.prefs.getSafetyLevel.html */
			$rsp = $this->call('flickr.prefs.getSafetyLevel', array());
			return $rsp ? $rsp['person'] : $rsp;
		}

		/* Reflection Methods */
		function reflection_getMethodInfo ($method_name) {
			/* https://www.flickr.com/services/api/flickr.reflection.getMethodInfo.html */
			$this->request("flickr.reflection.getMethodInfo", array("method_name" => $method_name));
			return $this->parsed_response ? $this->parsed_response : false;
		}

		function reflection_getMethods () {
			/* https://www.flickr.com/services/api/flickr.reflection.getMethods.html */
			$this->request("flickr.reflection.getMethods");
			return $this->parsed_response ? $this->parsed_response['methods']['method'] : false;
		}

		/* Stats Methods */
		function stats_getCollectionDomains ($date, $collection_id = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getCollectionDomains.html */
			return $this->call('flickr.stats.getCollectionDomains', array('date' => $date, 'collection_id' => $collection_id, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getCollectionReferrers ($date, $domain, $collection_id = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getCollectionReferrers.html */
			return $this->call('flickr.stats.getCollectionReferrers', array('date' => $date, 'domain' => $domain, 'collection_id' => $collection_id, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getCollectionStats ($date, $collection_id) {
			/* https://www.flickr.com/services/api/flickr.stats.getCollectionStats.html */
			return $this->call('flickr.stats.getCollectionStats', array('date' => $date, 'collection_id' => $collection_id));
		}

		function stats_getCSVFiles () {
			/* https://www.flickr.com/services/api/flickr.stats.getCSVFiles.html */
			return $this->call('flickr.stats.getCSVFiles', array());
		}

		function stats_getPhotoDomains ($date, $photo_id = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotoDomains.html */
			return $this->call('flickr.stats.getPhotoDomains', array('date' => $date, 'photo_id' => $photo_id, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getPhotoReferrers ($date, $domain, $photo_id = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotoReferrers.html */
			return $this->call('flickr.stats.getPhotoReferrers', array('date' => $date, 'domain' => $domain, 'photo_id' => $photo_id, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getPhotosetDomains ($date, $photoset_id = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotosetDomains.html */
			return $this->call('flickr.stats.getPhotosetDomains', array('date' => $date, 'photoset_id' => $photoset_id, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getPhotosetReferrers ($date, $domain, $photoset_id = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotosetReferrers.html */
			return $this->call('flickr.stats.getPhotosetReferrers', array('date' => $date, 'domain' => $domain, 'photoset_id' => $photoset_id, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getPhotosetStats ($date, $photoset_id) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotosetStats.html */
			return $this->call('flickr.stats.getPhotosetStats', array('date' => $date, 'photoset_id' => $photoset_id));
		}

		function stats_getPhotoStats ($date, $photo_id) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotoStats.html */
			return $this->call('flickr.stats.getPhotoStats', array('date' => $date, 'photo_id' => $photo_id));
		}

		function stats_getPhotostreamDomains ($date, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotostreamDomains.html */
			return $this->call('flickr.stats.getPhotostreamDomains', array('date' => $date, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getPhotostreamReferrers ($date, $domain, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotostreamReferrers.html */
			return $this->call('flickr.stats.getPhotostreamReferrers', array('date' => $date, 'domain' => $domain, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getPhotostreamStats ($date) {
			/* https://www.flickr.com/services/api/flickr.stats.getPhotostreamStats.html */
			return $this->call('flickr.stats.getPhotostreamStats', array('date' => $date));
		}

		function stats_getPopularPhotos ($date = NULL, $sort = NULL, $per_page = NULL, $page = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getPopularPhotos.html */
			return $this->call('flickr.stats.getPopularPhotos', array('date' => $date, 'sort' => $sort, 'per_page' => $per_page, 'page' => $page));
		}

		function stats_getTotalViews ($date = NULL) {
			/* https://www.flickr.com/services/api/flickr.stats.getTotalViews.html */
			return $this->call('flickr.stats.getTotalViews', array('date' => $date));
		}

		/* Tags Methods */
		function tags_getClusterPhotos ($tag, $cluster_id) {
			/* https://www.flickr.com/services/api/flickr.tags.getClusterPhotos.html */
			return $this->call('flickr.tags.getClusterPhotos', array('tag' => $tag, 'cluster_id' => $cluster_id));
		}

		function tags_getClusters ($tag) {
			/* https://www.flickr.com/services/api/flickr.tags.getClusters.html */
			return $this->call('flickr.tags.getClusters', array('tag' => $tag));
		}

		function tags_getHotList ($period = NULL, $count = NULL) {
			/* https://www.flickr.com/services/api/flickr.tags.getHotList.html */
			$this->request("flickr.tags.getHotList", array("period" => $period, "count" => $count));
			return $this->parsed_response ? $this->parsed_response['hottags'] : false;
		}

		function tags_getListPhoto ($photo_id) {
			/* https://www.flickr.com/services/api/flickr.tags.getListPhoto.html */
			$this->request("flickr.tags.getListPhoto", array("photo_id" => $photo_id));
			return $this->parsed_response ? $this->parsed_response['photo']['tags']['tag'] : false;
		}

		function tags_getListUser ($user_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.tags.getListUser.html */
			$this->request("flickr.tags.getListUser", array("user_id" => $user_id));
			return $this->parsed_response ? $this->parsed_response['who']['tags']['tag'] : false;
		}

		function tags_getListUserPopular ($user_id = NULL, $count = NULL) {
			/* https://www.flickr.com/services/api/flickr.tags.getListUserPopular.html */
			$this->request("flickr.tags.getListUserPopular", array("user_id" => $user_id, "count" => $count));
			return $this->parsed_response ? $this->parsed_response['who']['tags']['tag'] : false;
		}

		function tags_getListUserRaw ($tag = NULL) {
			/* https://www.flickr.com/services/api/flickr.tags.getListUserRaw.html */
			return $this->call('flickr.tags.getListUserRaw', array('tag' => $tag));
		}

		function tags_getRelated ($tag) {
			/* https://www.flickr.com/services/api/flickr.tags.getRelated.html */
			$this->request("flickr.tags.getRelated", array("tag" => $tag));
			return $this->parsed_response ? $this->parsed_response['tags'] : false;
		}

		function test_echo ($args = array()) {
			/* https://www.flickr.com/services/api/flickr.test.echo.html */
			$this->request("flickr.test.echo", $args);
			return $this->parsed_response ? $this->parsed_response : false;
		}

		function test_login () {
			/* https://www.flickr.com/services/api/flickr.test.login.html */
			$this->request("flickr.test.login");
			return $this->parsed_response ? $this->parsed_response['user'] : false;
		}

		function urls_getGroup ($group_id) {
			/* https://www.flickr.com/services/api/flickr.urls.getGroup.html */
			$this->request("flickr.urls.getGroup", array("group_id"=>$group_id));
			return $this->parsed_response ? $this->parsed_response['group']['url'] : false;
		}

		function urls_getUserPhotos ($user_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.urls.getUserPhotos.html */
			$this->request("flickr.urls.getUserPhotos", array("user_id"=>$user_id));
			return $this->parsed_response ? $this->parsed_response['user']['url'] : false;
		}

		function urls_getUserProfile ($user_id = NULL) {
			/* https://www.flickr.com/services/api/flickr.urls.getUserProfile.html */
			$this->request("flickr.urls.getUserProfile", array("user_id"=>$user_id));
			return $this->parsed_response ? $this->parsed_response['user']['url'] : false;
		}

		function urls_lookupGallery ($url) {
			/* https://www.flickr.com/services/api/flickr.urls.lookupGallery.html */
			return $this->call('flickr.urls.lookupGallery', array('url' => $url));
		}

		function urls_lookupGroup ($url) {
			/* https://www.flickr.com/services/api/flickr.urls.lookupGroup.html */
			$this->request("flickr.urls.lookupGroup", array("url"=>$url));
			return $this->parsed_response ? $this->parsed_response['group'] : false;
		}

		function urls_lookupUser ($url) {
			/* https://www.flickr.com/services/api/flickr.photos.notes.edit.html */
			$this->request("flickr.urls.lookupUser", array("url"=>$url));
			return $this->parsed_response ? $this->parsed_response['user'] : false;
		}
	}

	class phpFlickr_pager {
		var $phpFlickr, $per_page, $method, $args, $results, $global_phpFlickr;
		var $total = null, $page = 0, $pages = null, $photos, $_extra = null;


		function phpFlickr_pager($phpFlickr, $method = null, $args = null, $per_page = 30) {
			$this->per_page = $per_page;
			$this->method = $method;
			$this->args = $args;
			$this->set_phpFlickr($phpFlickr);
		}

		function set_phpFlickr($phpFlickr) {
			if ( is_a($phpFlickr, 'phpFlickr') ) {
				$this->phpFlickr = $phpFlickr;
				if ( $this->phpFlickr->cache ) {
					$this->args['per_page'] = 500;
				} else {
					$this->args['per_page'] = (int) $this->per_page;
				}
			}
		}

		function __sleep() {
			return array(
				'method',
				'args',
				'per_page',
				'page',
				'_extra',
			);
		}

		function load($page) {
			$allowed_methods = array(
				'flickr.photos.search' => 'photos',
				'flickr.photosets.getPhotos' => 'photoset',
			);
			if ( !in_array($this->method, array_keys($allowed_methods)) ) return false;

			if ( $this->phpFlickr->cache ) {
				$min = ($page - 1) * $this->per_page;
				$max = $page * $this->per_page - 1;
				if ( floor($min/500) == floor($max/500) ) {
					$this->args['page'] = floor($min/500) + 1;
					$this->results = $this->phpFlickr->call($this->method, $this->args);
					if ( $this->results ) {
						$this->results = $this->results[$allowed_methods[$this->method]];
						$this->photos = array_slice($this->results['photo'], $min % 500, $this->per_page);
						$this->total = $this->results['total'];
						$this->pages = ceil($this->results['total'] / $this->per_page);
						return true;
					} else {
						return false;
					}
				} else {
					$this->args['page'] = floor($min/500) + 1;
					$this->results = $this->phpFlickr->call($this->method, $this->args);
					if ( $this->results ) {
						$this->results = $this->results[$allowed_methods[$this->method]];

						$this->photos = array_slice($this->results['photo'], $min % 500);
						$this->total = $this->results['total'];
						$this->pages = ceil($this->results['total'] / $this->per_page);

						$this->args['page'] = floor($min/500) + 2;
						$this->results = $this->phpFlickr->call($this->method, $this->args);
						if ( $this->results ) {
							$this->results = $this->results[$allowed_methods[$this->method]];
							$this->photos = array_merge($this->photos, array_slice($this->results['photo'], 0, $max % 500 + 1));
						}
						return true;
					} else {
						return false;
					}

				}
			} else {
				$this->args['page'] = $page;
				$this->results = $this->phpFlickr->call($this->method, $this->args);
				if ( $this->results ) {
					$this->results = $this->results[$allowed_methods[$this->method]];

					$this->photos = $this->results['photo'];
					$this->total = $this->results['total'];
					$this->pages = $this->results['pages'];
					return true;
				} else {
					return false;
				}
			}
		}

		function get($page = null) {
			if ( is_null($page) ) {
				$page = $this->page;
			} else {
				$this->page = $page;
			}
			if ( $this->load($page) ) {
				return $this->photos;
			}
			$this->total = 0;
			$this->pages = 0;
			return array();
		}

		function next() {
			$this->page++;
			if ( $this->load($this->page) ) {
				return $this->photos;
			}
			$this->total = 0;
			$this->pages = 0;
			return array();
		}

	}
