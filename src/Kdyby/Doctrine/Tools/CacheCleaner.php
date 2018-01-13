<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Tools;

use Doctrine;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ClearableCache;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CacheCleaner
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $entityManager;

	/**
	 * @var (ClearableCache|Doctrine\Common\Cache\Cache|null)[]
	 */
	private $cacheStorages = [];



	public function __construct(Doctrine\ORM\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}



	public function addCacheStorage(ClearableCache $storage)
	{
		$this->cacheStorages[] = $storage;
	}



	public function invalidate()
	{
		$ormConfig = $this->entityManager->getConfiguration();
		$dbalConfig = $this->entityManager->getConnection()->getConfiguration();

		$cache = $this->cacheStorages;
		$cache[] = $ormConfig->getHydrationCacheImpl();
		$cache[] = $ormConfig->getMetadataCacheImpl();
		$cache[] = $ormConfig->getQueryCacheImpl();
		$cache[] = $ormConfig->getResultCacheImpl();
		$cache[] = $dbalConfig->getResultCacheImpl();

		foreach ($cache as $impl) {
			if (!$impl instanceof ClearableCache) {
				continue;
			}

			$impl->deleteAll();
		}
	}

}
