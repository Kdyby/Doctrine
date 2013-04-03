<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

if (!defined('KDYBY_TO_NETTE_PHPGEN') && !class_exists('Nette\PhpGenerator\ClassType')) {
	/**
	 * I know this is ugly, ugly hack. When you find a better solution, I will kiss your feed, provided that
	 * - this will work for stable Nette & for dev Nette
	 * - the newer form (Nette\PhpGenerator) will be used in this library
	 * - with Composer
	 * - your IDE of choice will not raise any warning on this library
	 * - your IDE will not break on "multiple definition of class"
	 */
	$aliases = <<<GEN
namespace Nette\PhpGenerator {
	class ClassType extends \Nette\Utils\PhpGenerator\ClassType {}
	class Helpers extends \Nette\Utils\PhpGenerator\Helpers {}
	class Method extends \Nette\Utils\PhpGenerator\Method {}
	class Parameter extends \Nette\Utils\PhpGenerator\Parameter {}
	class PhpLiteral extends \Nette\Utils\PhpGenerator\PhpLiteral {}
	class Property extends \Nette\Utils\PhpGenerator\Property {}
}
GEN;
	eval($aliases);
	define('KDYBY_TO_NETTE_PHPGEN', 1);
}

if (!defined('KDYBY_TO_NETTE_SERVICEINTERNAL')) {
	Nette\DI\ServiceDefinition::extensionMethod('setInject', function ($_this) {
		return $_this;
	});
	define('KDYBY_TO_NETTE_SERVICEINTERNAL', 1);
}
