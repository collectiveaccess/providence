<?php
/** ---------------------------------------------------------------------
 * iDigBioDataReader.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */
require_once(__CA_LIB_DIR__ . '/Import/BaseDataReader.php');

// Pull in Guzzle library (web services client)
require_once(__CA_BASE_DIR__ . '/vendor/autoload.php');

use GuzzleHttp\Client;


class iDigBioDataReader extends BaseDataReader
{
    # -------------------------------------------------------
    private $items = null;
    private $row_buf = [];
    private $current_row = 0;   // row index within entire dataset
    private $current_offset = 0; // row index within current frame

    private $client = null;

    private $source = null;
    private $start = 0;
    private $limit = 500;
    private $total_items = null;

    # -------------------------------------------------------

    /**
     *
     */
    public function __construct($source = null, $options = null)
    {
        parent::__construct($source, $options);

        $this->ops_title = _t('iDigBio data reader');
        $this->ops_display_name = _t('iDigBio');
        $this->ops_description = _t(
            'Reads data from the iDigBio data service (http://idigbio.org) using the version 2 API'
        );

        $this->opa_formats = array('idigbio');    // must be all lowercase to allow for case-insensitive matching


    }
    # -------------------------------------------------------

    /**
     *
     *
     * @param string $source MySQL URL
     * @param array $options
     * @return bool
     */
    public function read($source, $options = null)
    {
        parent::read($source, $options);

        $this->current_row = -1;
        $this->current_offset = -1;
        $this->items = [];

        $this->source = $source;
        $this->start = 0;

        $this->getData();

        return true;
    }
    # -------------------------------------------------------

    /**
     *
     *
     */
    private function getData()
    {
        try {
            $this->client = new Client();
            $url = "https://search.idigbio.org/v2/search/records?limit=" . (int)$this->limit . "&offset=" . (int)$this->start . "&rq=" . urlencode(
                    $this->source
                );

            $response = $this->client->request("GET", $url);

            $data = json_decode($response->getBody(), true);

            if (is_array($data) && isset($data['itemCount']) && ((int)$data['itemCount'] > 0) && is_array(
                    $data['items']
                )) {
                $this->total_items = $data['itemCount'];
                $data = $data['items'];

                // get related media ids
                $media_ids = [];
                foreach ($data as $di => $r) {
                    if (is_array($r['indexTerms']['mediarecords']) && sizeof($r['indexTerms']['mediarecords'])) {
                        foreach ($r['indexTerms']['mediarecords'] as $mi => $media_id) {
                            $media_ids[] = $media_id;
                        }
                        if (is_array($media_info = $this->getMedia(array_unique($media_ids))) && is_array(
                                $media_info['items']
                            ) && sizeof($media_info['items'])) {
                            $data[$di]['data']['media'] = $media_info['items'];
                        }
                    } elseif (isset($r['data']['dwc:associatedMedia']) && strlen($r['data']['dwc:associatedMedia'])) {
                        // add media
                        $data[$di]['data']['media'][] = [
                            'data' => ['ac:accessURI' => $r['data']['dwc:associatedMedia']]
                        ];
                    }
                }
                $this->start += sizeof($data);
                $this->items = $data;
                $this->current_offset = -1;

                return $data;
            }
        } catch (Exception $e) {
            return false;
        }
    }
    # -------------------------------------------------------

    /**
     *
     *
     */
    private function getMedia($ids)
    {
        try {
            $this->client = new Client();
            $url = "https://search.idigbio.org/v2/search/media?mq=" . json_encode(['uuid' => $ids], true);

            $response = $this->client->request("GET", $url);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return false;
        }
    }
    # -------------------------------------------------------

    /**
     *
     * @return bool
     */
    public function nextRow()
    {
        if (!$this->items || !is_array($this->items) || !sizeof($this->items)) {
            return false;
        }

        $this->current_offset++;

        if (isset($this->items[$this->current_offset]) && is_array($this->items[$this->current_offset])) {
            $this->current_row++;
            $this->row_buf = $this->items[$this->current_offset]['data'];
            return true;
        } elseif ($this->current_row < $this->total_items) {
            // get next frame
            $this->current_offset--;
            if ($this->getData()) {
                return $this->nextRow();
            }
        }
        return false;
    }
    # -------------------------------------------------------

    /**
     *
     *
     * @param int $row_num
     * @return bool
     */
    public function seek($row_num)
    {
        $row_num = (int)$row_num;

        if (($row_num >= 0) && ($row_num < $this->total_items)) {
            $this->current_row = $row_num;
            $this->start = $row_num;
            return (bool)$this->getData();
        }
        return false;
    }
    # -------------------------------------------------------

    /**
     *
     *
     * @param mixed $col
     * @param array $options
     * @return mixed
     */
    public function get($col, $options = null)
    {
        $return_as_array = caGetOption('returnAsArray', $options, false);
        $delimiter = caGetOption('delimiter', $options, ';');

        if ($vm_ret = parent::get($col, $options)) {
            return $vm_ret;
        }

        if (substr($col, 0, 6) === 'media:') {
            $spec = substr($col, 6);
            $media = $this->row_buf['media'];
            if (is_array($media)) {
                $d = array_map(
                    function ($v) use ($spec) {
                        return $v['data'][$spec];
                    },
                    array_filter(
                        $media,
                        function ($v) use ($spec) {
                            return isset($v['data'][$spec]);
                        }
                    )
                );
                return $return_as_array ? $d : join($delimiter, $d);
            }
        }

        if (is_array($this->row_buf) && ($col) && (isset($this->row_buf[$col]))) {
            if ($return_as_array) {
                return [$this->row_buf[$col]];
            } else {
                return $this->row_buf[$col];
            }
        }
        return null;
    }
    # -------------------------------------------------------

    /**
     *
     *
     * @return mixed
     */
    public function getRow($options = null)
    {
        if (isset($this->items[$this->current_offset]) && is_array($row = $this->items[$this->current_offset])) {
            return array_map(
                function ($v) {
                    return !is_array($v) ? [$v] : $v;
                },
                $row['data']
            );
        }

        return null;
    }
    # -------------------------------------------------------

    /**
     *
     *
     * @return int
     */
    public function numRows()
    {
        return $this->total_items; //is_array($this->items) ? sizeof($this->items) : 0;
    }
    # -------------------------------------------------------

    /**
     *
     *
     * @return int
     */
    public function currentRow()
    {
        return $this->current_row;
    }
    # -------------------------------------------------------

    /**
     *
     *
     * @return int
     */
    public function getInputType()
    {
        return __CA_DATA_READER_INPUT_TEXT__;
    }
    # -------------------------------------------------------

    /**
     * Values can repeat for CollectiveAccess data sources
     *
     * @return bool
     */
    public function valuesCanRepeat()
    {
        return true;
    }
    # -------------------------------------------------------
}
