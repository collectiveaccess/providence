<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLITools.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2020 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 *
 */

require_once(__CA_LIB_DIR__ . '/Utils/CLIBaseUtils.php');
require_once(__CA_APP_DIR__ . "/helpers/utilityHelpers.php");
require_once(__CA_LIB_DIR__ . "/Import/DataReaders/FMPXMLResultReader.php");
require_once(__CA_LIB_DIR__ . "/Import/DataReaders/PastPerfectXMLReader.php");

class CLITools extends CLIBaseUtils
{
    # -------------------------------------------------------
    # Create a profile <list> element from an Excel spreadsheet.
    # -------------------------------------------------------
    /**
     *
     */
    public static function make_list_from_excel($po_opts = null)
    {
        $vs_filepath = (string)$po_opts->getOption('file');
        if (!$vs_filepath) {
            CLITools::addError(_t("You must specify a file", $vs_filepath));
            return false;
        }
        if (!file_exists($vs_filepath)) {
            CLITools::addError(_t("File '%1' does not exist", $vs_filepath));
            return false;
        }

        if ($vs_output_path = (string)$po_opts->getOption('out')) {
            if (!is_writeable(pathinfo($vs_output_path, PATHINFO_DIRNAME))) {
                CLITools::addError(_t("Cannot write to %1", $vs_output_path));
                return false;
            }
        }

        $item_value_col = (string)$po_opts->getOption('itemValueColumn');
        $item_desc_col = (string)$po_opts->getOption('itemDescriptionColumn');
        $np_label_col = (string)$po_opts->getOption('nonPreferredLabelsColumn');

        $locale = (string)$po_opts->getOption('locale');
        if (!preg_match("!^[A-Za-z]{2,3}_[A-Za-z]{2,3}$!", $locale)) {
            $locale = 'en_US';
        }

        $vn_skip = (int)$po_opts->getOption('skip');

        $o_handle = IOFactory::load($vs_filepath);
        $o_sheet = $o_handle->getActiveSheet();


        $vn_c = 0;
        $vn_last_level = 0;
        $va_list = array();
        $va_stack = array(&$va_list);

        foreach ($o_sheet->getRowIterator() as $o_row) {
            $vn_c++;
            if ($vn_skip >= $vn_c) {
                continue;
            }

            $va_row = array();
            $o_cells = $o_row->getCellIterator();
            $o_cells->setIterateOnlyExistingCells(false);

            $vn_col = 0;
            foreach ($o_cells as $c => $o_cell) {
                if ($label = trim((string)$o_cell->getValue())) {
                    $item_value = (strlen($item_value_col)) ? $o_sheet->getCellByColumnAndRow(
                        $item_value_col,
                        $vn_c
                    )->getValue() : null;
                    $item_description = (strlen($item_desc_col)) ? $o_sheet->getCellByColumnAndRow(
                        $item_desc_col,
                        $vn_c
                    )->getValue() : null;
                    $np_labels = (strlen($np_label_col)) ? $o_sheet->getCellByColumnAndRow(
                        $np_label_col,
                        $vn_c
                    )->getValue() : null;

                    if ($vn_col > $vn_last_level) {
                        $va_stack[] = &$va_stack[sizeof($va_stack) - 1][sizeof(
                            $va_stack[sizeof($va_stack) - 1]
                        ) - 1]['subitems'];
                    } elseif ($vn_col < $vn_last_level) {
                        while (sizeof($va_stack) && ($va_stack[sizeof($va_stack) - 1][0]['level'] > $vn_col)) {
                            array_pop($va_stack);
                        }
                    }
                    $va_stack[sizeof($va_stack) - 1][] = array(
                        'label' => $label,
                        'subitems' => array(),
                        'level' => $vn_col,
                        'item_value' => $item_value,
                        'item_description' => $item_description,
                        'nonPreferredLabels' => $np_labels
                    );
                    $vn_last_level = $vn_col;

                    $vn_col++;
                    break;
                }

                $vn_col++;
            }
        }

        $vs_output = "<list code=\"LIST_CODE_HERE\" hierarchical=\"0\" system=\"0\" vocabulary=\"0\">\n";
        $vs_output .= "\t<labels>
		<label locale=\"{$locale}\">
			<name>ListNameHere</name>
		</label>
	</labels>\n";
        $vs_output .= "<items>\n";
        $vs_output .= CLITools::_makeList($va_list, 1, null, $locale);
        $vs_output .= "</items>\n";
        $vs_output .= "</list>\n";

        if ($vs_output_path) {
            file_put_contents($vs_output_path, $vs_output);
            CLITools::addMessage(_t("Wrote output to %1", $vs_output_path));
        } else {
            print $vs_output;
        }
        return true;
    }

