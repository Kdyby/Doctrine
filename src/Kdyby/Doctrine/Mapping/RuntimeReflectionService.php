<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Mapping;

use Doctrine;
use Kdyby;
use ReflectionClass;
use ReflectionProperty;


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RuntimeReflectionService extends Doctrine\Persistence\Mapping\RuntimeReflectionService
{

	/**
	 * @param string $class
	 */
	public function getClass($class): ?ReflectionClass
	{
		return new ReflectionClass($class);
	}



	/**
	 * Return an accessible property (setAccessible(true)) or null.
	 *
	 * @param string $class
	 * @param string $property
	 */
	public function getAccessibleProperty($class, $property): ?ReflectionProperty
	{
		try {
			$property = new ReflectionProperty($class, $property);
			$property->setAccessible(TRUE);

			return $property;

		} catch (\ReflectionException $e) {
			return NULL;
		}
	}

}
