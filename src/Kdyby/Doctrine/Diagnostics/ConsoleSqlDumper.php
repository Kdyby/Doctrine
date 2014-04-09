<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Doctrine\DBAL\Connection;
use Kdyby;
use Nette;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait ConsoleSqlDumper
{

	/**
	 * @var \Doctrine\DBAL\Connection
	 * @inject
	 */
	public $_kdybyConnection;



	public function run(InputInterface $input, OutputInterface $output)
	{
		$exitCode = parent::run($input, $output);

		if ($panel = $this->getPanel()) {
			$panel->getPanel($output);
		}

		return $exitCode;
	}



	/**
	 * @return Panel
	 */
	private function getPanel()
	{
		if (!$this->_kdybyConnection instanceof Connection) {
			return NULL;
		}

		$logger = $this->_kdybyConnection->getConfiguration()->getSQLLogger();
		if ($logger instanceof Panel) {
			return $logger;

		} elseif (!$logger instanceof LoggerChain) {
			return NULL;
		}

		foreach ($logger as $subscriber) {
			if ($subscriber instanceof Panel) {
				return $subscriber;
			}
		}

		return NULL;
	}

}
