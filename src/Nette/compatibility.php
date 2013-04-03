<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

if (!defined('KDYBY_TO_NETTE_PHPGEN') && !class_exists('Nette\Utils\PhpGenerator\ClassType')) {
	class_alias('Nette\Utils\PhpGenerator\ClassType', 'Nette\PhpGenerator\ClassType');
	class_alias('Nette\Utils\PhpGenerator\Helpers', 'Nette\PhpGenerator\Helpers');
	class_alias('Nette\Utils\PhpGenerator\Method', 'Nette\PhpGenerator\Method');
	class_alias('Nette\Utils\PhpGenerator\Parameter', 'Nette\PhpGenerator\Parameter');
	class_alias('Nette\Utils\PhpGenerator\PhpLiteral', 'Nette\PhpGenerator\PhpLiteral');
	class_alias('Nette\Utils\PhpGenerator\Property', 'Nette\PhpGenerator\Property');
	define('KDYBY_TO_NETTE_PHPGEN', 1);
}

if (!defined('KDYBY_TO_NETTE_SERVICEINTERNAL')) {
	Nette\DI\ServiceDefinition::extensionMethod('setInject', function ($_this) {
		return $_this;
	});
	define('KDYBY_TO_NETTE_SERVICEINTERNAL', 1);
}
