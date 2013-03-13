<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker: */

/**
 * Parser for MARC records
 *
 * This package is based on the PHP MARC package, originally called "php-marc",
 * that is part of the Emilda Project (http://www.emilda.org). Christoffer
 * Landtman generously agreed to make the "php-marc" code available under the
 * GNU LGPL so it could be used as the basis of this PEAR package.
 * 
 * PHP version 5
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  File_Formats
 * @package   File_MARC
 * @author    Christoffer Landtman <landtman@realnode.com>
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2003-2008 Oy Realnode Ab, Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id: Exception.php 268035 2008-10-30 17:01:14Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Exception extends PEAR_Exception
/**
 * The File_MARC_Exception class enables error-handling
 * for the File_MARC package.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Exception extends PEAR_Exception
{
    // {{{ Error codes
    /**
     * File could not be opened
     */
    const ERROR_INVALID_FILE = -1;

    /**
     * User passed an unknown SOURCE_ constant
     */
    const ERROR_INVALID_SOURCE = -2;

    /**
     * MARC record ended with an invalid terminator
     */
    const ERROR_INVALID_TERMINATOR = -3;

    /**
     * No directory was found for the MARC record
     */
    const ERROR_NO_DIRECTORY = -4;

    /**
     * An entry in the MARC directory was not 12 bytes
     */
    const ERROR_INVALID_DIRECTORY_LENGTH = -5;

    /**
     * An entry in the MARC directory specified an invalid tag
     */
    const ERROR_INVALID_DIRECTORY_TAG = -6;

    /**
     * An entry in the MARC directory specified an invalid tag length
     */
    const ERROR_INVALID_DIRECTORY_TAG_LENGTH = -7;

    /**
     * An entry in the MARC directory specified an invalid field offset
     */
    const ERROR_INVALID_DIRECTORY_OFFSET = -8;

    /**
     * An entry in the MARC directory runs past the end of the record
     */
    const ERROR_INVALID_DIRECTORY = -9;

    /**
     * A field does not end with the expected end-of-field character
     */
    const ERROR_FIELD_EOF = -10;

    /**
     * A field has invalid indicators
     */
    const ERROR_INVALID_INDICATORS = -11;

    /**
     * A subfield is defined, but has no data
     */
    const ERROR_EMPTY_SUBFIELD = -12;

    /**
     * An indicator other than 1 or 2 was requested
     */
    const ERROR_INVALID_INDICATOR_REQUEST = -13;

    /**
     * An invalid mode for adding a field was specified
     */
    const ERROR_INSERTFIELD_MODE = -14;

    /**
     * An invalid object was passed instead of a File_MARC_Field object
     */
    const ERROR_INVALID_FIELD = -15;

    /**
     * An invalid object was passed instead of a File_MARC_Subfield object
     */
    const ERROR_INVALID_SUBFIELD = -16;

    /**
     * An invalid mode for adding a subfield was specified
     */
    const ERROR_INSERTSUBFIELD_MODE = -17;

    /**
     * The length in the MARC leader does not match the actual record length
     */
    const ERROR_INCORRECT_LENGTH = -18;

    /**
     * The length field in the leader was less than five characters long
     */
    const ERROR_MISSING_LENGTH = -19;

    /**
     * A five-digit length could not be found in the MARC leader
     */
    const ERROR_NONNUMERIC_LENGTH = -20;

    /**
     * Tag does not adhere to MARC standards
     */
    const ERROR_INVALID_TAG = -21;

    /**
     * A field has invalid indicators
     */
    const ERROR_INVALID_INDICATOR = -22;
    // }}}

    // {{{ error messages
    public static $messages = array(
        self::ERROR_EMPTY_SUBFIELD => 'No subfield data found in tag "%tag%"', 
        self::ERROR_FIELD_EOF => 'Field for tag "%tag%" does not end with an end of field character', 
        self::ERROR_INCORRECT_LENGTH => 'Invalid record length: Leader says "%record_length%" bytes; actual record length is "%actual%"', 
        self::ERROR_INSERTFIELD_MODE => 'insertField() mode "%mode%" was not recognized', 
        self::ERROR_INSERTSUBFIELD_MODE => 'insertSubfield() mode "%mode%" was not recognized', 
        self::ERROR_INVALID_DIRECTORY => 'Directory entry for tag "%tag%" runs past the end of the record', 
        self::ERROR_INVALID_DIRECTORY_LENGTH => 'Invalid directory length', 
        self::ERROR_INVALID_DIRECTORY_OFFSET => 'Invalid offset "%offset%" for tag "%tag%" in directory', 
        self::ERROR_INVALID_DIRECTORY_TAG => 'Invalid tag "%tag%" in directory', 
        self::ERROR_INVALID_DIRECTORY_TAG_LENGTH => 'Invalid length "%len%" in directory for tag "%tag%"', 
        self::ERROR_INVALID_FIELD => 'Specified field must be a File_MARC_Data_Field or File_MARC_Control_Field object, but was "%field%"', 
        self::ERROR_INVALID_FILE => 'Invalid input file "%filename%"', 
        self::ERROR_INVALID_INDICATOR_REQUEST => 'Attempt to access indicator "%indicator%" failed; 1 and 2 are the only valid indicators', 
        self::ERROR_INVALID_INDICATORS => 'Invalid indicators "%indicators%" forced to blanks for tag "%tag%"', 
        self::ERROR_INVALID_SOURCE => "Invalid source for MARC records", 
        self::ERROR_INVALID_SUBFIELD => 'Specified field must be a File_MARC_Subfield object, but was "%class%"', 
        self::ERROR_INVALID_TAG => 'Tag "%tag%" is not a valid tag.', 
        self::ERROR_INVALID_TERMINATOR => 'Invalid record terminator', 
        self::ERROR_MISSING_LENGTH => "Couldn't find record length", 
        self::ERROR_NO_DIRECTORY => 'No directory found', 
        self::ERROR_NONNUMERIC_LENGTH => 'Record length "%record_length%" is not numeric',
        self::ERROR_INVALID_INDICATOR => 'Illegal indicator "%indicator%" in field "%tag%" forced to blank', 
    );
    // }}}

    // {{{ formatError()
    /**
     * Replaces placeholder tokens in an error message with actual values.
     *
     * This method enables you to internationalize the messages for the
     * File_MARC class by simply replacing the File_MARC_Exception::$messages
     * array with translated values for the messages.
     *
     * @param string $message     Error message containing placeholders
     * @param array  $errorValues Actual values to substitute for placeholders
     *
     * @return string             Formatted message
     */
    public static function formatError($message, $errorValues)
    {
        foreach ($errorValues as $token => $value) {
            $message = preg_replace("/\%$token\%/", $value, $message);
        }
        return $message;
    }
    // }}}
}
// }}}

