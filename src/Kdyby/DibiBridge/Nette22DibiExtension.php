<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DibiBridge;

use Dibi;
use Kdyby;
use Nette;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class Nette22DibiExtension extends Dibi\Bridges\Nette\DibiExtension22
{

	public function loadConfiguration()
	{
		parent::loadConfiguration();
		$container = $this->getContainerBuilder();

		$connection = $container->getDefinition($this->prefix('connection'));
		$config =& $connection->factory->arguments[0];
		$config['lazy'] = TRUE;
		$config['driver'] = 'pdo';
		$config['resource'] = new Nette\DI\Statement('@\Kdyby\Doctrine\Connection::getWrappedConnection');
	}

}
