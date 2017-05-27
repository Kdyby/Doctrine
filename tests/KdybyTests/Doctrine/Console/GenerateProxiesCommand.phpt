<?php

/**
 * Test: Kdyby\Doctrine\Console\GenerateProxiesCommand.
 *
 * @testCase Kdyby\Doctrine\Console\GenerateProxiesCommandTest
 * @author Tomáš Jacík <tomas@jacik.cz>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine\Console;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 */
class GenerateProxiesCommandTest extends CommandTestCase
{

	public function testDefaultConnectionExportEntities()
	{
		$destDir = TEMP_DIR . '/GenerateProxiesCommandTest.default';
		FileSystem::createDir($destDir);

		$applicationTester = $this->executeCommand('orm:generate-proxies', [
			'dest-path' => $destDir,
		]);

		$output = $applicationTester->getDisplay();

		foreach (self::$entities as $entity) {
			Assert::contains("Processing entity \"{$entity}\"", $output);
		}
		Assert::notContains(sprintf('Processing entity "%s"', \KdybyTests\Doctrine\Models2\Foo::class), $output);
		Assert::contains('Proxy classes generated to "' . realpath($destDir) . '"', $output);
	}



	public function testSecondConnectionExportEntities()
	{
		$destDir = TEMP_DIR . '/GenerateProxiesCommandTest.remote';
		FileSystem::createDir($destDir);

		$applicationTester = $this->executeCommand('orm:generate-proxies', [
			'dest-path' => $destDir,
			'--em'      => 'remote',
		]);

		$output = $applicationTester->getDisplay();

		Assert::notContains('Processing entity "' . self::$entities[0] . '"', $output);
		Assert::contains(sprintf('Processing entity "%s"', \KdybyTests\Doctrine\Models2\Foo::class), $output);
		Assert::contains('Proxy classes generated to "' . realpath($destDir) . '"', $output);
	}

}

(new GenerateProxiesCommandTest())->run();
