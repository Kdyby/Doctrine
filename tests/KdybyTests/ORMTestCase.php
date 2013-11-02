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
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby;
use Nette;
use Nette\Utils\PhpGenerator as Code;
use Tester;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class ORMTestCase extends Tester\TestCase
{

	/**
	 * @return Kdyby\Doctrine\EntityManager
	 */
	protected function createMemoryManager()
	{
		require_once __DIR__ . '/Doctrine/models/cms.php';

		$config = new Nette\Config\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addConfig(__DIR__ . '/nette-reset.neon', FALSE);
		$config->addConfig(__DIR__ . '/Doctrine/config/memory.neon', FALSE);

		Kdyby\Events\DI\EventsExtension::register($config);
		Kdyby\Console\DI\ConsoleExtension::register($config);
		Kdyby\Annotations\DI\AnnotationsExtension::register($config);
		Kdyby\Doctrine\DI\OrmExtension::register($config);

		$container = $config->createContainer();
		/** @var Nette\DI\Container $container */

		$em = $container->getByType('Kdyby\Doctrine\EntityManager');
		/** @var Kdyby\Doctrine\EntityManager $em */

		$schemaTool = new SchemaTool($em);
		$schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

		return $em;
	}



	/**
	 * Creates an EntityManager for testing purposes.
	 *
	 * @param Connection $conn
	 * @param EventManager $eventManager
	 * @return EntityManager
	 */
	protected function createTestEntityManager($conn = NULL, EventManager $eventManager = NULL)
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

		if (!$eventManager) {
			$eventManager = new EventManager();
		}

		if (is_array($conn)) {
			$conn = DriverManager::getConnection($conn, $config, $eventManager);
		}

		return DoctrineMocks\EntityManagerMock::create($conn, $config, $eventManager);
	}



	/**
	 * @param string $className
	 * @param array $props
	 * @return object
	 */
	protected function newInstance($className, $props = array())
	{
		return Code\Helpers::createObject($className, $props);
	}

}
