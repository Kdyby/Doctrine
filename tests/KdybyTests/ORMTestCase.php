<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Kdyby;
use Nette;
use Tester;


require_once __DIR__ . '/Doctrine/mocks.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ORMTestCase extends Tester\TestCase
{

	/**
	 * Creates an EntityManager for testing purposes.
	 *
	 * @param Connection $conn
	 * @param EventManager $eventManager
	 * @return EntityManager
	 */
	protected function createTestEntityManager($conn = NULL, $eventManager = NULL)
	{
		$config = new Configuration();
		$config->setMetadataCacheImpl(new ArrayCache);
		$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(__DIR__ . '/Doctrine/models'), TRUE));
		$config->setQueryCacheImpl(new ArrayCache);
		$config->setProxyDir(TEMP_DIR . '/proxies');
		$config->setProxyNamespace('Doctrine\Tests\Proxies');
		$config->addEntityNamespace('test', 'KdybyTests\Doctrine');

		if ($conn === NULL) {
			$conn = array(
				'driverClass' => 'KdybyTests\Doctrine\DriverMock',
				'wrapperClass' => 'KdybyTests\Doctrine\ConnectionMock',
				'user' => 'filip',
				'password' => 'prochazka'
			);
		}

		if (is_array($conn)) {
			$conn = DriverManager::getConnection($conn, $config, $eventManager);
		}

		return Doctrine\EntityManagerMock::create($conn, $config, $eventManager);
	}

}
