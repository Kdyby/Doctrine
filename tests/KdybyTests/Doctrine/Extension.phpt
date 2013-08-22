<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase Kdyby\Doctrine\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

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
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . md5($configFile))));
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		require_once __DIR__ . '/models/cms.php';

		$container = $this->createContainer('memory');
		$entityManager = $container->getByType('Kdyby\Doctrine\EntityManager');
		Assert::true($entityManager instanceof Kdyby\Doctrine\EntityManager);
		/** @var Kdyby\Doctrine\EntityManager $entityManager */

		$userDao = $entityManager->getDao('KdybyTests\Doctrine\CmsUser');
		Assert::true($userDao instanceof Kdyby\Doctrine\EntityDao);
	}

}

\run(new ExtensionTest());
