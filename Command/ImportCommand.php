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

namespace Far\AssetManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Far\AssetManagerBundle\Service\ImportCsv;

class ImportCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("assetmanager:import")
            ->setDescription("Import Assets to system")
            ->addOption('csv')
            ->addArgument('file')
            ->addArgument('separator');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        $options['separator'] = $input->getArgument('separator');
        if (trim($options['separator']) == '') {
           $options['separator'] = ';';
        }
        $erm = $this->getContainer()->get('doctrine')->getEntityManager();
        $output->write("Running... on File>$file, separator=".$options['separator']);
        if ($input->getOption('csv')) {
            // go to csv import
            $imp = new ImportCsv($file, $options, $erm);

            return ;
        }
    }
}
