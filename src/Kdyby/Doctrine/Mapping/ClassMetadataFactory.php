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
use Doctrine\ORM\EntityManager;
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
	 * @var EntityManager
	 */
	private $em;



	/**
	 * Enforce Nette\Reflection
	 */
	public function __construct()
	{
		$this->setReflectionService(new RuntimeReflectionService);
	}



	/**
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
		parent::setEntityManager($em);
	}



	/**
	 * Creates a new ClassMetadata instance for the given class name.
	 *
	 * @param string $className
	 * @return ClassMetadata
	 */
	protected function newClassMetadataInstance($className)
	{
		return new ClassMetadata($className, $this->em->getConfiguration()->getNamingStrategy());
	}

}
