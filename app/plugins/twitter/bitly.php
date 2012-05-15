<?php

/**
 * bitly
 * 
 * For more information on this file and how to use the class please visit
 * http://www.hashbangcode.com/blog/php-class-to-interact-with-bit-ly-api-1315.html
 *
 * Changes in this version:
 * 1. Corrected incorrect comments on some functions.
 *
 * @author 	  Philip Norton
 * @version   1.1
 * @copyright 2009 #! code
 * 
 */
 
/**
 * This class provides a set of functions that replicate the bit.ly API.
 * In order to interface with bit.ly a login and API key are needed. The
 * functions will always create an array containing the data returned
 * from the bit.ly service, but different functions will return the most 
 * important information. For more information about the bitly API have
 * a look at http://code.google.com/p/bitly-api/wiki/ApiDocumentation
 *
 * @package    bitly
 */
class bitly{

	/**
	 * The login used for the API connection.
	 *
	 * @var string
	 */
	private $login;

	/**
	 * The API key used for the API connection.
	 *
	 * @var string
	 */
	private $apikey;

	/**
	 * All API calls require a version parameter.
	 *
	 * @var string
	 */
	private $version = '2.0.1';
	
	/**
	 * The format that the data is returned in. JSON is the
	 * default, XML is also available.
	 *
	 * @var string
	 */
	private $format = 'json';
	
	/**
	 * The raw data returned from bit.ly.
	 *
	 * @var array
	 */
	private $results;

	/**
	 * The any error messages returned from bit.ly.
	 *
	 * @var mixed
	 */
	private $errors = false;
	
	/**
     * Constructor 
     *
	 * @param string $login  The login to use for the connection.
	 * @param string $apikey The API key to use for the connection.
     */
	public function bitly($login, $apikey)
	{
		$this->login = $login;
		$this->apikey = $apikey;
	}
	
	/**
     * Set the format of the data being returned, this can be in json or xml.
	 *
	 * @param string $format The format.
     */
	public function setFormat($format)
	{	
		$format = strtolower($format);
		if ( $format == 'json' || $format == 'xml' ) {
			$this->format = strtolower($format);
		} else {
			return 'Invalid format specified!';
		}
	}
	
	/**
     * Get the format of the returned data.
     *
	 * @return string The format of the returned data.
     */
	public function getFormat()
	{
		return $this->format;
	}

	/**
     * Get the latest errors.
     *
	 * @return array An array containing the latest errors.
     */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
     * Get the latest results from the bit.ly service.
     *
	 * @return array An array containing the bit.ly results.
     */
	public function getRawResults()
	{
		return $this->results;
	}

	/**
     * Shorten a URL using the bit.ly service.
	 *
	 * @param string  $url        The URL to shorten.
	 * @param boolean $returnHash Boolean value to make the function return an array 
	 *							  containing the shortened URL and a hash.
	 *
	 * @return mixed Either the shortened URL or an array containing the shortened URL 
	 *	             and the hash value returned from the site.
     */
	public function shorten($url, $returnHash = false)
	{
		$bitlyurl = 'http://api.bit.ly/shorten?version='.$this->version.'&longUrl='.$url.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		if ( $this->getResult($bitlyurl) !== false ) {
			if ( $returnHash == true ) {
				return array('shortUrl' => $this->results['shortUrl'], 'hash' => $this->results['hash']);
			} else {
				return $this->results['shortUrl'];
			}
		}
		return false;
	}	

	/**
     * Expand a URL that has been shortened using the bit.ly service.
	 *
	 * @param string $url  The URL to expand.
	 * @param string $hash The hash value to be translated into a long URL.
	 *
	 * @return string The long URL, as translated by the bit.ly service.
     */
	public function expand($shortUrl, $hash = '')
	{
		if ( $shortUrl != '' ) {
			$bitlyurl = 'http://api.bit.ly/expand?version='.$this->version.'&shortUrl='.$shortUrl.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		}
		if ( $shortUrl == '' && $hash != '' ) {
			$bitlyurl = 'http://api.bit.ly/expand?version='.$this->version.'&hash='.$hash.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		}
		if ( $this->getResult($bitlyurl) !== false ) {
			return $this->results['longUrl'];
		}
		return false;
	}

