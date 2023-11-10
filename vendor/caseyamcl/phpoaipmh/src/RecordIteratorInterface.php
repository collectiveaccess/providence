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
 * Response List Entity iterates over records returned from an OAI-PMH Endpoint
 *
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 * @since v2.6
 */
interface RecordIteratorInterface extends \Traversable
{
    /**
     * @return ClientInterface
     */
    public function getClient();

    /**
     * Get the total number of requests made during this run
     *
     * @return int The number of HTTP requests made
     */
    public function getNumRequests();

    /**
     * Get the total number of records processed during this run
     *
     * @return int The number of records processed
     */
    public function getNumRetrieved();

    /**
     * Get the resumption token if it is specified
     *
     * @return null|string
     */
    public function getResumptionToken();

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpirationDate();

    /**
     * Get the total number of records in the collection if available
     *
     * This only returns a value if the OAI-PMH server provides this information
     * in the response, which not all servers do (it is optional in the OAI-PMH spec)
     *
     * Also, the number of records may change during the requests, so it should
     * be treated as an estimate
     *
     * @return int|null
     */
    public function getTotalRecordCount();
}
