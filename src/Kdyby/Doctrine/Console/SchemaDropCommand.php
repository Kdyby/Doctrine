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
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class SchemaDropCommand extends Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand
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



	/**
	 * {@inheritDoc}
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('em', NULL, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command');
	}



	/**
	 * {@inheritdoc}
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		if ($input->getOption('em')) {
			CommandHelper::setApplicationEntityManager($this->getHelper('container'), $input->getOption('em'));
		}

		$this->cacheCleaner->invalidate();
	}



	/**
	 * {@inheritdoc}
	 */
	protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
	{
		return parent::executeSchemaCommand($input, new ColoredSqlOutput($output), $schemaTool, $metadatas);
	}

}