    # -------------------------------------------------------
    private static function _makeList($pa_list, $pn_indent = 0, $pa_stack = null, $locale = null)
    {
        if (!is_array($pa_stack)) {
            $pa_stack = array();
        }
        if ($locale) {
            $locale = 'en_US';
        }

        $vn_ident = $pn_indent ? str_repeat("\t", $pn_indent) : '';
        $vs_buf = '';
        foreach ($pa_list as $vn_i => $va_item) {
            $vs_label = caEscapeForXML($va_item['label']);
            $vs_item_value = caEscapeForXML($va_item['item_value']);
            $vs_item_desc = caEscapeForXML($va_item['item_description']);
            $vs_label_proc = preg_replace("![^A-Za-z0-9]+!", "_", $vs_label);

            $np_label_list = array_map(
                function ($v) {
                    return caEscapeForXML(trim($v));
                },
                preg_split("![;]+!", $va_item['nonPreferredLabels'])
            );

            $non_preferred_labels = '';
            foreach ($np_label_list as $np) {
                if (!$np) {
                    continue;
                }
                $non_preferred_labels .= "<label locale=\"{$locale}\" preferred=\"0\"><name_singular>{$np}</name_singular><name_plural>{$np}</name_plural></label>";
            }

            if ($vs_label_prefix = join('_', $pa_stack)) {
                $vs_label_prefix .= '_';
            }
            $vs_buf .= "{$vn_ident}<item idno=\"{$vs_label_prefix}{$vs_label_proc}\" enabled=\"1\" default=\"0\" value=\"{$vs_item_value}\">
{$vn_ident}\t<labels>
{$vn_ident}\t\t<label locale=\"{$locale}\" preferred=\"1\">
{$vn_ident}\t\t\t<name_singular>{$vs_label}</name_singular>
{$vn_ident}\t\t\t<name_plural>{$vs_label}</name_plural>
{$vn_ident}\t\t\t<description>{$vs_item_desc}</description>
{$vn_ident}\t\t</label>
{$vn_ident}{$non_preferred_labels}
{$vn_ident}\t</labels>" .
                ((is_array($va_item['subitems']) && sizeof(
                        $va_item['subitems']
                    )) ? "{$vn_ident}\t<items>\n{$vn_indent}" . CLITools::_makeList(
                        $va_item['subitems'],
                        $pn_indent + 2,
                        array_merge(
                            $pa_stack,
                            array(
                                substr(
                                    $vs_label_proc,
                                    0,
                                    10
                                )
                            )
                        ),
                        $locale
                    ) . "{$vn_ident}\t</items>" : '') . "
{$vn_ident}</item>\n";
        }

        return $vs_buf;
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_list_from_excelParamList()
    {
        return array(
            "file|f-s" => _t('Excel file to convert to profile list.'),
            "out|o-s" => _t('File to write output to.'),
            "skip|s-s" => _t('Number of rows to skip before reading data.'),
            "locale|l-s" => _t('ISO locale code to use. Default is en_US.'),
            "itemValueColumn|x=s" => _t('Column number to use for item values. Omit to not set item values.'),
            "itemDescriptionColumn|y=s" => _t(
                'Column number to use for item descriptions. Omit to not set item descriptions.'
            ),
            "nonPreferredLabelsColumn|z=s" => _t(
                'Column number to use for item nonpreferred labels. Omit to not set nonpreferred labels.'
            ),
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_list_from_excelUtilityClass()
    {
        return _t('Profile development tools');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_list_from_excelHelp()
    {
        return _t(
            "Create a profile <list> element from an Excel spreadsheet. Your list should have one list item per row, with hierarchical level indicated by indentation. For example, if you want to have a list with A, B, C, D, E and F, with B and C sub-items of A and F a sub-item of E your Excel document should look like this:\n\n\tA\n\t\tB\n\t\tC\n\tD\n\tE\n\t\tF\n\n\tIf your Excel document has column headers you can skip them by specifying the number of rows to skip using the \"skip\" option."
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_list_from_excelShortHelp()
    {
        return _t("Create a profile <list> element from an Excel spreadsheet. ");
    }
    # -------------------------------------------------------
    # Create a profile <relationshipTable> element from an Excel spreadsheet.
    # -------------------------------------------------------
    /**
     *
     */
    public static function make_relationship_types_from_excel($po_opts = null)
    {
        $vs_filepath = (string)$po_opts->getOption('file');
        if (!$vs_filepath) {
            CLITools::addError(_t("You must specify a file", $vs_filepath));
            return false;
        }
        if (!file_exists($vs_filepath)) {
            CLITools::addError(_t("File '%1' does not exist", $vs_filepath));
            return false;
        }

        if ($vs_output_path = (string)$po_opts->getOption('out')) {
            if (!is_writeable(pathinfo($vs_output_path, PATHINFO_DIRNAME))) {
                CLITools::addError(_t("Cannot write to %1", $vs_output_path));
                return false;
            }
        }

        $vn_skip = (int)$po_opts->getOption('skip');

        $o_handle = IOFactory::load($vs_filepath);
        $o_sheet = $o_handle->getActiveSheet();


        $vn_c = 0;
        $vn_last_level = 0;
        $va_list = array();
        $va_stack = array(&$va_list);

        foreach ($o_sheet->getRowIterator() as $o_row) {
            $vn_c++;
            if ($vn_skip >= $vn_c) {
                continue;
            }

            $va_row = array();
            $o_cells = $o_row->getCellIterator();
            $o_cells->setIterateOnlyExistingCells(false);

            $vn_col = 0;
            foreach ($o_cells as $o_cell) {
                if ($vs_val = trim((string)$o_cell->getValue())) {
                    if ($vn_col > $vn_last_level) {
                        $va_stack[] = &$va_stack[sizeof($va_stack) - 1][sizeof(
                            $va_stack[sizeof($va_stack) - 1]
                        ) - 1]['subitems'];
                    } elseif ($vn_col < $vn_last_level) {
                        while (sizeof($va_stack) && ($va_stack[sizeof($va_stack) - 1][0]['level'] > $vn_col)) {
                            array_pop($va_stack);
                        }
                    }
                    $va_stack[sizeof($va_stack) - 1][] = array(
                        'value' => $vs_val,
                        'subitems' => array(),
                        'level' => $vn_col
                    );
                    $vn_last_level = $vn_col;

                    $vn_col++;
                    break;
                }

                $vn_col++;
            }
        }

        $locale = 'en_US';

        $vs_output = "<relationshipTable name=\"TABLE_CODE_HERE\">\n\t<types>\n";
        $vs_output .= CLITools::_makeTypeList($va_list, 2);
        $vs_output .= "\t</types>\n</relationshipTable>\n";

        if ($vs_output_path) {
            file_put_contents($vs_output_path, $vs_output);
            CLITools::addMessage(_t("Wrote output to %1", $vs_output_path));
        } else {
            print $vs_output;
        }
        return true;
    }

    # -------------------------------------------------------
    private static function _makeTypeList($pa_list, $pn_indent = 0, $pa_stack = null)
    {
        if (!is_array($pa_stack)) {
            $pa_stack = [];
        }
        $locale = 'en_US';
        $vn_ident = $pn_indent ? str_repeat("\t", $pn_indent) : '';
        $vs_buf = '';
        foreach ($pa_list as $vn_i => $va_item) {
            $labels = explode(";", caEscapeForXML($va_item['value']));
            $label_forward = trim($labels[0]);
            $label_reverse = (isset($labels[1]) && strlen($labels[1]) ? $labels[1] : $labels[0]);
            if (!strlen($label_forward) || !strlen($label_reverse)) {
                continue;
            }

            $label_proc = preg_replace("![^A-Za-z0-9]+!", "_", $label_forward);
            if ($label_prefix = join('_', $pa_stack)) {
                $label_prefix .= '_';
            }
            $vs_buf .= "{$vn_ident}<type code=\"{$label_prefix}{$label_proc}\" default=\"0\">
{$vn_ident}\t<labels>
{$vn_ident}\t\t<label locale=\"{$locale}\">
{$vn_ident}\t\t\t<typename>{$label_forward}</typename>
{$vn_ident}\t\t\t<typename_reverse>{$label_reverse}</typename_reverse>
{$vn_ident}\t\t</label>
{$vn_ident}\t</labels>\n" .
                ((is_array($va_item['subitems']) && sizeof(
                        $va_item['subitems']
                    )) ? "{$vn_ident}\t<types>\n{$vn_indent}" . CLITools::_makeTypeList(
                        $va_item['subitems'],
                        $pn_indent + 2,
                        array_merge(
                            $pa_stack,
                            array(
                                substr(
                                    $vs_label_proc,
                                    0,
                                    10
                                )
                            )
                        )
                    ) . "{$vn_ident}\t</types>" : '') . "
{$vn_ident}</type>\n";
        }

        return $vs_buf;
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_relationship_types_from_excelParamList()
    {
        return array(
            "file|f-s" => _t('Excel file to convert to profile <relationshipTable> element.'),
            "out|o-s" => _t('File to write output to.'),
            "skip|s-s" => _t('Number of rows to skip before reading data.')
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_relationship_types_from_excelUtilityClass()
    {
        return _t('Profile development tools');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_relationship_types_from_excelHelp()
    {
        return _t(
            "Create a profile <relationshipTable> element from an Excel spreadsheet. Your relationship type list should have one type per row, with hierarchical level indicated by indentation. Row text will be used for both forward and reverse types. If you wish to have different text for forward and reverse separate the text for each with a semicolon. For example, if you want to have a list with A, B, C, D, E and F, with B and C sub-items of A and F a sub-item of E your Excel document should look like this:\n\n\tA\n\t\tB\n\t\tC\n\tD\n\tE\n\t\tF\n\n\tIf your Excel document has column headers you can skip them by specifying the number of rows to skip using the \"skip\" option."
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function make_relationship_types_from_excelShortHelp()
    {
        return _t("Create a profile <relationshipTable> element from an Excel spreadsheet. ");
    }
    # -------------------------------------------------------
    # Get EXIF tags
    # -------------------------------------------------------
    /**
     *
     */
    public static function get_exif_tags($po_opts = null)
    {
        require_once(__CA_LIB_DIR__ . "/Import/DataReaders/ExifDataReader.php");

        $vs_directory_path = (string)$po_opts->getOption('directory');
        if (!$vs_directory_path) {
            CLITools::addError(_t("You must specify a directory", $vs_directory_path));
            return false;
        }
        if (!file_exists($vs_directory_path)) {
            CLITools::addError(_t("Directory '%1' does not exist", $vs_directory_path));
            return false;
        }
        if (!is_dir($vs_directory_path)) {
            CLITools::addError(_t("'%1' is not a directory", $vs_directory_path));
            return false;
        }
        if (!($vs_output_path = (string)$po_opts->getOption('out'))) {
            CLITools::addError(_t("You must specify an output file"));
            return false;
        }
        if (!is_writeable(pathinfo($vs_output_path, PATHINFO_DIRNAME))) {
            CLITools::addError(_t("Cannot write to %1", $vs_output_path));
            return false;
        }

        if (!caExifToolInstalled()) {
            CLITools::addError(_t("ExifTool external application is required but not installed on this server."));
            return false;
        }

        $va_file_list = caGetDirectoryContentsAsList($vs_directory_path);

        $o_reader = new ExifDataReader();

        $va_tag_list = [];
        $va_sample_data = [];
        foreach ($va_file_list as $vs_file_path) {
            if ($o_reader->read($vs_file_path) && $o_reader->nextRow()) {
                $va_tag_groups = $o_reader->getRow();

                foreach ($va_tag_groups as $vs_group => $va_tags) {
                    if (is_array($va_tags)) {
                        foreach ($va_tags as $vs_tag => $vs_value) {
                            $va_tag_list["{$vs_group}/{$vs_tag}"]++;
                            $va_sample_data["{$vs_group}/{$vs_tag}"][(string)$vs_value]++;
                        }
                    } else {
                        $va_tag_list[$vs_group]++;
                        $va_sample_data[$vs_group][$vs_value]++;
                    }
                }
            }
        }

        // output tags
        $va_output = ["Tag\tUsed in # files\tTop 3 values"];
        foreach ($va_tag_list as $vs_tag => $vn_count) {
            $va_values = $va_sample_data[$vs_tag];
            asort($va_values, SORT_NUMERIC);
            $va_output[] = "\"{$vs_tag}\"\t\"{$vn_count}\"\t\"" . join(
                    "; ",
                    array_slice(array_keys($va_values), 0, 3)
                ) . "\"";
        }
        file_put_contents($vs_output_path, join("\n", $va_output));
        CLITools::addMessage(_t("Wrote output to '%1'", $vs_output_path));
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function get_exif_tagsParamList()
    {
        return array(
            "directory|d-s" => _t('Directory containing images to examine.'),
            "out|o-s" => _t('File to write tab-delimited output to.')
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function get_exif_tagsUtilityClass()
    {
        return _t('Profile development tools');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function get_exif_tagsHelp()
    {
        return _t(
            "Analyzes a directory of images and returns a tab-delimited list of import-mapping compatible EXIF and IPTC tags containing data."
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function get_exif_tagsShortHelp()
    {
        return _t(
            "Analyzes a directory of images and returns a tab-delimited list of import-mapping compatible EXIF and IPTC tags containing data."
        );
    }
    # -------------------------------------------------------
    # Convert XML files to delimited
    # -------------------------------------------------------
    /**
     *
     */
    public static function convert_xml_to_delimited($po_opts = null)
    {
        $file_path = (string)$po_opts->getOption('file');
        if (!$file_path) {
            CLITools::addError(_t("You must specify a file", $file_path));
            return false;
        }
        if (!file_exists($file_path)) {
            CLITools::addError(_t("File '%1' does not exist", $file_path));
            return false;
        }
        if (is_dir($file_path)) {
            CLITools::addError(_t("'%1' must not be a directory", $file_path));
            return false;
        }
        if (!($output_path = (string)$po_opts->getOption('out'))) {
            CLITools::addError(_t("You must specify an output file"));
            return false;
        }
        if (!is_writeable(pathinfo($output_path, PATHINFO_DIRNAME))) {
            CLITools::addError(_t("Cannot write to %1", $output_path));
            return false;
        }

        $input_format = strtolower((string)$po_opts->getOption('format'));

        switch ($input_format) {
            case 'fmpxml':
            case 'fmpxmlresult':
                $reader = new FMPXMLResultReader();
                break;
            case 'pastperfect':
            case 'pp':
                $reader = new PastPerfectXMLReader();
                break;
            default:
                if ($input_format) {
                    CLITools::addError(_t("%1 is not a valid input format. Defaulting to PastPerfect.", $input_format));
                } else {
                    CLITools::addMessage(_t("No input format was specified. Defaulting to PastPerfect."));
                }
                $reader = new PastPerfectXMLReader();
                break;
        }

        $output_format = strtolower((string)$po_opts->getOption('outputFormat'));
        if (!in_array($output_format, ['csv', 'tab'])) {
            CLITools::addMessage(_t("No output format was specified. Defaulting to CSV."));
            $output_format = 'csv';
        }

        $reader->read($file_path);

        $headers = [];
        $c = 0;

        $delimiter = ($output_format === 'csv') ? "," : "\t";

        if (!($fp = fopen($output_path, "w"))) {
            CLITools::addError(_t("Could not open output file %1", $output_path));
            return;
        }

        while ($reader->nextRow()) {
            $row = $reader->getRow();
            if ($c == 0) {
                $headers = array_keys($row);
            }
            $line = array_values($row);

            if ($c == 0) {
                fwrite(
                    $fp,
                    join(
                        $delimiter,
                        array_map(
                            function ($v) {
                                if (is_array($v)) {
                                    $v = join("|", $v);
                                }
                                return '"' . str_replace('"', '""', trim($v)) . '"';
                            },
                            $headers
                        )
                    ) . "\n"
                );
            }
            fwrite(
                $fp,
                join(
                    $delimiter,
                    array_map(
                        function ($v) {
                            if (is_array($v)) {
                                $v = join("|", $v);
                            }
                            return '"' . str_replace('"', '""', trim($v)) . '"';
                        },
                        $line
                    )
                ) . "\n"
            );

            $c++;
        }
        fclose($fp);

        CLITools::addMessage(_t("Wrote output to '%1'", $output_path));
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function convert_xml_to_delimitedParamList()
    {
        return array(
            "file|f-s" => _t('XML file to convert.'),
            "out|o-s" => _t('File to write delimited output to.'),
            "format|i=s" => _t(
                'XML format of input file. Valid options are "PastPerfect" (PastPerfect XML export files), "FMPXML" (FileMaker Pro XML Result format).'
            ),
            "outputFormat|t=s" => _t('Format of output (CSV or tab-delimited). Default is CSV.'),
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function convert_xml_to_delimitedUtilityClass()
    {
        return _t('Data conversion tools');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function convert_xml_to_delimitedHelp()
    {
        return _t("Convert selected tags in an XML file to delimited text (CSV or tab-delimited).");
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function convert_xml_to_delimitedShortHelp()
    {
        return _t(
            "Convert selected tags in an XML file to delimited text (CSV or tab-delimited). All sub-tags of selected tags are output into delimited rows of data. The input format \"PastPerfect\" will employ the XPath expression //export to select all <export> tags and output data tags contained within into rows of delimited data."
        );
    }
    # -------------------------------------------------------
    # Filter invalid characters thay may be embedded in XML files.
    # PastPerfect loves to do this.
    # -------------------------------------------------------
    /**
     *
     */
    public static function filter_invalid_xml_characters($po_opts = null)
    {
        $file_path = (string)$po_opts->getOption('file');
        if (!$file_path) {
            CLITools::addError(_t("You must specify a file", $file_path));
            return false;
        }
        if (!file_exists($file_path)) {
            CLITools::addError(_t("File '%1' does not exist", $file_path));
            return false;
        }
        if (is_dir($file_path)) {
            CLITools::addError(_t("'%1' must not be a directory", $file_path));
            return false;
        }
        if (!($output_path = (string)$po_opts->getOption('out'))) {
            CLITools::addError(_t("You must specify an output file"));
            return false;
        }
        if (!is_writeable(pathinfo($output_path, PATHINFO_DIRNAME))) {
            CLITools::addError(_t("Cannot write to %1", $output_path));
            return false;
        }

        if (!($fp_in = fopen($file_path, "r"))) {
            CLITools::addError(_t("Could not open input file %1", $file_path));
            return;
        }
        if (!($fp_out = fopen($output_path, "w"))) {
            CLITools::addError(_t("Could not open output file %1", $output_path));
            return;
        }
        $l = 1;
        while (($line = fgets($fp_in)) !== false) {
            if (preg_match(
                "![^\x{0009}\x{000A}\x0D\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+!u",
                $line,
                $m
            )) {
                CLITools::addError(_t("Found invalid character at line %1: %2", $l, $line));
                $line = preg_replace(
                    "![^\x{0009}\x{000A}\x0D\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+!u",
                    "",
                    $line
                );
            }
            fputs($fp_out, $line);
            $l++;
        }
        fclose($fp_in);
        fclose($fp_out);

        CLITools::addMessage(_t("Wrote output to '%1'", $output_path));
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function filter_invalid_xml_charactersParamList()
    {
        return array(
            "file|f-s" => _t('XML file to convert.'),
            "out|o-s" => _t('File to write filtered output to.')
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function filter_invalid_xml_charactersUtilityClass()
    {
        return _t('Data conversion tools');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function filter_invalid_xml_charactersHelp()
    {
        return _t("Remove invalid characters from an XML file.");
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function filter_invalid_xml_charactersShortHelp()
    {
        return _t(
            "Filters invalid characters from XML files that may prevent parsing and validation. Some XML producers occassionally add these invalid characters due to improper validation of source data or unintended behavior."
        );
    }
    # -------------------------------------------------------
}
