<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Tools;

use Doctrine\Common\Cache\CacheProvider;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CacheCleaner extends Nette\Object
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $entityManager;



	public function __construct(Kdyby\Doctrine\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}



	public function invalidate()
	{
		$ormConfig = $this->entityManager->getConfiguration();
		$dbalConfig = $this->entityManager->getConnection()->getConfiguration();

		$cache = array(
			$ormConfig->getHydrationCacheImpl(),
			$ormConfig->getMetadataCacheImpl(),
			$ormConfig->getQueryCacheImpl(),
			$ormConfig->getResultCacheImpl(),
			$dbalConfig->getResultCacheImpl(),
		);

		foreach ($cache as $impl) {
			if (!$impl instanceof CacheProvider) {
				continue;
			}

			$impl->deleteAll();
		}
	}

}
