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

namespace Phpoaipmh\Exception;

/**
 * HttpAdapter Protocol Exception Class thrown when HTTP transmission errors occur
 *
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 * @since v2.0
 */
class HttpException extends BaseOaipmhException
{
    /**
     * @var string
     */
    private $body;

    /**
     * Constructor
     *
     * @param string     $responseBody  Empty if no response body provided
     * @param int        $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($responseBody, $message, $code = 0, \Exception $previous = null)
    {
        $this->body = $responseBody;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
