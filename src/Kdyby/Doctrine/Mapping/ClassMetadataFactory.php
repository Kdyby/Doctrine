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
	 * @var Kdyby\Doctrine\Configuration
	 */
	private $config;



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
		$this->config = $em->getConfiguration();
		parent::setEntityManager($em);
	}



	protected function loadMetadata($name)
	{
		$origName = $name;
		if ($this->config instanceof Kdyby\Doctrine\Configuration) {
			$name = $this->config->getTargetEntityClassName($name);
		}

		if (!class_exists($name)) {
			throw new Kdyby\Doctrine\MissingClassException("Metadata of class $name was not found, because the class is missing or cannot be autoloaded.");
		}

		$result = parent::loadMetadata($name);
		if ($name !== $origName) {
			$this->setMetadataFor($origName, $this->getMetadataFor($name));
		}

		return $result;
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
