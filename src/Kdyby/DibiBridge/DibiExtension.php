<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\DibiBridge;

use Kdyby;
use Nette;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

if (class_exists('DibiNette21Extension') && !class_exists('DibiNetteExtension')) {
	class_alias('DibiNette21Extension', 'DibiNetteExtension');
}


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DibiExtension extends \DibiNetteExtension
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
