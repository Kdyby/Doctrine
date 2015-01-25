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
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Kdyby;
use Kdyby\Persistence;
use Nette;
use Nette\Utils\Callback;
use Nette\Utils\Arrays;



/**
 * This class is an extension to EntityRepository and should help you with prototyping.
 * The first and only rule with DAO is not to ever inherit them, ever.
 *
 * The only valid reason to inherit EntityDao is to add more common methods to all DAO's in application,
 * when you're creating your own framework (but do we really need to go any deeper than this?).
 *
 * WARNING: use save() method only for prototyping or only when you really know
 * internals of Doctrine and you're 100% sure you know what you're doing.
 * The save() method only saves entities of the current type that your DAO works with,
 * it will NOT persist or update any relations. The only exceptions is cascade persist,
 * but that again is a Doctrine internals.
 *
 * Unless you really wanna save only one entity, just use EntityManager::flush().
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityDao extends EntityRepository implements Persistence\ObjectDao
{

	/**
	 * Persists given entities, but does not flush.
	 *
	 * @param object|array|\Traversable $entity
	 * @param object|array|\Traversable $relations
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function add($entity, $relations = NULL)
	{
		foreach ($relations = self::iterableArgs($relations) as $item) {
			$this->getEntityManager()->persist($item);
		}

		foreach ($entity = self::iterableArgs($entity) as $item) {
			if (!$item instanceof $this->_entityName) {
				throw new InvalidArgumentException('Entity is not instanceof ' . $this->_entityName . ', instanceof ' . get_class($item) . ' given.');
			}

			$this->getEntityManager()->persist($item);
		}

		return array_merge($entity, $relations);
	}



	/**
	 * Persists given entities and flushes them, and only them, to the storage.
	 * If no entities are passed, all the entities of current type are persisted.
	 *
	 * @deprecated I won't remove this method for BC, but please stop using it ASAP! Use EntityManager->persist() and ->flush(), this is only a dumb shortcut for VERY specific use-cases!
	 *
	 * @param object|array|\Traversable $entity
	 * @param object|array|\Traversable $relations
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function save($entity = NULL, $relations = NULL)
	{
		if ($entity !== NULL) {
			$result = $this->add($entity, $relations);
			$this->getEntityManager()->flush(array_merge($result, $this->getLoadedEntities()));

			return (empty($relations) && !is_array($entity) && !$entity instanceof \Traversable) ? $entity : $result;
		}

		$this->flush();
		return array();
	}



	/**
	 * @param object $entity
	 * @throws InvalidArgumentException
	 * @return bool|object
	 */
	public function safePersist($entity)
	{
		if (!$entity instanceof $this->_entityName) {
			throw new InvalidArgumentException('Entity is not instanceof ' . $this->_entityName . ', ' . get_class($entity) . ' given.');
		}

		return $this->getEntityManager()->safePersist($entity);
	}



	/**
	 * @deprecated I won't remove this method for BC, but please stop using it ASAP! Use EntityManager->remove() and ->flush(), this is only a dumb shortcut for VERY specific use-cases!
	 *
	 * @param object|array|\Traversable $entity
	 * @param object|array|\Traversable|bool $relations
	 * @param bool $flush
	 * @throws InvalidArgumentException
	 */
	public function delete($entity, $relations = NULL, $flush = Persistence\ObjectDao::FLUSH)
	{
		if (is_bool($relations)) {
			$flush = $relations;
			$relations = NULL;
		}

		foreach (self::iterableArgs($relations) as $item) {
			$this->getEntityManager()->remove($item);
		}

		foreach (self::iterableArgs($entity) as $item) {
			if (!$item instanceof $this->_entityName) {
				throw new InvalidArgumentException('Entity is not instanceof ' . $this->_entityName . ', ' . get_class($item) . ' given.');
			}

			$this->getEntityManager()->remove($item);
		}

		$this->flush($flush);
	}



	/**
	 * @param boolean $flush
	 */
	protected function flush($flush = Persistence\ObjectDao::FLUSH)
	{
		if ($flush === Persistence\ObjectDao::FLUSH) {
			$this->getEntityManager()->flush($this->getLoadedEntities());
		}
	}



	/**
	 * @return object[]
	 */
	private function getLoadedEntities()
	{
		$em = $this->getEntityManager();
		$UoW = $em->getUnitOfWork();
		$im = $UoW->getIdentityMap();

		return array_merge(
			$UoW->getScheduledEntityDeletions(),
			$UoW->getScheduledEntityInsertions(),
			!empty($im[$this->_entityName]) ? Arrays::flatten($im[$this->_entityName]) : array()
		);
	}



	/**
	 * @deprecated I won't remove this method for BC, but please stop using it ASAP! Use EntityManager->transactional(), this is only a dumb shortcut for VERY specific use-cases!
	 *
	 * @param callable $callback
	 * @throws \Exception
	 * @return mixed|boolean
	 */
	public function transactional($callback)
	{
		$connection = $this->getEntityManager()->getConnection();
		$connection->beginTransaction();

		try {
			$return = Callback::invoke($callback, $this, $this->getEntityManager());
			$this->flush();
			$connection->commit();

			return $return ? : TRUE;

		} catch (\Exception $e) {
			$connection->rollback();
			throw $e;
		}
	}



	/**
	 * @param array|string|\Traversable $args
	 * @return array|\Traversable
	 */
	private static function iterableArgs($args)
	{
		if (empty($args)) {
			return array();
		}

		return !is_array($args) && !$args instanceof \Traversable ? array($args) : $args;
	}

}
