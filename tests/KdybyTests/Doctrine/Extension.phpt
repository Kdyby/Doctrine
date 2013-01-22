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
		$config = new Nette\Config\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . md5($configFile))));
		Kdyby\Doctrine\DI\OrmExtension::register($config);
		Kdyby\Events\DI\EventsExtension::register($config);
		Kdyby\Console\DI\ConsoleExtension::register($config);
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer('memory.defaults');

	}

}

\run(new ExtensionTest());
