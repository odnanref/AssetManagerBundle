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

namespace Far\AssetManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityRepository;

class ItemType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('defid')
            ->add('description')
            ->add('valueUnit')
            ->add('datain')
            ->add('dataviewed')
            ->add('dataout')
            ->add('location', 'entity',array(
                'class' => 'FarAssetManagerBundle:Location',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.description','ASC');
                }
            ))
            ->add('qt')
            ->add('state', 'entity',array(
                'class' => 'FarAssetManagerBundle:State',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.description','ASC');
                }
            ))
            ->add('typeofItem', 'entity',array(
                'class' => 'FarAssetManagerBundle:Typeofitem',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.description','ASC');
                }
            ))
            ->add('aquiredtype', 'entity',array(
                'class' => 'FarAssetManagerBundle:Aquiredtype',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.description','ASC');
                }
            ))
            ->add('protocol', 'entity',array(
                'class' => 'FarAssetManagerBundle:Protocol',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.description','ASC');
                }
            ))
            ->add('code')
            ->add('protocolCode')
            ->add('ncmReference', 'entity',array(
                'class' => 'FarAssetManagerBundle:NcmReference',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.description','ASC');
                }
            ))
            ->add('depreciation')
            ->add('supplier')
            ->add('supplier_note_number')
            ->add('ean128')
            ->add('searchable')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Far\AssetManagerBundle\Entity\Item'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'far_assetmanagerbundle_item';
    }
}
