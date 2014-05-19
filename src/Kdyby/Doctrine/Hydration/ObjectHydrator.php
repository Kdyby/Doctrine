<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Hydration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Mapping\ClassMetadata;
use Kdyby;
use Kdyby\Doctrine\Events;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ObjectHydrator extends \Doctrine\ORM\Internal\Hydration\ObjectHydrator
{

	/**
	 * @var \Doctrine\ORM\Event\ListenersInvoker
	 */
	private $lifecycleEventsInvoker;

	/**
	 * @var \Doctrine\ORM\UnitOfWork
	 */
	private $uow;

	/**
	 * @var array
	 */
	private $lastRowData = array();

	/**
	 * @var array|ClassMetadata[]
	 */
	private $aliasMetadata = array();

	/**
	 * @var array
	 */
	private $aliasInvokers = array();



	public function __construct(EntityManager $em)
	{
		parent::__construct($em);
		$this->lifecycleEventsInvoker = new ListenersInvoker($em);
		$this->uow = $em->getUnitOfWork();
	}



	protected function prepare()
	{
		parent::prepare();

		foreach ($this->_rsm->aliasMap as $dqlAlias => $className) {
			if (isset($this->aliasMetadata[$dqlAlias])) {
				continue;
			}

			$class = $this->_em->getClassMetadata($className);
			if (!$invoke = $this->lifecycleEventsInvoker->getSubscribedSystems($class, Events::postLoadRelations)) {
				continue;
			}


			$this->aliasMetadata[$dqlAlias] = $class;
			$this->aliasInvokers[$dqlAlias] = $invoke;
		}
	}



	protected function hydrateRowData(array $row, array &$cache, array &$result)
	{
		parent::hydrateRowData($row, $cache, $result);

		$identityMap = $this->uow->getIdentityMap();

		foreach ($this->aliasInvokers as $dqlAlias => $invokers) {
			$class = $this->aliasMetadata[$dqlAlias];

			$id = array();
			foreach ($class->identifier as $idProperty) {
				if (!isset($this->lastRowData[$dqlAlias][$idProperty])) {
					if (!isset($class->associationMappings[$idProperty])) {
						continue 2;

					} elseif (!isset($this->lastRowData[$dqlAlias][$idProperty = $class->getSingleAssociationJoinColumnName($idProperty)])) {
						continue 2;
					}
				}

				$id[$idProperty] = $this->lastRowData[$dqlAlias][$idProperty];
			}

			if (!isset($identityMap[$class->rootEntityName][$idHash = implode(' ', (array) $id)])) {
				continue;
			}

			$entity = $identityMap[$class->rootEntityName][$idHash];
			$this->lifecycleEventsInvoker->invoke($class, Events::postLoadRelations, $entity, new LifecycleEventArgs($entity, $this->_em), $invokers);
		}
	}



	protected function gatherRowData(array $data, array &$cache, array &$id, array &$nonemptyComponents)
	{
		return $this->lastRowData = parent::gatherRowData($data, $cache, $id, $nonemptyComponents);
	}



	protected function cleanup()
	{
		parent::cleanup();

		$this->lastRowData =
		$this->aliasMetadata =
		$this->aliasInvokers = NULL;
	}



	public function onClear($eventArgs)
	{
		parent::onClear($eventArgs);

		$this->lastRowData = NULL;
	}

}
