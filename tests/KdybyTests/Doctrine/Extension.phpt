<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase Kdyby\Doctrine\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return \SystemContainer|Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		require_once __DIR__ . '/models/cms.php';

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
		$config->addParameters(['appDir' => $rootDir = __DIR__ . '/..', 'wwwDir' => $rootDir]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer('memory');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType('Kdyby\Doctrine\EntityManager');
		Assert::true($default instanceof Kdyby\Doctrine\EntityManager);

		$userRepository = $default->getRepository('KdybyTests\Doctrine\CmsUser');
		Assert::true($userRepository instanceof Kdyby\Doctrine\EntityRepository);
	}



	public function testMultipleConnections()
	{
		$container = $this->createContainer('multiple-connections');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType('Kdyby\Doctrine\EntityManager');
		Assert::true($default instanceof Kdyby\Doctrine\EntityManager);
		Assert::same($container->getService('kdyby.doctrine.default.entityManager'), $default);

		Assert::true($container->getService('kdyby.doctrine.remote.entityManager') instanceof Kdyby\Doctrine\EntityManager);
		Assert::notSame($container->getService('kdyby.doctrine.remote.entityManager'), $default);
	}



	public function testCmsModelEntities()
	{
		$container = $this->createContainer('memory');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType('Kdyby\Doctrine\EntityManager');
		$entityClasses = array_map(function (ClassMetadata $class) {
			return $class->getName();
		}, $default->getMetadataFactory()->getAllMetadata());

		sort($entityClasses);

		Assert::same([
			'KdybyTests\\Doctrine\\AnnotationDriver\\App\\Bar',
			'KdybyTests\\Doctrine\\AnnotationDriver\\App\\FooEntity',
			'KdybyTests\\Doctrine\\AnnotationDriver\\Something\\Baz',
			'KdybyTests\\Doctrine\\CmsAddress',
			'KdybyTests\\Doctrine\\CmsArticle',
			'KdybyTests\\Doctrine\\CmsComment',
			'KdybyTests\\Doctrine\\CmsEmail',
			'KdybyTests\\Doctrine\\CmsEmployee',
			'KdybyTests\\Doctrine\\CmsGroup',
			'KdybyTests\\Doctrine\\CmsOrder',
			'KdybyTests\\Doctrine\\CmsPhoneNumber',
			'KdybyTests\\Doctrine\\CmsUser',
			'KdybyTests\\Doctrine\\StiAdmin',
			'KdybyTests\\Doctrine\\StiBoss',
			'KdybyTests\\Doctrine\\StiEmployee',
			'KdybyTests\\Doctrine\\StiUser',
			'Kdyby\\Doctrine\\Entities\\BaseEntity',
			'Kdyby\\Doctrine\\Entities\\IdentifiedEntity',
		], $entityClasses);
	}

}

\run(new ExtensionTest());
