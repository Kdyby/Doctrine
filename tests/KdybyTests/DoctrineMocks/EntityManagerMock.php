<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\DoctrineMocks;

use Kdyby\Doctrine\Configuration;
use Kdyby\Doctrine\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityManagerMock extends EntityManager
{

	/**
	 * @var Doctrine\ORM\UnitOfWork
	 */
	private $_uowMock;

	/**
	 * @var Doctrine\ORM\Proxy\ProxyFactory
	 */
	private $_proxyFactoryMock;



	/**
	 * @return \Doctrine\ORM\UnitOfWork
	 */
	public function getUnitOfWork()
	{
		return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
	}



	/**
	 * @param Doctrine\ORM\UnitOfWork $uow
	 */
	public function setUnitOfWork($uow)
	{
		$this->_uowMock = $uow;
	}



	/**
	 * @param Doctrine\ORM\Proxy\ProxyFactory $proxyFactory
	 */
	public function setProxyFactory($proxyFactory)
	{
		$this->_proxyFactoryMock = $proxyFactory;
	}



	/**
	 * @return \Doctrine\ORM\Proxy\ProxyFactory
	 */
	public function getProxyFactory()
	{
		return isset($this->_proxyFactoryMock) ? $this->_proxyFactoryMock : parent::getProxyFactory();
	}



	/**
	 * Mock factory method to create an EntityManager.
	 *
	 * @param Doctrine\DBAL\Connection $conn
	 * @param \Doctrine\ORM\Configuration $config
	 * @param \Doctrine\Common\EventManager $eventManager
	 * @return \Doctrine\ORM\EntityManager|EntityManagerMock
	 */
	public static function create($conn, Doctrine\ORM\Configuration $config = NULL, EventManager $eventManager = NULL)
	{
		if (is_null($config)) {
			$config = new Configuration();
			$config->setProxyDir(TEMP_DIR . '/proxies');
			$config->setProxyNamespace('KdybyTests\DoctrineProxies');
			$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([], TRUE));
		}

		if (is_null($eventManager)) {
			$eventManager = new EventManager();
		}

		return new EntityManagerMock($conn, $config, $eventManager);
	}

}
