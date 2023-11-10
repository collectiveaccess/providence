<?php

/**
 * PHPOAIPMH Library
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/caseyamcl/phpoaipmh
 * @version 3.0
 * @package caseyamcl/phpoaipmh
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 *
 * For the full copyright and license information, -please view the LICENSE.md
 * file that was distributed with this source code.
 *
 * ------------------------------------------------------------------
 */

namespace Phpoaipmh;

/**
 * OAI-PMH Endpoint Class
 *
 * @since v1.0
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 */
class Endpoint implements EndpointInterface
{
    const AUTO = null;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $granularity;

    /**
     * Build endpoint using URL and default settings
     *
     * @param string $url
     * @return Endpoint
     * @throws \Exception
     */
    public static function build($url)
    {
        return new Endpoint(new Client($url));
    }

    /**
     * Constructor
     *
     * @param ClientInterface $client       Optional; will attempt to auto-build dependency if not passed
     * @param string          $granularity  Optional; the OAI date format for fetching records, use constants from
     *                                      Granularity class
     * @throws \Exception
     */
    public function __construct(ClientInterface $client = null, $granularity = self::AUTO)
    {
        $this->client = $client ?: new Client();
        $this->granularity = $granularity;
    }

    /**
     * Identify the OAI-PMH Endpoint
     *
     * @return \SimpleXMLElement A XML document with attributes describing the repository
     */
    public function identify()
    {
        $resp = $this->client->request('Identify');

        return $resp;
    }

    /**
     * List Metadata Formats
     *
     * Return the list of supported metadata format for a particular record (if $identifier
     * is provided), or the entire repository (if no arguments are provided)
     *
     * @param  string  $identifier If specified, will return only those metadata formats that a
     *                             particular record supports
     * @return RecordIteratorInterface
     */
    public function listMetadataFormats($identifier = null)
    {
        $params = ($identifier) ? array('identifier' => $identifier) : array();

        return new RecordIterator($this->client, 'ListMetadataFormats', $params);
    }

    /**
     * List Record Sets
     *
     * @return RecordIteratorInterface
     */
    public function listSets()
    {
        return new RecordIterator($this->client, 'ListSets');
    }

    /**
     * Get a single record
     *
     * @param  string            $id             Record Identifier
     * @param  string            $metadataPrefix Required by OAI-PMH endpoint
     * @return \SimpleXMLElement An XML document corresponding to the record
     */
    public function getRecord($id, $metadataPrefix)
    {
        $params = array(
            'identifier'     => $id,
            'metadataPrefix' => $metadataPrefix
        );

        return $this->client->request('GetRecord', $params);
    }

    /**
     * List Record identifiers
     *
     * Corresponds to OAI Verb to list record identifiers
     *
     * @param  string             $metadataPrefix Required by OAI-PMH endpoint
     * @param  \DateTimeInterface $from             An optional 'from' date for selective harvesting
     * @param  \DateTimeInterface $until            An optional 'until' date for selective harvesting
     * @param  string             $set              An optional setSpec for selective harvesting
     * @param  string             $resumptionToken  An optional resumptionToken for selective harvesting
     * @return RecordIteratorInterface
     */
    public function listIdentifiers($metadataPrefix, $from = null, $until = null, $set = null, $resumptionToken = null)
    {
        return $this->createRecordIterator("ListIdentifiers", $metadataPrefix, $from, $until, $set, $resumptionToken);
    }

    /**
     * List Records
     *
     * Corresponds to OAI Verb to list records
     *
     * @param  string             $metadataPrefix Required by OAI-PMH endpoint
     * @param  \DateTimeInterface $from             An optional 'from' date for selective harvesting
     * @param  \DateTimeInterface $until            An optional 'from' date for selective harvesting
     * @param  string             $set              An optional setSpec for selective harvesting
     * @param  string             $resumptionToken  An optional resumptionToken for selective harvesting
     * @return RecordIteratorInterface
     */
    public function listRecords($metadataPrefix, $from = null, $until = null, $set = null, $resumptionToken = null)
    {
        return $this->createRecordIterator("ListRecords", $metadataPrefix, $from, $until, $set, $resumptionToken);
    }

    /**
     * Create a record iterator
     *
     * @param  string             $verb             OAI Verb
     * @param  string             $metadataPrefix   Required by OAI-PMH endpoint
     * @param  \DateTimeInterface $from             An optional 'from' date for selective harvesting
     * @param  \DateTimeInterface $until            An optional 'from' date for selective harvesting
     * @param  string             $set              An optional setSpec for selective harvesting
     * @param  string             $resumptionToken  An optional resumptionToken for selective harvesting
     *
     * @return RecordIteratorInterface
     */
    private function createRecordIterator($verb, $metadataPrefix, $from, $until, $set = null, $resumptionToken = null)
    {
        $params = array('metadataPrefix' => $metadataPrefix);

        if ($from instanceof \DateTimeInterface) {
            $params['from'] = Granularity::formatDate($from, $this->getGranularity());
        } elseif (null !== $from) {
            throw new \InvalidArgumentException(sprintf(
                '%s::%s $from parameter must be an instance of \DateTimeInterface',
                get_called_class(),
                'createRecordIterator'
            ));
        }

        if ($until instanceof \DateTimeInterface) {
            $params['until'] = Granularity::formatDate($until, $this->getGranularity());
        } elseif (null !== $until) {
            throw new \InvalidArgumentException(sprintf(
                '%s::%s $until parameter must be an instance of \DateTimeInterface',
                get_called_class(),
                'createRecordIterator'
            ));
        }

        if ($set) {
            $params['set'] = $set;
        }

        return new RecordIterator($this->client, $verb, $params, $resumptionToken);
    }

    /**
     * Lazy load granularity from Identify, if not specified
     *
     * @return string
     */
    private function getGranularity()
    {
        // If the granularity is not specified, attempt to retrieve it from the server
        // Fall back on DATE granularity
        if ($this->granularity === null) {
            $response = $this->identify();
            return (isset($response->Identify->granularity))
                ? (string) $response->Identify->granularity
                : Granularity::DATE;
        }

        return $this->granularity;
    }
}
