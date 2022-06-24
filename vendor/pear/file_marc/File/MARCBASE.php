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
 * @version   CVS: $Id: MARCXML.php 301727 2010-07-30 17:30:51Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 * @example   read.php Retrieve specific fields and subfields from a record
 * @example   subfields.php Create new subfields and add them in specific order
 * @example   marc_yaz.php Pretty print a MARC record retrieved via yaz extension
 */

require_once 'File/MARC/Record.php';

// {{{ class File_MARCBASE
/**
 * The main File_MARCBASE class provides common methods for File_MARC and
 * File_MARCXML - primarily for generating MARCXML output.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARCBASE
{

    /**
     * XMLWriter for writing collections
     * 
     * @var XMLWriter
     */
    protected $xmlwriter;

    /**
     * Record class
     *
     * @var string
     */
    protected $record_class;
    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Read in MARCXML records
     *
     * This function reads in files or strings that
     * contain one or more MARCXML records.
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
     * @param string $source       Name of the file, or a raw MARC string
     * @param int    $type         Source of the input: SOURCE_FILE or SOURCE_STRING
     * @param string $record_class Record class, defaults to File_MARC_Record
     */
    function __construct($source, $type, $record_class)
    {
        $this->xmlwriter = new XMLWriter();
        $this->xmlwriter->openMemory();
        $this->xmlwriter->startDocument('1.0', 'UTF-8');

        $this->record_class = $record_class ?: File_MARC_Record::class;
    }
    // }}}

    // {{{ toXMLHeader()
    /**
     * Initializes the MARCXML output of a record or collection of records 
     *
     * This method produces an XML representation of a MARC record that
     * attempts to adhere to the MARCXML standard documented at
     * http://www.loc.gov/standards/marcxml/
     *
     * @return bool true if successful
     */
    function toXMLHeader()
    {
        $this->xmlwriter->startElement("collection");
        $this->xmlwriter->writeAttribute("xmlns", "http://www.loc.gov/MARC21/slim");
        return true;
    }
    // }}}

    // {{{ getXMLWriter()
    /**
     * Returns the XMLWriter object
     *
     * This method produces an XML representation of a MARC record that
     * attempts to adhere to the MARCXML standard documented at
     * http://www.loc.gov/standards/marcxml/
     *
     * @return XMLWriter XMLWriter instance
     */
    function getXMLWriter()
    {
        return $this->xmlwriter;
    }
    // }}}

    // {{{ toXMLFooter()
    /**
     * Returns the MARCXML collection footer
     *
     * This method produces an XML representation of a MARC record that
     * attempts to adhere to the MARCXML standard documented at
     * http://www.loc.gov/standards/marcxml/
     *
     * @return string           representation of MARC record in MARCXML format
     */
    function toXMLFooter()
    {
        $this->xmlwriter->endElement(); // end collection
        $this->xmlwriter->endDocument();
        return $this->xmlwriter->outputMemory();
    }
    // }}}

}
// }}}
