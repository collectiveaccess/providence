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
 * Class MalformedResponseException
 *
 * Thrown when the HTTP response body cannot be parsed into valid OAI-PMH (usually XML errors)
 *
 * @author Casey McLaughlin <caseyamcl@gmail.com>
 * @since v2.0
 */
class MalformedResponseException extends BaseOaipmhException
{
    // pass..
}
