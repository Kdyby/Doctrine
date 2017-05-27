<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command Delegate.
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class DbalDelegateCommand extends Command
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Symfony\Component\Console\Command\Command
	 */
	protected $command;

	/**
	 * @return \Symfony\Component\Console\Command\Command
	 */
	abstract protected function createCommand();

	/**
	 * @param string $connectionName
	 * @return \Symfony\Component\Console\Command\Command
	 */
	protected function wrapCommand($connectionName)
	{
		CommandHelper::setApplicationConnection($this->getHelper('container'), $connectionName);
		$this->command->setApplication($this->getApplication());
		return $this->command;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure()
	{
		$this->command = $this->createCommand();

		$this->setName($this->command->getName());
		$this->setHelp($this->command->getHelp());
		$this->setDefinition($this->command->getDefinition());
		$this->setDescription($this->command->getDescription());

		$this->addOption('connection', NULL, InputOption::VALUE_OPTIONAL, 'The connection to use for this command');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		return $this->wrapCommand($input->getOption('connection'))->execute($input, $output);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		$this->wrapCommand($input->getOption('connection'))->interact($input, $output);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->wrapCommand($input->getOption('connection'))->initialize($input, $output);
	}
}
