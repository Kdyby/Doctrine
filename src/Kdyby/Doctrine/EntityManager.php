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
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMException;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method flush(array $entity = NULL)
 * @method onDaoCreate(EntityManager $em, EntityDao $dao)
 */
class EntityManager extends Doctrine\ORM\EntityManager
{

	/**
	 * @var array
	 */
	public $onDaoCreate = array();

	/**
	 * @var array|EntityDao[]
	 */
	private $repositories = array();



	/**
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	public function createQueryBuilder()
	{
		return new QueryBuilder($this);
	}



	/**
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function createSelection()
	{
		return new DqlSelection($this);
	}



	/**
	 * @param string $entityName
	 * @return EntityDao
	 */
	public function getRepository($entityName)
	{
		$entityName = ltrim($entityName, '\\');

		if (isset($this->repositories[$entityName])) {
			return $this->repositories[$entityName];
		}

		$metadata = $this->getClassMetadata($entityName);
		if (!$daoClassName = $metadata->customRepositoryClassName) {
			$daoClassName = $this->getConfiguration()->getDefaultRepositoryClassName();
		}

		$dao = new $daoClassName($this, $metadata);
		$this->repositories[$entityName] = $dao;
		$this->onDaoCreate($this, $dao);

		return $dao;
	}



	/**
	 * @param string $entityName
	 * @return EntityDao
	 */
	public function getDao($entityName)
	{
		return $this->getRepository($entityName);
	}



	/**
	 * Factory method to create EntityManager instances.
	 *
	 * @param \Doctrine\DBAL\Connection|array $conn
	 * @param \Doctrine\ORM\Configuration $config
	 * @param \Doctrine\Common\EventManager $eventManager
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \InvalidArgumentException
	 * @throws \Doctrine\ORM\ORMException
	 * @return EntityManager
	 */
	public static function create($conn, Configuration $config, EventManager $eventManager = NULL)
	{
		if (!$config->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}

		switch (TRUE) {
			case (is_array($conn)):
				$conn = DriverManager::getConnection(
					$conn, $config, ($eventManager ? : new EventManager())
				);
				break;

			case ($conn instanceof Doctrine\DBAL\Connection):
				if ($eventManager !== NULL && $conn->getEventManager() !== $eventManager) {
					throw ORMException::mismatchedEventManager();
				}
				break;

			default:
				throw new \InvalidArgumentException("Invalid connection");
		}

		return new EntityManager($conn, $config, $conn->getEventManager());
	}

}
