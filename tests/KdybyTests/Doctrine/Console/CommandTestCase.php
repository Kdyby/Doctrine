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
		\KdybyTests\Doctrine\CmsAddress::class,
		\KdybyTests\Doctrine\CmsArticle::class,
		\KdybyTests\Doctrine\CmsComment::class,
		\KdybyTests\Doctrine\CmsEmail::class,
		\KdybyTests\Doctrine\CmsEmployee::class,
		\KdybyTests\Doctrine\CmsGroup::class,
		\KdybyTests\Doctrine\CmsPhoneNumber::class,
		\KdybyTests\Doctrine\CmsUser::class,
		\KdybyTests\Doctrine\CmsOrder::class,
		\KdybyTests\Doctrine\StiUser::class,
		\KdybyTests\Doctrine\StiAdmin::class,
		\KdybyTests\Doctrine\StiEmployee::class,
		\KdybyTests\Doctrine\StiBoss::class,
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
		/** @var \Kdyby\Console\Application $application */
		$application = $this->getServiceLocator()->getByType(\Kdyby\Console\Application::class);
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
			->addConfig(TEST_DIR . '/nette-reset.neon')
			->addConfig(TEST_DIR . '/Doctrine/config/multiple-connections.neon')
			->addParameters([
				'appDir' => TEST_DIR,
				'wwwDir' => TEST_DIR,
			])
			->createContainer();
	}

}
