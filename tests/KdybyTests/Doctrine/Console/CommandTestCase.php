<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine\Console;

use Kdyby;
use Nette;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester;



/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 */
abstract class CommandTestCase extends Tester\TestCase
{

	/**
	 * @var array
	 */
	protected static $entities = [
		'KdybyTests\Doctrine\CmsAddress',
		'KdybyTests\Doctrine\CmsArticle',
		'KdybyTests\Doctrine\CmsComment',
		'KdybyTests\Doctrine\CmsEmail',
		'KdybyTests\Doctrine\CmsEmployee',
		'KdybyTests\Doctrine\CmsGroup',
		'KdybyTests\Doctrine\CmsPhoneNumber',
		'KdybyTests\Doctrine\CmsUser',
		'KdybyTests\Doctrine\CmsOrder',
		'KdybyTests\Doctrine\AnnotationDriver\Something\Baz',
		'KdybyTests\Doctrine\AnnotationDriver\App\FooEntity',
		'KdybyTests\Doctrine\AnnotationDriver\App\Bar',
		'KdybyTests\Doctrine\StiUser',
		'KdybyTests\Doctrine\StiAdmin',
		'KdybyTests\Doctrine\StiEmployee',
		'KdybyTests\Doctrine\StiBoss',
	];

	/**
	 * @var array
	 */
	protected static $tables = [
		'cms_addresses',
		'cms_articles',
		'cms_comments',
		'cms_emails',
		'cms_employees',
		'cms_groups',
		'cms_phonenumbers',
		'cms_users',
		'cms_users_groups',
		'cms_orders',
		'baz',
		'foo_entity',
		'bar',
		'sti_users',
	];

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;



	/**
	 * @param string $command
	 * @param array  $input
	 * @param array  $options
	 *
	 * @return ApplicationTester
	 */
	protected function executeCommand($command, array $input = [], array $options = [])
	{
		$applicationTester = new ApplicationTester($this->getApplication());
		$applicationTester->run(['command' => $command] + $input, $options);
		return $applicationTester;
	}



	/**
	 * @return Kdyby\Console\Application
	 */
	protected function getApplication()
	{
		$application = $this->getServiceLocator()->getByType('Kdyby\Console\Application');
		$application->setAutoExit(FALSE);
		return $application;
	}



	/**
	 * @return Nette\DI\Container
	 */
	protected function getServiceLocator()
	{
		if (!$this->serviceLocator) {
			$this->serviceLocator = $this->createServiceLocator();
		}
		return $this->serviceLocator;
	}



	/**
	 * @return Nette\DI\Container
	 */
	private function createServiceLocator()
	{
		$config = new Nette\Configurator;
		return $config->setTempDirectory(TEMP_DIR)
			->addConfig(TEST_DIR . '/nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22')
			->addConfig(TEST_DIR . '/Doctrine/config/multiple-connections.neon')
			->addParameters([
				'appDir' => TEST_DIR,
				'wwwDir' => TEST_DIR,
			])
			->createContainer();
	}

}
