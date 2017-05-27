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
abstract class OrmDelegateCommand extends Command
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
	 * @param string $entityManagerName
	 * @return \Symfony\Component\Console\Command\Command
	 */
	protected function wrapCommand($entityManagerName)
	{
		CommandHelper::setApplicationEntityManager($this->getHelper('container'), $entityManagerName);
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

		$this->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		return $this->wrapCommand($input->getOption('em'))->execute($input, $output);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		$this->wrapCommand($input->getOption('em'))->interact($input, $output);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->wrapCommand($input->getOption('em'))->initialize($input, $output);
	}
}
