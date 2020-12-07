<?php
/** ---------------------------------------------------------------------
 * app/lib/Db/DbBase.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2015 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__ . '/BaseObject.php');

/**
 * Provides common error handling methods
 */
class DbBase
{
    /**
     * Should we die on error?
     *
     * @access private
     */
    public $opb_die_on_error = false;

    /**
     * List of errors
     *
     * @access private
     */
    public $errors = array();

    /**
     * Defines if the application dies if an error occurrs. Default is false.
     *
     * @param bool $pb_die_on_error
     */
    public function dieOnError($pb_die_on_error = false)
    {
        $this->opb_die_on_error = $pb_die_on_error;
    }

    /**
     * Do we die on error?
     *
     * @return bool
     */
    public function getDieOnError()
    {
        return $this->opb_die_on_error;
    }

    /**
     * How many errors occurred?
     *
     * @return int
     */
    public function numErrors()
    {
        return sizeof($this->errors);
    }

    /**
     * Fetches the errors. Returns an array of Error objects.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Fetches the error. Returns an array containing the error descriptions.
     *
     * @return array
     */
    public function getErrors()
    {
        $va_error_descs = array();
        if (sizeof($this->errors)) {
            foreach ($this->errors as $o_e) {
                array_push($va_error_descs, $o_e->getErrorDescription());
            }
        }
        return $va_error_descs;
    }

    /**
     * Clears all errors.
     *
     * @return bool
     */
    public function clearErrors()
    {
        $this->errors = array();
        return true;
    }

    /**
     * Adds a new error
     *
     * @param int $pn_num
     * @param string $ps_message
     * @param string $ps_context
     */
    public function postError($pn_num, $ps_message, $ps_context, $ps_source = '')
    {
        $o_error = new ApplicationError();
        $o_error->setErrorOutput($this->opb_die_on_error);
        $o_error->setHaltOnError($this->opb_die_on_error);
        $o_error->setError($pn_num, $ps_message, $ps_context, $ps_source);
        array_push($this->errors, $o_error);
        return true;
    }
    # ---------------------------------------------------------------------------

    /**
     * Converts native datatypes to db datatypes
     *
     * @param string string representation of the datatype
     * @return array array with more information about the type, specific to mysql
     */
    public function nativeToDbDataType($ps_native_datatype_spec)
    {
        if (preg_match(
            "/^([A-Za-z]+)[\(]{0,1}([\d,]*)[\)]{0,1}[ ]*([A-Za-z]*)/",
            $ps_native_datatype_spec,
            $va_matches
        )) {
            $vs_native_type = $va_matches[1];
            $vs_length = $va_matches[2];
            $vb_unsigned = ($va_matches[3] == "unsigned") ? true : false;
            switch ($vs_native_type) {
                case 'varchar':
                    return array("type" => "varchar", "length" => $vs_length);
                    break;
                case 'char':
                    return array("type" => "char", "length" => $vs_length);
                    break;
                case 'bigint':
                    return array(
                        "type" => "int",
                        "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 64) / 2)),
                        "maximum" => $vb_unsigned ? pow(2, 64) - 1 : (pow(2, 64) / 2) - 1
                    );
                    break;
                case 'int':
                    return array(
                        "type" => "int",
                        "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 32) / 2)),
                        "maximum" => $vb_unsigned ? pow(2, 32) - 1 : (pow(2, 32) / 2) - 1
                    );
                    break;
                case 'mediumint':
                    return array(
                        "type" => "int",
                        "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 24) / 2)),
                        "maximum" => $vb_unsigned ? pow(2, 24) - 1 : (pow(2, 24) / 2) - 1
                    );
                    break;
                case 'smallint':
                    return array(
                        "type" => "int",
                        "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 16) / 2)),
                        "maximum" => $vb_unsigned ? pow(2, 16) - 1 : (pow(2, 16) / 2) - 1
                    );
                    break;
                case 'tinyint':
                    return array(
                        "type" => "int",
                        "minimum" => $vb_unsigned ? 0 : -128,
                        "maximum" => $vb_unsigned ? 255 : 127
                    );
                    break;
                case 'decimal':
                case 'float':
                case 'numeric':
                    $va_tmp = explode(",", $vs_length);
                    if ($vb_unsigned) {
                        $vn_max = (pow(10, $va_tmp[0]) - (1 / pow(10, $va_tmp[1]))) - 1;
                        $vn_min = 0;
                    } else {
                        $vn_max = ((pow(10, $va_tmp[0]) - (1 / pow(10, $va_tmp[1]))) / 2) - 1;
                        $vn_min = -1 * ((pow(10, $va_tmp[0]) - (1 / pow(10, $va_tmp[1]))) / 2);
                    }
                    return array("type" => "float", "minimum" => $vn_min, "maximum" => $vn_max);
                    break;
                case 'tinytext':
                    return array("type" => "varchar", "length" => 255);
                    break;
                case 'text':
                    return array("type" => "text", "length" => pow(2, 16) - 1);
                    break;
                case 'mediumtext':
                    return array("type" => "text", "length" => pow(2, 24) - 1);
                    break;
                case 'longtext':
                    return array("type" => "text", "length" => pow(2, 32) - 1);
                    break;
                case 'tinyblob':
                    return array("type" => "blob", "length" => 255);
                    break;
                case 'blob':
                    return array("type" => "blob", "length" => pow(2, 16) - 1);
                    break;
                case 'mediumblob':
                    return array("type" => "blob", "length" => pow(2, 24) - 1);
                    break;
                case 'longblob':
                    return array("type" => "blob", "length" => pow(2, 32) - 1);
                    break;
                default:
                    return null;
                    break;
            }
        } else {
            return null;
        }
    }
}
