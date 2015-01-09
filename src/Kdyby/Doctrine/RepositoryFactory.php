<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine;
use Doctrine\ORM\EntityManagerInterface;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RepositoryFactory extends Nette\Object implements Doctrine\ORM\Repository\RepositoryFactory
{

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;

	/**
	 * The list of EntityRepository instances.
	 *
	 * @var \Doctrine\Common\Persistence\ObjectRepository[]
	 */
	private $repositoryList = array();



	public function __construct(Nette\DI\Container $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}



	/**
	 * @param EntityManagerInterface|EntityManager $entityManager
	 * @param string $entityName
	 * @return EntityRepository
	 */
	public function getRepository(EntityManagerInterface $entityManager, $entityName)
	{
		if (is_object($entityName)) {
			$entityName = Doctrine\Common\Util\ClassUtils::getRealClass(get_class($entityName));
		}

		$entityName = ltrim($entityName, '\\');

		if (isset($this->repositoryList[$emId = spl_object_hash($entityManager)][$entityName])) {
			return $this->repositoryList[$emId][$entityName];
		}

		/** @var Doctrine\ORM\Mapping\ClassMetadata $metadata */
		$metadata = $entityManager->getClassMetadata($entityName);

		$repository = $this->createRepository($entityManager, $metadata);
		$entityManager->onDaoCreate($entityManager, $repository);

		return $this->repositoryList[$emId][$entityName] = $repository;
	}



	/**
	 * Create a new repository instance for an entity class.
	 *
	 * @param \Doctrine\ORM\EntityManagerInterface $entityManager The EntityManager instance.
	 * @param Doctrine\ORM\Mapping\ClassMetadata $metadata

	 * @return Doctrine\Common\Persistence\ObjectRepository
	 */
	private function createRepository(EntityManagerInterface $entityManager, Doctrine\ORM\Mapping\ClassMetadata $metadata)
	{
		$defaultRepository = $entityManager->getConfiguration()->getDefaultRepositoryClassName();
		$repositoryClassName = $metadata->customRepositoryClassName ?: $defaultRepository;

		if ($repositoryClassName === $defaultRepository) {
			return new $repositoryClassName($entityManager, $metadata);

		} elseif (!$services = $this->serviceLocator->findByType($repositoryClassName)) { // todo: solve me in future, maybe just throw an exception?
			return new $repositoryClassName($entityManager, $metadata);

		} elseif (count($services) > 1) { // todo: solve me in future, maybe just throw an exception?
			return new $repositoryClassName($entityManager, $metadata);

		} else {
			return $this->serviceLocator->createService($services[0], array('entityManager' => $entityManager, 'metadata' => $metadata));
		}
	}

}
