<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console;

use Kdyby\Doctrine\Helpers;
use Nette\Utils\Arrays;
use Nette\Utils\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;


if (!class_exists('Tracy\Debugger')) {
	class_alias('Nette\Diagnostics\Debugger', 'Tracy\Debugger');
}


/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class MigrationCommand extends Command
{

	const VERSIONS_TABLE_NAME = 'doctrine_migration_versions';

	/**
	 * @var \Kdyby\Doctrine\Connection
	 * @inject
	 */
	public $connection;



	protected function configure()
	{
		$this->setName('orm:migration')
			->setDescription('Processes SQL script migration files.')
			->addArgument('directory', InputArgument::REQUIRED, 'Path to directory with SQL script migration files')
			->addOption('dry', NULL, InputOption::VALUE_NONE, 'Would not run any of migration');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$directory = $input->getArgument('directory');
		$dry = $input->getOption('dry');
		$connection = $this->connection;

		$tables = Arrays::flatten($connection->fetchAll('SHOW TABLES'));
		if (!in_array(self::VERSIONS_TABLE_NAME, $tables)) {
			$connection->executeQuery('CREATE TABLE ' . self::VERSIONS_TABLE_NAME . ' ( version VARCHAR(255) NOT NULL, UNIQUE KEY version (version));');
		}

		$files = array();
		/** @var \SplFileInfo $file */
		foreach (Finder::findFiles('[0-9]*.sql')->from($directory) as $file) {
			if (!$this->connection->fetchAll('SELECT 1 FROM ' . self::VERSIONS_TABLE_NAME . ' WHERE version = ?', [$file->getBasename('.sql')])) {
				$files[] = $file;
			}
		}

		$scriptsCount = count($files);
		$output->writeln('<fg=cyan>Running <options=bold>' . $scriptsCount . '</options=bold> migration scripts.</fg=cyan>');

		usort($files, function (\SplFileInfo $fileA, \SplFileInfo $fileB) {
			return strcmp($fileA->getFilename(), $fileB->getFilename());
		});

		foreach ($files as $i => $file) {
			$output->write('<fg=yellow;options=bold>' . ($i + 1) . '/' . $scriptsCount . '</fg=yellow;options=bold> ' . $file->getFilename());
			$connection->beginTransaction();
			try {
				if (!$dry) {
					Helpers::loadFromFile($connection, $file->getRealPath());
					$connection->insert(self::VERSIONS_TABLE_NAME, ['version' => $file->getBasename('.sql')]);
				} else {
					$output->write(' <fg=magenta>dry run</fg=magenta>');
				}
				$connection->commit();
				$output->writeln(' <info>OK</info>');
			} catch (\Exception $e) {
				$connection->rollBack();
				$output->writeln(' <error>FAIL!</error>');
				$output->writeln('- <fg=red>' . $e->getMessage() . '</fg=red>');
				Debugger::log($e, 'doctrine-migration');
			}
		}

		$output->writeln('<info>Finished</info>');

		return 0;
	}

}
