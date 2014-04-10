<?php

/**
 * This file belongs to the Far/AssetManagerBundle
 *
 * Copyright (C) 2013  Fernando André <netriver at gmail dot com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Far\AssetManagerBundle\Service;

ini_set("memory_limit","1224M");

use Far\AssetManagerBundle\Entity\Item;

class ImportCsv
{
    private $erm;

    private $has_header = true;

    public $filename;

    private $output = null;

    private $separator = ';';

    public function __construct(array $csv, array $options = null)
    {
        $this->filename = "CSVExport_" . date("Y-m-d") . ".csv";
        $separator = ';';

        if ($options != null && count($options) > 0) {
            if (array_key_exists("separator", $options)) {
                $this->separator = $options['separator'];
            }
            if (array_key_exists("has-header", $options)) {
                $this->has_header = $options['has_header'];
            }
            if (array_key_exists("filename", $options)) {
                $this->filename = $options['filename']
            }
        }

        $row = 0;
        $this->output = $this->array2csv($csv);

        if (is_writable($this->filename)) {
            $handle = fopen($this->filename, "w");
            fwrite($handle, $this->output);
            fclose($handle);
        }
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function array2csv(array $array)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row, $this->separator);
        }
        fclose($df);
        return ob_get_clean();
    }

}
