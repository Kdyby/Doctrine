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
class SimpleObjectHydrator extends \Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator
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



	protected function hydrateRowData(array $sqlResult, array &$cache, array &$result)
	{
		parent::hydrateRowData($sqlResult, $cache, $result);

		if (empty($this->aliasInvokers)) {
			return;
		}

		$invokers = reset($this->aliasInvokers);
		$dqlAlias = key($this->aliasInvokers);
		$class = $this->aliasMetadata[$dqlAlias];

		$entity = end($result);
		$this->lifecycleEventsInvoker->invoke($class, Events::postLoadRelations, $entity, new LifecycleEventArgs($entity, $this->_em), $invokers);
	}

}
