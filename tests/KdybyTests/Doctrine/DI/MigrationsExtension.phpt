<?php

/**
 * Test: Kdyby\Doctrine\DI\MigrationsExtension.
 *
 * @testCase Kdyby\Doctrine\DI\MigrationsExtension
 * @author Pavel Kouřil <pk@pavelkouril.cz>
 * @package Kdyby\Doctrine\DI
 */

namespace KdybyTests\Doctrine\DI;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Pavel Kouřil <pk@pavelkouril.cz>
 */
class MigrationsExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $file
	 * @param array $parameters
	 *
	 * @return \Nette\DI\Container
	 */
	private function createContainer($file, array $parameters = [])
	{
		$loader = new Nette\DI\ContainerLoader(TEMP_DIR, true);
		$class = $loader->load('', function(Nette\DI\Compiler $compiler) use ($file, $parameters) {
			$compiler->addExtension('extensions', new Nette\DI\Extensions\ExtensionsExtension());
			if ($parameters) {
				$compiler->addConfig(['parameters' => $parameters]);
			}
			$compiler->loadConfig($file);
		});
		return new $class;
	}



	public function testRegisterCommands()
	{
		$container = $this->createContainer(__DIR__ . '/config/migrations.extension.neon');
		Assert::count(7, $container->findByTag(Kdyby\Console\DI\ConsoleExtension::TAG_COMMAND));
	}

}

\run(new MigrationsExtensionTest());
