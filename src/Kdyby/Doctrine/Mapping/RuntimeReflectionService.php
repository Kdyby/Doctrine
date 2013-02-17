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
use Nette;
use Nette\Reflection;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RuntimeReflectionService extends Doctrine\Common\Persistence\Mapping\RuntimeReflectionService
{

	/**
	 * Return a reflection class instance or null
	 *
	 * @param string $class
	 * @return \Nette\Reflection\ClassType
	 */
	public function getClass($class)
	{
		return new Reflection\ClassType($class);
	}



	/**
	 * Return an accessible property (setAccessible(true)) or null.
	 *
	 * @param string $class
	 * @param string $property
	 * @return \Nette\Reflection\Property
	 */
	public function getAccessibleProperty($class, $property)
	{
		try {
			$property = new Reflection\Property($class, $property);
			$property->setAccessible(TRUE);

			return $property;

		} catch (\ReflectionException $e) {
			return NULL;
		}
	}

}
