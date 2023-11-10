<?php

namespace Phpoaipmh;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

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

/**
 * Granularity class provides utility for specifying date and constraint precision
 *
 * @author Christian Scheb
 */
class Granularity
{
    const DATE = "YYYY-MM-DD";
    const DATE_AND_TIME = "YYYY-MM-DDThh:mm:ssZ";

    /**
     * Format DateTime string based on granularity
     *
     * @param DateTimeInterface $dateTime
     * @param string $format       Either self::DATE or self::DATE_AND_TIME
     *
     * @return string
     * @throws \Exception
     */
    public static function formatDate(DateTimeInterface $dateTime, $format)
    {
        $phpFormats = array(
            self::DATE => "Y-m-d",
            self::DATE_AND_TIME => 'Y-m-d\TH:i:s\Z',
        );
        $phpFormat = $phpFormats[$format];

        return (new DateTimeImmutable(
            '@' . $dateTime->getTimestamp(),
            new DateTimeZone("UTC")
        ))->format($phpFormat);
    }
}
