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

use Doctrine\ORM\Mapping as ORM;

/**
 * Item
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Far\AssetManagerBundle\Entity\ItemRepository")
 */
class Item
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="defid", type="integer")
     */
    private $defid;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="value_unit", type="float", nullable=true)
     */
    private $valueUnit = 0.01;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datain", type="date", nullable=true)
     */
    private $datain;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dataviewed", type="date", nullable=true)
     */
    private $dataviewed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dataout", type="date", nullable=true)
     */
    private $dataout;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Far\AssetManagerBundle\Entity\Location")
     * @ORM\JoinColumns(
     *  {@ORM\JoinColumn(name="location_id", referencedColumnName="id")}
     * )
     */
    private $location;

    /**
     * @var float
     *
     * @ORM\Column(name="qt", type="float")
     */
    private $qt = 1;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Far\AssetManagerBundle\Entity\State")
     * @ORM\JoinColumns({@ORM\JoinColumn(name="state_id", referencedColumnName="id")})
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Far\AssetManagerBundle\Entity\Typeofitem")
     * @ORM\JoinColumns({@ORM\JoinColumn(name="typeofItem_id", referencedColumnName="id")})
     */
    private $typeofItem;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Far\AssetManagerBundle\Entity\Aquiredtype")
     * @ORM\JoinColumns({@ORM\JoinColumn(name="aquiredtype_id", referencedColumnName="id")})
     */
    private $aquiredtype;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Far\AssetManagerBundle\Entity\Protocol")
     * @ORM\JoinColumns({@ORM\JoinColumn(name="protocol_id", referencedColumnName="id")})
     */
    private $protocol;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="protocol_code", type="string", length=255, nullable=true)
     */
    private $protocolCode;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="Far\AssetManagerBundle\Entity\NcmReference")
     * @ORM\JoinColumns({@ORM\JoinColumn(name="ncm_reference", referencedColumnName="id")})
     */
    private $ncmReference;

    /**
     * @var float
     *
     * @ORM\Column(name="depreciation", type="float",nullable=true)
     */
    private $depreciation=null;

    /**
     * @var string
     *
     * @ORM\Column(name="supplier", type="string", length=255,nullable=true)
     */
    private $supplier;


    /**
     * @var string
     *
     * @ORM\Column(name="supplier_note_number", type="string", length=255,nullable=true)
     */
    private $supplier_note_number;

    /**
     * @var string
     *
     * @ORM\Column(name="ean128", type="string", length=255,nullable=true)
     */
    private $ean128;

    /**
     * @var string
     *
     * @ORM\Column(name="searchable", type="string", length=255,nullable=true)
     */
    private $searchable;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set defid
     *
     * @param integer $defid
     * @return Item
     */
    public function setDefid($defid)
    {
        $this->defid = (double) $defid;

        return $this;
    }

    /**
     * Get defid
     *
     * @return integer 
     */
    public function getDefid()
    {
        return $this->defid;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Item
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set valueUnit
     *
     * @param float $valueUnit
     * @return Item
     */
    public function setValueUnit($valueUnit)
    {
        if (trim($valueUnit) == '') {
            $valueUnit = 0;
        }
        $valueUnit = str_replace(",", ".", $valueUnit);

        $this->valueUnit = $valueUnit;

        return $this;
    }

    /**
     * Get valueUnit
     *
     * @return float 
     */
    public function getValueUnit()
    {
        return $this->valueUnit;
    }

    /**
     * Set datain
     *
     * @param \DateTime $datain
     * @return Item
     */
    public function setDatain($datain)
    {
        $this->datain = $datain;
    
        return $this;
    }

    /**
     * Get datain
     *
     * @return \DateTime 
     */
    public function getDatain()
    {
        return $this->datain;
    }

    /**
     * Set dataviewed
     *
     * @param \DateTime $dataviewed
     * @return Item
     */
    public function setDataviewed($dataviewed)
    {
        $this->dataviewed = $dataviewed;
    
        return $this;
    }

    /**
     * Get dataviewed
     *
     * @return \DateTime 
     */
    public function getDataviewed()
    {
        return $this->dataviewed;
    }

    /**
     * Set dataout
     *
     * @param \DateTime $dataout
     * @return Item
     */
    public function setDataout($dataout)
    {
        $this->dataout = $dataout;
    
        return $this;
    }

    /**
     * Get dataout
     *
     * @return \DateTime 
     */
    public function getDataout()
    {
        return $this->dataout;
    }

    /**
     * Set qt
     *
     * @param float $qt
     * @return Item
     */
    public function setQt($qt)
    {
        $this->qt = (double) $qt;

        return $this;
    }

    /**
     * Get qt
     *
     * @return float 
     */
    public function getQt()
    {
        return $this->qt;
    }

    /**
     * Set stateId
     *
     * @param string $stateId
     * @return Item
     */
    public function setState($stateId)
    {
        $this->state = $stateId;
    
        return $this;
    }

    /**
     * Get stateId
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set aquiredtype
     *
     * @param string $aquiredtype
     * @return Item
     */
    public function setAquiredtype($aquiredtype)
    {
        $this->aquiredtype = $aquiredtype;
    
        return $this;
    }

    /**
     * Get aquiredtype
     *
     * @return string 
     */
    public function getAquiredtype()
    {
        return $this->aquiredtype;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Item
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set protocolCode
     *
     * @param string $protocolCode
     * @return Item
     */
    public function setProtocolCode($protocolCode)
    {
        $this->protocolCode = $protocolCode;
    
        return $this;
    }

    /**
     * Get protocolCode
     *
     * @return string 
     */
    public function getProtocolCode()
    {
        return $this->protocolCode;
    }

    /**
     * Set ncmReference
     *
     * @param string $ncmReference
     * @return Item
     */
    public function setNcmReference($ncmReference)
    {
        $this->ncmReference = $ncmReference;
    
        return $this;
    }

    /**
     * Get ncmReference
     *
     * @return string 
     */
    public function getNcmReference()
    {
        return $this->ncmReference;
    }

    /**
     * Set depreciation
     *
     * @param float $depreciation
     * @return Item
     */
    public function setDepreciation($depreciation)
    {
        $this->depreciation = $depreciation;
    
        return $this;
    }

    /**
     * Get depreciation
     *
     * @return float 
     */
    public function getDepreciation()
    {
        return $this->depreciation;
    }

    /**
     * Set supplier
     *
     * @param string $supplier
     * @return Item
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
    
        return $this;
    }

    /**
     * Get supplier
     *
     * @return string 
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set supplier_note_number
     *
     * @param string $supplierNoteNumber
     * @return Item
     */
    public function setSupplierNoteNumber($supplierNoteNumber)
    {
        $this->supplier_note_number = $supplierNoteNumber;
    
        return $this;
    }

    /**
     * Get supplier_note_number
     *
     * @return string 
     */
    public function getSupplierNoteNumber()
    {
        return $this->supplier_note_number;
    }

    /**
     * Set ean128
     *
     * @param string $ean128
     * @return Item
     */
    public function setEan128($ean128)
    {
        $this->ean128 = $ean128;
    
        return $this;
    }

    /**
     * Get ean128
     *
     * @return string 
     */
    public function getEan128()
    {
        return $this->ean128;
    }

    /**
     * Set searchable
     *
     * @param string $searchable
     * @return Item
     */
    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
    
        return $this;
    }

    /**
     * Get searchable
     *
     * @return string 
     */
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * Set location
     *
     * @param \Far\AssetManagerBundle\Entity\Location $location
     * @return Item
     */
    public function setLocation(\Far\AssetManagerBundle\Entity\Location $location = null)
    {
        $this->location = $location;
    
        return $this;
    }

    /**
     * Get location
     *
     * @return \Far\AssetManagerBundle\Entity\Location 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set typeofItem
     *
     * @param \Far\AssetManagerBundle\Entity\Typeofitem $typeofItem
     * @return Item
     */
    public function setTypeofItem(\Far\AssetManagerBundle\Entity\Typeofitem $typeofItem = null)
    {
        $this->typeofItem = $typeofItem;
    
        return $this;
    }

    /**
     * Get typeofItem
     *
     * @return \Far\AssetManagerBundle\Entity\Typeofitem 
     */
    public function getTypeofItem()
    {
        return $this->typeofItem;
    }

    /**
     * Set protocol
     *
     * @param \Far\AssetManagerBundle\Entity\Protocol $protocol
     * @return Item
     */
    public function setProtocol(\Far\AssetManagerBundle\Entity\Protocol $protocol = null)
    {
        $this->protocol = $protocol;
    
        return $this;
    }

    /**
     * Get protocol
     *
     * @return \Far\AssetManagerBundle\Entity\Protocol 
     */
    public function getProtocol()
    {
        return $this->protocol;
    }
}
