<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class ORMTestCase extends Tester\TestCase
{

	/**
	 * @var \Nette\DI\Container|\SystemContainer
	 */
	protected $serviceLocator;



	/**
	 * @return Kdyby\Doctrine\EntityManager
	 */
	protected function createMemoryManager(array $files = [])
	{
		$rootDir = __DIR__ . '/..';

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR)
			->addConfig(__DIR__ . '/../nette-reset.neon')
			->addConfig(__DIR__ . '/config/memory.neon')
			->addParameters([
				'appDir' => $rootDir,
				'wwwDir' => $rootDir,
			]);

		foreach ($files as $file) {
			$config->addConfig($file);
		}

		$container = $config->createContainer();
		/** @var Nette\DI\Container $container */

		$em = $container->getByType('Kdyby\Doctrine\EntityManager');
		/** @var Kdyby\Doctrine\EntityManager $em */

		$schemaTool = new SchemaTool($em);
		$schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

		$this->serviceLocator = $container;

		return $em;
	}



	protected function configure(Configurator $config)
	{
	}



	/**
	 * @param string $className
	 * @param array $props
	 * @return object
	 */
	protected function newInstance($className, $props = [])
	{
		return Code\Helpers::createObject($className, $props);
	}

}
