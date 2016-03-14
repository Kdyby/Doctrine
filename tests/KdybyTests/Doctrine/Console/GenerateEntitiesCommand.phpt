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

	public function testDefaultConnectionExportEntities()
	{
		$destDir = TEMP_DIR . '/GenerateEntitiesCommandTest.default';
		FileSystem::createDir($destDir);

		$applicationTester = $this->executeCommand('orm:generate-entities', [
			'dest-path' => $destDir,
		]);

		$output = $applicationTester->getDisplay();

		foreach (self::$entities as $entity) {
			Assert::contains("Processing entity \"{$entity}\"", $output);
		}
		Assert::notContains('Processing entity "KdybyTests\Doctrine\Models2\Foo"', $output);
		Assert::contains('Entity classes generated to "' . realpath($destDir) . '"', $output);
	}



	public function testSecondConnectionExportEntities()
	{
		$destDir = TEMP_DIR . '/GenerateEntitiesCommandTest.remote';
		FileSystem::createDir($destDir);

		$applicationTester = $this->executeCommand('orm:generate-entities', [
			'dest-path' => $destDir,
			'--em'      => 'remote',
		]);

		$output = $applicationTester->getDisplay();

		Assert::notContains('Processing entity "' . self::$entities[0] . '"', $output);
		Assert::contains('Processing entity "KdybyTests\Doctrine\Models2\Foo"', $output);
		Assert::contains('Entity classes generated to "' . realpath($destDir) . '"', $output);
	}

}

\run(new GenerateEntitiesCommandTest());
