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
 * OAI-PMH protocol Exception Class thrown when OAI-PMH protocol errors occur
 *
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 * @since v2.0
 */
class OaipmhException extends BaseOaipmhException
{
    /**
     * @var string
     */
    private $oaiErrorCode;

    /**
     * OaipmhException constructor.
     * @param string          $oaiErrorCode
     * @param string          $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($oaiErrorCode, $message, $code = 0, \Exception $previous = null)
    {
        $this->oaiErrorCode = $oaiErrorCode;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "OaipmhException: [{$this->code}]: ({$this->oaiErrorCode}) {$this->message}";
    }

    /**
     * @return string
     */
    public function getOaiErrorCode()
    {
        return $this->oaiErrorCode;
    }
}
