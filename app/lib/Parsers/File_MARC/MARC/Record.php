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
 * @version   CVS: $Id: Record.php 308146 2011-02-08 20:36:20Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Record
/**
 * Represents a single MARC record
 * 
 * A MARC record contains a leader and zero or more fields held within a
 * linked list structure. Fields are represented by {@link File_MARC_Data_Field}
 * objects.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Christoffer Landtman <landtman@realnode.com>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Record
{

    // {{{ properties
    /**
     * Contains a linked list of {@link File_MARC_Data_Field} objects for
     * this record
     * @var File_MARC_List
     */
    protected $fields;

    /**
     * Record leader
     * @var string
     */
    protected $leader;

    /**
     * Non-fatal warnings generated during parsing
     * @var array
     */
    protected $warnings;

    /**
     * XMLWriter for writing collections
     * 
     * @var XMLWriter
     */
    protected $marcxml;

    /**
     * MARC instance for access to the XML header/footer methods
     * We need this so that we can properly wrap a collection of MARC records.
     * 
     * @var File_MARC
     */
    protected $marc;

    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Start function
     *
     * Set all variables to defaults to create new File_MARC_Record object
     *
     * @param File_MARC $marc MARC record from File_MARC or File_MARCXML
     *
     * @return true 
     */
    function __construct($marc = null)
    {
        $this->fields = new File_MARC_List();
        $this->setLeader(str_repeat(' ', 24));
        if (!$marc) {
            $marc = new File_MARC(null, File_MARC::SOURCE_STRING); // oh the hack
        }
        $this->marc = $marc;
        $this->marcxml = $marc->getXMLWriter();
    }
    // }}}

    // {{{ Destructor: function __destruct()
    /**
     * Destroys the data field
     */
    function __destruct()
    {
        $this->fields = null;
        $this->warnings = null;
    }
    // }}}

    // {{{ getLeader()
    /**
     * Get MARC leader
     *
     * Returns the leader for the MARC record. No validation
     * on the specified leader is performed.
     *
     * @return string returns the leader
     */
    function getLeader()
    {
        return (string)$this->leader;
    }
    // }}}

    // {{{ setLeader()
    /**
     * Set MARC record leader
     *
     * Sets the leader for the MARC record. No validation
     * on the specified leader is performed.
     *
     * @param string $leader Leader
     *
     * @return string returns the leader
     */
    function setLeader($leader)
    {
        $this->leader = $leader;
        return $this->leader;
    }
    // }}}

    // {{{ appendField()
    /**
     * Appends field to MARC record
     *
     * Adds a {@link File_MARC_Control_Field} or {@link File_MARC_Data_Field}
     * object to the end of the existing list of fields.
     *
     * @param File_MARC_Field $new_field The field to add
     *
     * @return File_MARC_Field The field that was added
     */
    function appendField(File_MARC_Field $new_field)
    {
        /* Append as the last field in the record */
        $this->fields->appendNode($new_field);
        return $new_field;
    }
    // }}}

    // {{{ prependField()
    /**
     * Prepends field to MARC record
     *
     * Adds a {@link File_MARC_Control_Field} or {@link File_MARC_Data_Field}
     * object to the start of to the existing list of fields.
     *
     * @param File_MARC_Field $new_field The field to add
     *
     * @return File_MARC_Field The field that was added
     */
    function prependField(File_MARC_Field $new_field)
    {
        $this->fields->prependNode($new_field);
        return $new_field;
    }
    // }}}

    // {{{ insertField()
    /**
     * Inserts a field in the MARC record relative to an existing field
     *
     * Inserts a {@link File_MARC_Control_Field} or {@link File_MARC_Data_Field}
     * object before or after a specified existing field.
     *
     * <code>
     * // Example: Insert a new field before the first 650 field
     *
     * // Create the new field
     * $subfields[] = new File_MARC_Subfield('a', 'Scott, Daniel.');
     * $new_field = new File_MARC_Data_Field('100', $subfields, 0, null);
     *
     * // Retrieve the target field for our insertion point
     * $subject = $record->getFields('650');
     *
     * // Insert the new field
     * if (is_array($subject)) {
     *     $record->insertField($new_field, $subject[0], true);
     * }
     * elseif ($subject) {
     *     $record->insertField($new_field, $subject, true);
     * }
     * </code>
     *
     * @param File_MARC_Field $new_field      The field to add
     * @param File_MARC_Field $existing_field The target field
     * @param bool            $before         Insert the new field before the existing field if true, after the existing field if false
     *
     * @return File_MARC_Field                The field that was added
     */
    function insertField(File_MARC_Field $new_field, File_MARC_Field $existing_field, $before = false)
    {
        switch ($before) {
        /* Insert before the specified field in the record */
        case true:
            $this->fields->insertNode($new_field, $existing_field, true);
            break;

        /* Insert after the specified field in the record */
        case false:
            $this->fields->insertNode($new_field, $existing_field);
            break;

        default: 
             $errorMessage = File_MARC_Exception::formatError(File_MARC_Exception::$messages[File_MARC_Exception::ERROR_INSERTFIELD_MODE], array("mode" => $before));
             throw new File_MARC_Exception($errorMessage, File_MARC_Exception::ERROR_INSERTFIELD_MODE);
        }
        return $new_field;
    }
    // }}}

    // {{{ _buildDirectory()
    /**
     * Build record directory
     *
     * Generate the directory of the record according to the current contents
     * of the record.
     *
     * @return array Array ($fields, $directory, $total, $base_address)
     */
    private function _buildDirectory()
    {
        // Vars
        $fields = array();
        $directory = array();
        $data_end = 0;

        foreach ($this->fields as $field) {
            // No empty fields allowed
            if (!$field->isEmpty()) {
                // Get data in raw format
                $str = $field->toRaw();
                $fields[] = $str;

                // Create directory entry
                $len = strlen($str);
                $direntry = sprintf("%03s%04d%05d", $field->getTag(), $len, $data_end);
                $directory[] = $direntry;
                $data_end += $len;
            }
        }

        /**
         * Rules from MARC::Record::USMARC
         */
        $base_address
            = File_MARC::LEADER_LEN +    // better be 24
                (count($directory) * File_MARC::DIRECTORY_ENTRY_LEN) +
                                // all the directory entries
                1;              // end-of-field marker


        $total
            = $base_address +  // stuff before first field
                $data_end +      // Length of the fields
                1;              // End-of-record marker


        return array($fields, $directory, $total, $base_address);
    }
    // }}}

    // {{{ setLeaderLengths()
    /**
     * Set MARC record leader lengths
     *
     * Set the Leader lengths of the record according to defaults specified in
     * {@link http://www.loc.gov/marc/bibliographic/ecbdldrd.html}
     *
     * @param int $record_length Record length
     * @param int $base_address  Base address of data
     *
     * @return bool              Success or failure
     */
    function setLeaderLengths($record_length, $base_address)
    {
        if (!is_int($record_length)) {
            return false;
        }
        if (!is_int($base_address)) {
            return false;
        }

        // Set record length
        $this->setLeader(substr_replace($this->getLeader(), sprintf("%05d", $record_length), 0, 5));
        $this->setLeader(substr_replace($this->getLeader(), sprintf("%05d", $base_address), File_MARC::DIRECTORY_ENTRY_LEN, 5));
        $this->setLeader(substr_replace($this->getLeader(), '22', 10, 2));
        $this->setLeader(substr_replace($this->getLeader(), '4500', 20, 4));

        if (strlen($this->getLeader()) > File_MARC::LEADER_LEN) {
            // Avoid incoming leaders that are mangled to be overly long
            $this->setLeader(substr($this->getLeader(), 0, File_MARC::LEADER_LEN));
            $this->addWarning("Input leader was too long; truncated to " . File_MARC::LEADER_LEN . " characters");
        }
        return true;
    }
    // }}}

    // {{{ getField()
    /**
     * Return the first {@link File_MARC_Data_Field} or
     * {@link File_MARC_Control_Field} object that matches the specified tag
     * name. Returns false if no match is found.
     *
     * @param string $spec tag name
     * @param bool   $pcre if true, then match as a regular expression
     *
     * @return {@link File_MARC_Data_Field}|{@link File_MARC_Control_Field} first field that matches the requested tag name
     */
    function getField($spec = null, $pcre = null)
    {
        foreach ($this->fields as $field) {
            if (($pcre
                && preg_match("/$spec/", $field->getTag()))
                || (!$pcre
                && $spec == $field->getTag())
            ) {
                return $field;
            }
        }
        return false;
    }
    // }}}

    // {{{ getFields()
    /**
     * Return an array or {@link File_MARC_List} containing all
     * {@link File_MARC_Data_Field} or  {@link File_MARC_Control_Field} objects
     * that match the specified tag name. If the tag name is omitted all
     * fields are returned.
     *
     * @param string $spec tag name
     * @param bool   $pcre if true, then match as a regular expression
     *
     * @return File_MARC_List|array {@link File_MARC_Data_Field} or
     * {@link File_MARC_Control_Field} objects that match the requested tag name
     */
    function getFields($spec = null, $pcre = null)
    {
        if (!$spec) {
            return $this->fields;
        }

        // Okay, we're actually looking for something specific
        $matches = array();
        foreach ($this->fields as $field) {
            if (($pcre && preg_match("/$spec/", $field->getTag()))
                || (!$pcre && $spec == $field->getTag())
            ) {
                $matches[] = $field;
            }
        }
        return $matches;
    }
    // }}}

    // {{{ deleteFields()
    /**
     * Delete all occurrences of a field matching a tag name from the record.
     *
     * @param string $tag  tag for the fields to be deleted
     * @param bool   $pcre if true, then match as a regular expression
     *
     * @return int         number of fields that were deleted
     */
    function deleteFields($tag, $pcre = null)
    {
        $cnt = 0;
        foreach ($this->getFields() as $field) {
            if (($pcre
                && preg_match("/$tag/", $field->getTag()))
                || (!$pcre
                && $tag == $field->getTag())
            ) {
                $field->delete();
                $cnt++;
            }
        }
        return $cnt;
    }
    // }}}

    // {{{ addWarning()
    /**
     * Add a warning to the MARC record that something non-fatal occurred during
     * parsing.
     *
     * @param string $warning warning message
     *
     * @return true
     */
    public function addWarning($warning)
    {
        $this->warnings[] = $warning;
    }
    // }}}

    // {{{ getWarnings()
    /**
     * Return the array of warnings from the MARC record.
     *
     * @return array warning messages
     */
    public function getWarnings()
    {
        return $this->warnings;
    }
    // }}}

    // {{{ output methods
    /**
     * ========== OUTPUT METHODS ==========
     */

    // {{{ toRaw()
    /**
     * Return the record in raw MARC format.
     *
     * If you have modified an existing MARC record or created a new MARC
     * record, use this method to save the record for use in other programs
     * that accept the MARC format -- for example, your integrated library
     * system.
     *
     * <code>
     * // Example: Modify a record and save the output to a file
     * $record->deleteFields('650');
     *
     * // Now that the record has no subject fields, save it to disk
     * fopen($file, '/home/dan/no_subject.mrc', 'w');
     * fwrite($file, $record->toRaw());
     * fclose($file);
     * </code>
     *
     * @return string Raw MARC data
     */
    function toRaw()
    {
        list($fields, $directory, $record_length, $base_address) = $this->_buildDirectory();
        $this->setLeaderLengths($record_length, $base_address);

        /**
         * Glue together all parts
         */
        return $this->getLeader().implode("", $directory).File_MARC::END_OF_FIELD.implode("", $fields).File_MARC::END_OF_RECORD;
    }
    // }}}

    // {{{ __toString()
    /**
     * Return the MARC record in a pretty printed string
     *
     * This method produces an easy-to-read textual display of a MARC record.
     *
     * The structure is roughly:
     * <tag> <ind1> <ind2> _<code><data>
     *                     _<code><data>
     *
     * @return string Formatted representation of MARC record
     */
    function __toString()
    {
        // Begin output
        $formatted = "LDR " . $this->getLeader() . "\n";
        foreach ($this->fields as $field) {
            if (!$field->isEmpty()) {
                $formatted .= $field->__toString() . "\n";
            }
        }
        return $formatted;
    }
    // }}}

    // {{{ toJSON()
    /**
     * Return the MARC record in JSON format
     *
     * This method produces a JSON representation of a MARC record. The input
     * encoding must be UTF8, otherwise the returned values will be corrupted.
     *
     * @return string          representation of MARC record in JSON format
     *
     * @todo Fix encoding input / output issues (PHP 6.0 required?)
     */
    function toJSON()
    {
        $json = new StdClass();
        $json->leader = utf8_encode($this->getLeader());

        /* Start fields */
        $fields = array();
        foreach ($this->fields as $field) {
            if (!$field->isEmpty()) {
                switch(get_class($field)) {
                case "File_MARC_Control_Field":
                    $fields[] = array(utf8_encode($field->getTag()) => utf8_encode($field->getData()));
                    break;

                case "File_MARC_Data_Field":
                    $subs = array();
                    foreach ($field->getSubfields() as $sf) {
                        $subs[] = array(utf8_encode($sf->getCode()) => utf8_encode($sf->getData()));
                    }
                    $contents = new StdClass();
                    $contents->ind1 = utf8_encode($field->getIndicator(1));
                    $contents->ind2 = utf8_encode($field->getIndicator(2));
                    $contents->subfields = $subs;
                    $fields[] = array(utf8_encode($field->getTag()) => $contents);
                    break;
                }
            }
        }
        /* End fields and record */

        $json->fields = $fields;
        return json_encode($json);
    }

    // }}}

    // {{{ toJSONHash()
    /**
     * Return the MARC record in Bill Dueber's MARC-HASH JSON format
     *
     * This method produces a JSON representation of a MARC record as defined
     * at http://robotlibrarian.billdueber.com/new-interest-in-marc-hash-json/
     * The input * encoding must be UTF8, otherwise the returned values will
     * be corrupted.
     *
     * @return string          representation of MARC record in JSON format
     *
     * @todo Fix encoding input / output issues (PHP 6.0 required?)
     */
    function toJSONHash()
    {
        $json = new StdClass();
        $json->type = "marc-hash";
        $json->version = array(1, 0);
        $json->leader = utf8_encode($this->getLeader());

        /* Start fields */
        $fields = array();
        foreach ($this->fields as $field) {
            if (!$field->isEmpty()) {
                switch(get_class($field)) {
                case "File_MARC_Control_Field":
                    $fields[] = array(utf8_encode($field->getTag()), utf8_encode($field->getData()));
                    break;

                case "File_MARC_Data_Field":
                    $subs = array();
                    foreach ($field->getSubfields() as $sf) {
                        $subs[] = array(utf8_encode($sf->getCode()), utf8_encode($sf->getData()));
                    }
                    $contents = array(
                        utf8_encode($field->getTag()),
                        utf8_encode($field->getIndicator(1)),
                        utf8_encode($field->getIndicator(2)),
                        $subs
                    );
                    $fields[] = $contents;
                    break;
                }
            }
        }
        /* End fields and record */

        $json->fields = $fields;
        return json_encode($json);
    }

    // }}}


    // {{{ toXML()
    /**
     * Return the MARC record in MARCXML format
     *
     * This method produces an XML representation of a MARC record that
     * attempts to adhere to the MARCXML standard documented at
     * http://www.loc.gov/standards/marcxml/
     *
     * @param string $encoding output encoding for the MARCXML record
     * @param bool   $indent   pretty-print the MARCXML record
     * @param bool   $single   wrap the <record> element in a <collection> element
     *
     * @return string          representation of MARC record in MARCXML format
     *
     * @todo Fix encoding input / output issues (PHP 6.0 required?)
     */
    function toXML($encoding = "UTF-8", $indent = true, $single = true)
    {
        $this->marcxml->setIndent($indent);
        if ($single) {
            $this->marc->toXMLHeader();
        }
        $this->marcxml->startElement("record");

        // MARCXML schema has some strict requirements
        // We'll set reasonable defaults to avoid invalid MARCXML
        $xmlLeader = $this->getLeader();

        // Record status
        if ($xmlLeader[5] == " ") {
            // Default to "n" (new record)
            $xmlLeader[5] = "n";
        }

        // Type of record
        if ($xmlLeader[6] == " ") {
            // Default to "a" (language material)
            $xmlLeader[6] = "a";
        }

        $this->marcxml->writeElement("leader", $xmlLeader);

        foreach ($this->fields as $field) {
            if (!$field->isEmpty()) {
                switch(get_class($field)) {
                case "File_MARC_Control_Field":
                    $this->marcxml->startElement("controlfield");
                    $this->marcxml->writeAttribute("tag", $field->getTag());
                    $this->marcxml->text($field->getData());
                    $this->marcxml->endElement(); // end control field
                    break;

                case "File_MARC_Data_Field":
                    $this->marcxml->startElement("datafield");
                    $this->marcxml->writeAttribute("tag", $field->getTag());
                    $this->marcxml->writeAttribute("ind1", $field->getIndicator(1));
                    $this->marcxml->writeAttribute("ind2", $field->getIndicator(2));
                    foreach ($field->getSubfields() as $subfield) {
                        $this->marcxml->startElement("subfield");
                        $this->marcxml->writeAttribute("code", $subfield->getCode());
                        $this->marcxml->text($subfield->getData());
                        $this->marcxml->endElement(); // end subfield
                    }
                    $this->marcxml->endElement(); // end data field
                    break;
                }
            }
        }

        $this->marcxml->endElement(); // end record
        if ($single) {
            return $this->marc->toXMLFooter();
        }
    }

    // }}}

}
// }}}

