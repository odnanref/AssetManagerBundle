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

namespace Far\AssetManagerBundle\Entity;

trait TautoFill {

    public function getAndInsert($val)
    {
        $this->_em->getConnection()->beginTransaction();

        $valtmp = strtoupper(str_replace(' ','',trim($val)));
        $Res = $this->findBy(['code' => $valtmp]);
        if (count($Res) > 0 && $Res[0] != null) {
            $obj = $Res[0];
            $obj->setTcount($obj->getTcount()+1);
            $this->_em->persist($obj);

            $this->_em->flush();

        } else {
            $tmp = $this->getClassName();
            $obj = new $tmp();
            $obj->setDescription($val);
            $obj->setCode($valtmp);
            $obj->setTcount(1);
            // persist
            $this->_em->persist($obj);
            $this->_em->flush();
        }

        $this->_em->getConnection()->commit();
        return $obj;
    }
}

