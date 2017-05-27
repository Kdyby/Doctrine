<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console\Proxy;

use Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand;
use Kdyby\Doctrine\Console\OrmDelegateCommand;

/**
 * Command to clear a entity cache region.
 */
class CacheClearEntityRegionCommand extends OrmDelegateCommand
{

	protected function createCommand()
	{
		return new EntityRegionCommand();
	}

}
