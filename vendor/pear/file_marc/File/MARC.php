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
 * @copyright 2003-2010 Oy Realnode Ab, Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/File_MARC
 * @example   read.php Retrieve specific fields and subfields from a record
 * @example   subfields.php Create new subfields and add them in specific order
 * @example   marc_yaz.php Pretty print a MARC record retrieved through the PECL yaz extension
 */

require_once 'PEAR/Exception.php';
require_once 'File/MARCBASE.php';
require_once 'File/MARC/Record.php';
require_once 'File/MARC/Field.php';
require_once 'File/MARC/Control_Field.php';
require_once 'File/MARC/Data_Field.php';
require_once 'File/MARC/Subfield.php';
require_once 'File/MARC/Exception.php';
require_once 'File/MARC/List.php';

// {{{ class File_MARC
/**
 * The main File_MARC class enables you to return File_MARC_Record
 * objects from a stream or string.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Christoffer Landtman <landtman@realnode.com>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC extends File_MARCBASE
{

    // {{{ constants

    /**
     * MARC records retrieved from a file
     */
    const SOURCE_FILE = 1;

    /**
     * MARC records retrieved from a binary string 
     */
    const SOURCE_STRING = 2;

    /**
     * Hexadecimal value for Subfield indicator
     */
    const SUBFIELD_INDICATOR = "\x1F";

    /**
     * Hexadecimal value for End of Field
     */
    const END_OF_FIELD = "\x1E";

    /**
     * Hexadecimal value for End of Record
     */
    const END_OF_RECORD = "\x1D";

    /**
     * Length of the Directory
     */
    const DIRECTORY_ENTRY_LEN = 12;

    /**
     * Length of the Leader
     */
    const LEADER_LEN = 24;

    /**
     * Maximum record length
     */
    const MAX_RECORD_LENGTH = 99999;
    // }}}

    // {{{ properties
    /**
     * Source containing raw records
     * 
     * @var resource
     */
    protected $source;

    /**
     * Source type (SOURCE_FILE or SOURCE_STRING)
     * 
     * @var int
     */
    protected $type;

    /**
     * XMLWriter for writing collections
     * 
     * @var XMLWriter
     */
    protected $xmlwriter;
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Read in MARC records
     *
     * This function reads in MARC record files or strings that
     * contain one or more MARC records.
     *
     * <code>
     * <?php
     * // Retrieve MARC records from a file
     * $journals = new File_MARC('journals.mrc', SOURCE_FILE);
     *
     * // Retrieve MARC records from a string (e.g. Z39 query results)
     * $monographs = new File_MARC($raw_marc, SOURCE_STRING);
     * ?>
     * </code>
     *
     * @param string $source        Name of the file, or a raw MARC string
     * @param int    $type          Source of the input, either SOURCE_FILE or SOURCE_STRING
     * @param string $record_class  Record class, defaults to File_MARC_Record
     */
    function __construct($source, $type = self::SOURCE_FILE, $record_class = null)
    {

        parent::__construct($source, $type, $record_class);

        switch ($type) {

        case self::SOURCE_FILE:
            $this->type = self::SOURCE_FILE;
            $this->source = fopen($source, 'rb');
            if (!$this->source) {
                 $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_FILE], array('filename' => $source));
                 throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INVALID_FILE);
            }
            break;

        case self::SOURCE_STRING:
            $this->type = self::SOURCE_STRING;
            $this->source = explode(File_MARC::END_OF_RECORD, $source);
            break;

        default:
            throw new File_MARC_Exception(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_SOURCE], File_MARC_Exception::ERROR_INVALID_SOURCE);
        }
    }
    // }}}

    // {{{ nextRaw()
    /**
     * Return the next raw MARC record
     *
     * Returns the next raw MARC record, unless all records already have
     * been read.
     *
     * @return string Either a raw record or false
     */
    function nextRaw()
    {
        if ($this->type == self::SOURCE_FILE) {
            $record = stream_get_line($this->source, File_MARC::MAX_RECORD_LENGTH, File_MARC::END_OF_RECORD);

            // Remove illegal stuff that sometimes occurs between records
            $record = preg_replace('/^[\\x0a\\x0d\\x00]+/', "", $record);

        } elseif ($this->type == self::SOURCE_STRING) {
            $record = array_shift($this->source);
        }

        // Exit if we are at the end of the file
        if (!$record) {
            return false;
        }

        // Append the end of record we lost during stream_get_line() or explode()
        $record .= File_MARC::END_OF_RECORD;
        return $record;
    }
    // }}}

    // {{{ next()
    /**
     * Return next {@link File_MARC_Record} object
     *
     * Decodes the next raw MARC record and returns the {@link File_MARC_Record}
     * object.
     * <code>
     * <?php
     * // Retrieve a set of MARC records from a file
     * $journals = new File_MARC('journals.mrc', SOURCE_FILE);
     *
     * // Iterate through the retrieved records
     * while ($record = $journals->next()) {
     *     print $record;
     *     print "\n";
     * }
     *
     * ?>
     * </code>
     *
     * @return File_MARC_Record next record, or false if there are
     * no more records
     */
    function next()
    {
        $raw = $this->nextRaw();
        if ($raw) {
            return $this->_decode($raw);
        } else {
            return false;
        }
    }
    // }}}

    // {{{ _decode()
    /**
     * Decode a given raw MARC record
     *
     * Port of Andy Lesters MARC::File::USMARC->decode() Perl function into PHP.
     *
     * @param string $text Raw MARC record
     *
     * @return File_MARC_Record Decoded File_MARC_Record object
     */
    private function _decode($text)
    {
        $marc = new $this->record_class($this);

        // fallback on the actual byte length
        $record_length = strlen($text);

        $matches = array();
        if (preg_match("/^(\d{5})/", $text, $matches)) {
            // Store record length
            $record_length = $matches[1];
            if ($record_length != strlen($text)) {
                $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INCORRECT_LENGTH], array("record_length" => $record_length, "actual" => strlen($text))));
                // Real beats declared byte length
                $record_length = strlen($text);
            }
        } else {
            $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_NONNUMERIC_LENGTH], array("record_length" => substr($text, 0, 5))));
        }

        if (substr($text, -1, 1) != File_MARC::END_OF_RECORD)
             throw new File_MARC_Exception(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_TERMINATOR], File_MARC_Exception::ERROR_INVALID_TERMINATOR);

        // Store leader
        $marc->setLeader(substr($text, 0, File_MARC::LEADER_LEN));

        // bytes 12 - 16 of leader give offset to the body of the record
        $data_start = 0 + substr($text, 12, 5);

        // immediately after the leader comes the directory (no separator)
        $dir = substr($text, File_MARC::LEADER_LEN, $data_start - File_MARC::LEADER_LEN - 1);  // -1 to allow for \x1e at end of directory

        // character after the directory must be \x1e
        if (substr($text, $data_start-1, 1) != File_MARC::END_OF_FIELD) {
            $marc->addWarning(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_NO_DIRECTORY]);
        }

        // All directory entries 12 bytes long, so length % 12 must be 0
        if (strlen($dir) % File_MARC::DIRECTORY_ENTRY_LEN != 0) {
            $marc->addWarning(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_DIRECTORY_LENGTH]);
        }

        // go through all the fields
        $nfields = strlen($dir) / File_MARC::DIRECTORY_ENTRY_LEN;
        for ($n=0; $n<$nfields; $n++) {
            // As pack returns to key 1, leave place 0 in list empty
            list(, $tag) = unpack("A3", substr($dir, $n*File_MARC::DIRECTORY_ENTRY_LEN, File_MARC::DIRECTORY_ENTRY_LEN));
            list(, $len) = unpack("A3/A4", substr($dir, $n*File_MARC::DIRECTORY_ENTRY_LEN, File_MARC::DIRECTORY_ENTRY_LEN));
            list(, $offset) = unpack("A3/A4/A5", substr($dir, $n*File_MARC::DIRECTORY_ENTRY_LEN, File_MARC::DIRECTORY_ENTRY_LEN));

            // Check directory validity
            if (!preg_match("/^[0-9A-Za-z]{3}$/", $tag)) {
                $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_DIRECTORY_TAG], array("tag" => $tag)));
            }
            if (!preg_match("/^\d{4}$/", $len)) {
                $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_DIRECTORY_TAG_LENGTH], array("tag" => $tag, "len" => $len)));
            }
            if (!preg_match("/^\d{5}$/", $offset)) {
                $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_DIRECTORY_OFFSET], array("tag" => $tag, "offset" => $offset)));
            }
            if ($offset + $len > $record_length) {
                $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_DIRECTORY], array("tag" => $tag)));
            }

            $tag_data = substr($text, $data_start + $offset, $len);

            if (substr($tag_data, -1, 1) == File_MARC::END_OF_FIELD) {
                /* get rid of the end-of-tag character */
                $tag_data = substr($tag_data, 0, -1);
                $len--;
            } else {
                $marc->addWarning(File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_FIELD_EOF], array("tag" => $tag)));
            }

            if (preg_match("/^\d+$/", $tag) and ($tag < 10)) {
                $marc->appendField(new File_MARC_Control_Field($tag, $tag_data));
            } else {
                $subfields = explode(File_MARC::SUBFIELD_INDICATOR, $tag_data);
                $indicators = array_shift($subfields);

                if (strlen($indicators) != 2) {
                    $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_INDICATORS], array("tag" => $tag, "indicators" => $indicators));
                    $marc->addWarning($errorMessage);
                    // Do the best with the indicators we've got
                    if (strlen($indicators) == 1) {
                        $ind1 = $indicators;
                        $ind2 = " ";
                    } else {
                        list($ind1,$ind2) = array(" ", " ");
                    }
                } else {
                    $ind1 = substr($indicators, 0, 1);
                    $ind2 = substr($indicators, 1, 1);
                }

                // Split the subfield data into subfield name and data pairs
                $subfield_data = array();
                foreach ($subfields as $subfield) {
                    if (strlen($subfield) > 0) {
                        $subfield_data[] = new File_MARC_Subfield(substr($subfield, 0, 1), substr($subfield, 1));
                    } else {
                         $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_EMPTY_SUBFIELD], array("tag" => $tag));
                         $marc->addWarning($errorMessage);
                    }
                }

                if (!isset($subfield_data)) {
                     $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_EMPTY_SUBFIELD], array("tag" => $tag));
                     $marc->addWarning($errorMessage);
                }


                // If the data is invalid, let's just ignore the one field
                try {
                    $new_field = new File_MARC_Data_Field($tag, $subfield_data, $ind1, $ind2);
                    $marc->appendField($new_field);
                } catch (Exception $e) {
                    $marc->addWarning($e->getMessage());
                }
            }
        }

        return $marc;
    }
    // }}}

}
// }}}