	/**
     * Find out information about the URL.
	 *
	 * @param string $url  The shortened URL.
	 * @param string $hash The hash value from the bit.ly service.
	 * @param string $keys This will cause the bit.ly service to return only this data item.
	 *
	 * @return array The information about a URL from the bit.ly service.
     */
	public function info($shortUrl, $hash = '', $keys = '')
	{
		if ( $shortUrl != '' ) {
			$bitlyurl = 'http://api.bit.ly/info?version='.$this->version.'&shortUrl='.$shortUrl.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		}
		if ( $shortUrl == '' && $hash != '' ) {
			$bitlyurl = 'http://api.bit.ly/info?version='.$this->version.'&hash='.$hash.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		}
		if ( $keys != '' ) {
			$bitlyurl .= '&keys='.$keys;
		}
		if ( $this->getResult($shortUrl) !== false ) {
			return $this->results;
		}
		return false;
	}

	/**
     * Find out statistics about the URL. 
	 *
	 * @param string $url  The shortened URL.
	 * @param string $hash The hash value from the bit.ly service.
	 *
	 * @return array An array containing statistics about the URL in question.
     */
	public function stats($shortUrl, $hash = '')
	{
		if ( $shortUrl != '' ) {
			$bitlyurl = 'http://api.bit.ly/stats?version='.$this->version.'&shortUrl='.$shortUrl.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		}
		if ( $shortUrl == '' && $hash != '' ) {
			$bitlyurl = 'http://api.bit.ly/stats?version='.$this->version.'&hash='.$hash.'&login='.$this->login.'&apiKey='.$this->apikey.'&format='.$this->format;
		}
		if ( $this->getResult($bitlyurl) !== false ) {
			return $this->results;
		}
		return false;
	}

	/**
     * Return a list of all error codes from the bit.ly service.
	 *
	 * @return array An array containing all possible error codes.
     */
	public function errors()
	{
		$bitlyurl = 'http://api.bit.ly/errors?version='.$this->version.'&login='.$this->login.'&apiKey='.$this->apikey;
		if ( $this->getResult($bitlyurl, true) !== false ) {
			return $this->results;
		}
		return false;
	}
	
  /**
   * Get the results from a bit.ly service interaction.
   *
   * @param string  $url    The URL to interact with, the action of the interaction 
   *                        will be contained within the URL.
   * @param boolean $errors Passed by the errors() function and forces this function
   *						            to use json as the format.
   *
   * @return boolean True if everything has worked, otherwise false.
   */
	private function getResult($bitlyurl, $errors = false )
	{
		if ( $errors ) {
			$tmpFormat = $this->format;
			$this->format = 'json';
		}

		if ( $this->format == 'json' ) {
			$results = json_decode(file_get_contents($bitlyurl), true);
		} else if ( $this->format == 'xml' ) {
			$xml = file_get_contents($bitlyurl);
			$results = $this->XML2Array($xml);
		}

		if ( $errors ) {
			$this->format = $tmpFormat;
		}

		if ( $results['statusCode'] != 'OK' ) {
			$this->errors = $results;
			return false;
		}

		if ( $errors ) {
			// Save everything in the results array
			$this->results = $results['results'];
		} else {
			// Save the first item in the results array
			$this->results = current($results['results']);
		}
		return true;
	}

  /**
   * Convert an XML string into an array.
   *
   * @param string  $xml       The XML to convert.
   * @param boolean $recursive Has the function called itself?
   *
   * @return array The short URL
   */
	private function XML2Array($xml, $recursive = false)
	{
		if ( !$recursive ) {
			$array = simplexml_load_string($xml);
		} else {
			$array = $xml;
		}

		$newArray = array();
		$array = (array)$array;
		foreach ( $array as $key => $value ) {
			$value = (array)$value;
			if ( isset($value[0]) ) {
				$newArray[$key] = trim($value[0]);
			} else {
				$newArray[$key] = $this->XML2Array($value, true);
			}
		}
		return $newArray;
	}
}