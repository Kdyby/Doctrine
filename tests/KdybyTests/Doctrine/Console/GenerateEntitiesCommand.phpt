<?php

/**
 * Test: Kdyby\Doctrine\Console\GenerateEntitiesCommand.
 *
 * @testCase Kdyby\Doctrine\Console\GenerateEntitiesCommandTest
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
class GenerateEntitiesCommandTest extends CommandTestCase
{

	public function testExportEntities()
	{
		FileSystem::createDir(TEMP_DIR . '/GenerateEntitiesCommandTest');

		/** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
		$commandTester = $this->executeCommand('orm:generate-entities', [
			'dest-path' => TEMP_DIR . '/GenerateEntitiesCommandTest',
		]);

		$output = $commandTester->getDisplay();

		foreach (self::$models as $model) {
			Assert::contains("Processing entity \"{$model}\"", $output);
		}
		Assert::contains('Entity classes generated to "' . realpath(TEMP_DIR) . '/GenerateEntitiesCommandTest"', $output);
	}

}

\run(new GenerateEntitiesCommandTest());
