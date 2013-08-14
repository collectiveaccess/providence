<?php
/**
 * @package OaiPmhRepository
 * @subpackage Libraries
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Utility class for dealing with OAI identifiers
 *
 * OaiPmhRepository_OaiIdentifier represents an instance of a unique identifier
 * for the repository conforming to the oai-identifier recommendation.  The class
 * can parse the local ID out of a given identifier string, or create a new
 * identifier by specifing the local ID of the item.
 *
 * @package OaiPmhRepository
 * @subpackage Libraries
 */
class OaiIdentifier {
	const OAI_IDENTIFIER_NAMESPACE_URI = 'http://www.openarchives.org/OAI/2.0/oai-identifier';
	const OAI_IDENTIFIER_SCHEMA_URI = 'http://www.openarchives.org/OAI/2.0/oai-identifier.xsd';

	private static $namespaceId;

	public static function initializeNamespace($namespaceId) {
		self::$namespaceId = $namespaceId;
	}

	/**
	 * Converts the given OAI identifier to a CollectiveAccess item ID.
	 *
	 * @param string $oaiId OAI identifier.
	 * @return string Omeka item ID.
	 */
	public static function oaiIdToItem($oaiId) {
		$scheme = strtok($oaiId, ':');
		$namespaceId = strtok(':');
		$localId = strtok(':');
		if ($scheme != 'oai' || $namespaceId != self::$namespaceId || $localId < 0) {
		   return NULL;
		}
		return $localId;
	}

	/**
	 * Converts the given Omeka item ID to a OAI identifier.
	 *
	 * @param mixed $itemId Omeka item ID.
	 * @return string OAI identifier.
	 */
	public static function itemToOaiId($itemId) {
		return 'oai:' . self::$namespaceId . ':' . $itemId;
	}

	/**
	 * Outputs description element child describing the repository's OAI
	 * identifier implementation.
	 *
	 * @param DOMElement $parentElement Parent DOM element for XML output
	 */
	public static function describeIdentifier($parentElement) {
		$elements = array(
			'scheme'               => 'oai',
			'repositoryIdentifier' => self::$namespaceId,
			'delimiter'            => ':',
			'sampleIdentifier'     => self::itemtoOaiId(1)
        );
		$oaiIdentifier = $parentElement->ownerDocument->createElement('oai-identifier');
		foreach($elements as $tag => $value) {
			$oaiIdentifier->appendChild($parentElement->ownerDocument->createElement($tag, $value));
		}

		$parentElement->appendChild($oaiIdentifier);

		//must set xmlns attribute manually to avoid DOM extension appending 
		//default: prefix to element name
		$oaiIdentifier->setAttribute('xmlns', self::OAI_IDENTIFIER_NAMESPACE_URI);
		$oaiIdentifier->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
		$oaiIdentifier->setAttribute('xsi:schemaLocation',self::OAI_IDENTIFIER_NAMESPACE_URI . ' ' . self::OAI_IDENTIFIER_SCHEMA_URI);
   }
}
