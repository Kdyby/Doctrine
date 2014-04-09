<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Doctrine\DBAL\Logging\SQLLogger;
use Kdyby;
use Nette;



/**
 * @author Christophe Coevoet <stof@notk.org>
 * @author Filip Procházka <filip@prochazka.su>
 */
class LoggerChain extends \Doctrine\DBAL\Logging\LoggerChain implements SQLLogger, \IteratorAggregate
{

	/**
	 * @var \Doctrine\DBAL\Logging\SQLLogger[]
	 */
	private $loggers = array();



	/**
	 * Adds a logger in the chain.
	 *
	 * @param \Doctrine\DBAL\Logging\SQLLogger $logger
	 *
	 * @return void
	 */
	public function addLogger(SQLLogger $logger)
	{
		$this->loggers[] = $logger;
		parent::addLogger($logger);
	}



	/**
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->loggers);
	}

}
