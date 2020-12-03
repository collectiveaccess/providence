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
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2007-2010 Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/File_MARC
 * @example   read.php Retrieve specific fields and subfields from a record
 * @example   subfields.php Create new subfields and add them in specific order
 * @example   marc_yaz.php Pretty print a MARC record retrieved through the PECL yaz extension
 */

require_once 'PEAR/Exception.php';
require_once 'File/MARCBASE.php';
require_once 'File/MARC.php';
require_once 'File/MARC/Record.php';
require_once 'File/MARC/Field.php';
require_once 'File/MARC/Control_Field.php';
require_once 'File/MARC/Data_Field.php';
require_once 'File/MARC/Subfield.php';
require_once 'File/MARC/Exception.php';
require_once 'File/MARC/List.php';

// {{{ class File_MARCJSON
/**
 * The main File_MARCJSON class enables you to return File_MARC_Record
 * objects from a MARC-in-JSON stream or string.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARCJSON extends File_MARCBASE
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
     * Counter for MARCJSON records in a collection
     *
     * @var int
     */
    protected $counter;

    // }}}


    // {{{ Constructor: function __construct()
    /**
     * Read in MARC-in-JSON records
     *
     * This function reads in a string that contains a single MARC-in-JSON
     * record.
     *
     * <code>
     * <?php
     * // Retrieve MARC record from a string
     * $monographs = new File_MARCJSON($json);
     * ?>
     * </code>
     *
     * @param string $source       A raw MARC-in-JSON string
     * @param string $record_class Record class, defaults to File_MARC_Record
     */
    function __construct($source, $record_class = null)
    {
        parent::__construct($source, self::SOURCE_STRING, $record_class);

        $this->text = json_decode($source);

        if (!$this->text) {
            $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INVALID_FILE], array('filename' => $source));
            throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INVALID_FILE);
        }
    }
    // }}}

    // {{{ next()
    /**
     * Return next {@link File_MARC_Record} object
     *
     * Decodes a MARCJSON record and returns the {@link File_MARC_Record}
     * object. There can only be one MARCJSON record per string but we use
     * the next() approach to maintain a unified API with XML and MARC21
     * readers.
     * <code>
     * <?php
     * // Retrieve a MARC-in-JSON record from a string
     * $json = '{"leader":"01850     2200517   4500","fields":[...]';
     * $journals = new File_MARCJSON($json);
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
        if ($this->text) {
            $marc = $this->_decode($this->text);
            $this->text = null;
            return $marc;
        } else {
            return false;
        }
    }
    // }}}


    // {{{ _decode()
    /**
     * Decode a given MARC-in-JSON record
     *
     * @param string $text MARC-in-JSON record element
     *
     * @return File_MARC_Record Decoded File_MARC_Record object
     */
    private function _decode($text)
    {
        $marc = new $this->record_class($this);

        // Store leader
        $marc->setLeader($text->leader);

        // go through all fields
        foreach ($text->fields as $field) {
            foreach ($field as $tag => $values) {
                // is it a control field?
                if (strpos($tag, '00') === 0) {
                    $marc->appendField(new File_MARC_Control_Field($tag, $values));
                } else {
                    // it's a data field -- get the subfields
                    $subfield_data = array();
                    foreach ($values->subfields as $subfield) {
                        foreach ($subfield as $sf_code => $sf_value) {
                            $subfield_data[] = new File_MARC_Subfield($sf_code, $sf_value);
                        }
                    }
                    // If the data is invalid, let's just ignore the one field
                    try {
                        $new_field = new File_MARC_Data_Field($tag, $subfield_data, $values->ind1, $values->ind2);
                        $marc->appendField($new_field);
                    } catch (Exception $e) {
                        $marc->addWarning($e->getMessage());
                    }
                }
            }

        }
        return $marc;
    }
    // }}}

}
// }}}

