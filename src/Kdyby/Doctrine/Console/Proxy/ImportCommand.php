<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console\Proxy;

use Kdyby\Doctrine\Console\DbalDelegateCommand;

/**
 * Loads an SQL file and executes it.
 */
class ImportCommand extends DbalDelegateCommand
{

	protected function createCommand()
	{
		return new \Doctrine\DBAL\Tools\Console\Command\ImportCommand();
	}

}
