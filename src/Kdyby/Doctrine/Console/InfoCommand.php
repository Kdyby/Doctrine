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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InfoCommand extends Doctrine\ORM\Tools\Console\Command\InfoCommand
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



	protected function configure()
	{
		parent::configure();
		$this->addOption('debug-mode', NULL, InputOption::VALUE_OPTIONAL, "Force Tracy debug mode", TRUE);
	}



	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		Debugger::$productionMode = !$input->getOption('debug-mode');
		$this->cacheCleaner->invalidate();
	}

}
