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
class RuntimeReflectionService extends Doctrine\Persistence\Mapping\RuntimeReflectionService
{

	public function getAccessibleProperty($class, $property)
	{
		try {
			return parent::getAccessibleProperty($class, $property);

		} catch (\ReflectionException $e) {
			return NULL;
		}
	}

}
