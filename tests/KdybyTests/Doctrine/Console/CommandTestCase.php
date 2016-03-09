<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine\Console;

use Nette;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tester;



/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 */
abstract class CommandTestCase extends Tester\TestCase
{

	/**
	 * @var array
	 */
	protected static $models = [
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
	 * @return CommandTester
	 */
	protected function executeCommand($command, array $input = [], array $options = [])
	{
		$command = $this->getCommand($command);

		$commandTester = new CommandTester($command);
		$commandTester->execute(['command' => $command->getName()] + $input, $options);

		return $commandTester;
	}



	/**
	 * @param string $name
	 *
	 * @return Command
	 */
	protected function getCommand($name)
	{
		$command = $this->getServiceLocator()
			->getByType('Kdyby\Console\Application')
			->find($name);

		$this->getServiceLocator()->callInjects($command);

		return $command;
	}



	/**
	 * @return Nette\DI\Container
	 */
	protected function getServiceLocator()
	{
		if (!$this->serviceLocator) {
			$this->createServiceLocator();
		}
		return $this->serviceLocator;
	}



	private function createServiceLocator()
	{
		$config = new Nette\Configurator;
		/** @var Nette\DI\Container $container */
		$container = $config->setTempDirectory(TEMP_DIR)
			->addConfig(TEST_DIR . '/nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22')
			->addConfig(TEST_DIR . '/Doctrine/config/memory.neon')
			->addParameters([
				'appDir' => TEST_DIR,
				'wwwDir' => TEST_DIR,
			])
			->createContainer();

		$this->serviceLocator = $container;
	}

}
