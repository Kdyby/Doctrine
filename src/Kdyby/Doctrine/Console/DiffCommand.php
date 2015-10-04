<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console;

use Doctrine;
use Kdyby;
use Nette;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Pavel Kouřil <pk@pavelkouril.cz>
 */
class DiffCommand extends \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand
{

    /**
     * @var \Kdyby\Doctrine\Tools\CacheCleaner
     * @inject
     */
    public $cacheCleaner;



    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->cacheCleaner->invalidate();
    }

}
