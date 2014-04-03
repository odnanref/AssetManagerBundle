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

    public function __construct($csv, array $options, $erm = null )
    {
        if (count($options) > 0) {
            if (array_key_exists("separator", $options)) {
                $separator = $options['separator'];
            }
            if (array_key_exists("has-header", $options)) {
                $this->has_header = $options['has_header'];
            }
        } else {
            $separator = ';';
        }

        $this->erm = $erm;
        $row = 0;
        if (($handle = fopen($csv, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                if ($this->has_header === true ) {
                    $this->has_header = false;
                    continue;
                }
                $num = count($data);
                $row++;
                // handle fields
                $this->treatRow($data);
                if (($row%1000) == 0) {
                    $this->erm->flush();
                }
            }
            fclose($handle);
            $this->erm->flush();
        }
    }

    public function exists($defid)
    {
        $res = $this->erm->getRepository('FarAssetManagerBundle:Item')
            ->findByDefid($defid);
        return $res;
    }

    public function treatRow($dataRow)
    {
        if ( ($res = $this->exists($dataRow[2])) ) {
            print "recovering: " .$dataRow[2] . PHP_EOL;
            $item = $res[0];
        } else {
            $item = new Item();
        }

        $item->setNcmReference($this->getObject($dataRow[0], 'ncm_reference')); // treat has secondary
        $item->setDepreciation((double)$dataRow[1]);
        $item->setDefId($dataRow[2]);
        $item->setQt($dataRow[3]);
        $item->setDescription($dataRow[4]);
        $item->setCode($dataRow[5]);
        $item->setAquiredtype($this->getObject($dataRow[6], 'aquiredtype')); // treat has secondary
        $item->setValueUnit($dataRow[7]);
        $item->setTypeofItem($this->getObject($dataRow[8], 'typeofItem_id')); // treat has secondary
        $item->setState($this->getObject($dataRow[9], 'state')); // treat has second
        $item->setLocation( $this->getObject($dataRow[10], 'location')); // treat has second
        if ( trim($dataRow[11]) != '') {
            $dataRow[11] = \DateTime::createFromFormat("d/m/Y", $dataRow[11]);
            $item->setDatain($dataRow[11]);
        }

        $item->setProtocol($this->getObject($dataRow[12],'protocol')); // treat has second
        $item->setProtocolCode($dataRow[13]);
        $item->setSupplierNoteNumber($dataRow[14]);
        $item->setSupplier($dataRow[15]);
        $item->setEan128($dataRow[16]);

        $this->erm->persist($item);
    }

    public function getObject($val, $type = 'ncm_reference')
    {
        switch($type) {
            case 'ncm_reference':
                return $this->erm->getRepository("FarAssetManagerBundle:NcmReference")
                    ->getAndInsert($val);
                break;
            case 'aquiredtype':
                return $this->erm->getRepository("FarAssetManagerBundle:Aquiredtype")
                    ->getAndInsert($val);
                break;
            case 'typeofItem_id':
                return $this->erm->getRepository("FarAssetManagerBundle:Typeofitem")
                    ->getAndInsert($val);
                break;
            case 'state':
                return $this->erm->getRepository("FarAssetManagerBundle:State")
                    ->getAndInsert($val);
                break;
            case 'location':
                return $this->erm->getRepository("FarAssetManagerBundle:Location")
                    ->getAndInsert($val);
                break;
            case 'protocol':
                return $this->erm->getRepository("FarAssetManagerBundle:Protocol")
                    ->getAndInsert($val);
                break;
        }
    }

}
