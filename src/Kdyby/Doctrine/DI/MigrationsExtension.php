<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\DI;

use Doctrine;
use Kdyby;
use Nette;



/**
 * @author Pavel Kouřil <pk@pavelkouril.cz>
 */
class MigrationsExtension extends Nette\DI\CompilerExtension
{
	
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$services = $this->loadFromFile(__DIR__ . '/migrations.neon');

		foreach ($services as $i => $command) {
			$builder->addDefinition($this->prefix('cli.migrations.' . $i))
				->addTag(Kdyby\Console\DI\ConsoleExtension::TAG_COMMAND)
				->setClass($command);
		}
	}
	
}
