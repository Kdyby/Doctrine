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

	public function testExportEntities()
	{
		FileSystem::createDir(TEMP_DIR . '/GenerateProxiesCommandTest');

		/** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
		$commandTester = $this->executeCommand('orm:generate-proxies', [
			'dest-path' => TEMP_DIR . '/GenerateProxiesCommandTest',
		]);

		$output = $commandTester->getDisplay();

		foreach (self::$models as $model) {
			Assert::contains("Processing entity \"{$model}\"", $output);
		}
		Assert::contains('Proxy classes generated to "' . realpath(TEMP_DIR) . '/GenerateProxiesCommandTest"', $output);
	}

}

\run(new GenerateProxiesCommandTest());
