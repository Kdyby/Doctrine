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



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\Mapping\ClassMetadata getMetadataFor($className)
 * @method \Kdyby\Doctrine\Mapping\ClassMetadata[] getAllMetadata()
 */
class ClassMetadataFactory extends Doctrine\ORM\Mapping\ClassMetadataFactory
{

	/**
	 * Enforce Nette\Reflection
	 */
	public function __construct()
	{
		$this->setReflectionService(new RuntimeReflectionService);
	}


}
