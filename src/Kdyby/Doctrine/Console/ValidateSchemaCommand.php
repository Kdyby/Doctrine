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
class ValidateSchemaCommand extends Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand
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

		if (!$this->getDefinition()->hasOption('skip-mapping')) {
			$this->addOption('skip-mapping', null, InputOption::VALUE_NONE, 'Skip the mapping validation check');
		}

		if (!$this->getDefinition()->hasOption('skip-sync')) {
			$this->addOption('skip-sync', null, InputOption::VALUE_NONE, 'Skip checking if the mapping is in sync with the database');
		}

		$this->addOption('debug-mode', NULL, InputOption::VALUE_OPTIONAL, "Force Tracy debug mode", TRUE);
	}



	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		Debugger::$productionMode = !$input->getOption('debug-mode');
		$this->cacheCleaner->invalidate();
	}



	/**
	 * Hack to simulate behaviour of newer doctrine for older 2.4.* versions users.
	 * It does the check but doesn't return exit code for that check.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$exit = parent::execute($input, $output);

		if ($input->getOption('skip-sync')) {
			if ($exit >= 2) {
				$exit -= 2;
			}
		}

		if ($input->getOption('skip-mapping')) {
			if ($exit === 1 || $exit === 3) {
				$exit -= 1;
			}
		}

		return $exit;
	}

}
