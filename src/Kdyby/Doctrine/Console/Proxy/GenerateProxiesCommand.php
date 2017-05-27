<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console\Proxy;

use Kdyby\Doctrine\Console\OrmDelegateCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class GenerateProxiesCommand extends OrmDelegateCommand
{

	/**
	 * @var \Kdyby\Doctrine\Tools\CacheCleaner
	 * @inject
	 */
	public $cacheCleaner;

	public function __construct()
	{
		parent::__construct();
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		$this->cacheCleaner->invalidate();
	}

	protected function createCommand()
	{
		return new \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand();
	}

}
