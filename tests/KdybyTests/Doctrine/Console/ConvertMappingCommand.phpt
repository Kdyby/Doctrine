<?php

/**
 * Test: Kdyby\Doctrine\Console\ConvertMappingCommand.
 *
 * @testCase Kdyby\Doctrine\Console\ConvertMappingCommandTest
 * @author Tomáš Jacík <tomas@jacik.cz>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine\Console;

use Nette\Utils\Strings;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 */
class ConvertMappingCommandTest extends CommandTestCase
{

	public function testDefaultConnectionExportXML()
	{
		$destDir = TEMP_DIR . '/ConvertMappingCommandTest.default';

		$applicationTester = $this->executeCommand('orm:convert-mapping', [
			'to-type'   => 'xml',
			'dest-path' => $destDir,
		]);

		$output = $applicationTester->getDisplay();

		foreach (self::$entities as $entity) {
			Assert::contains("Processing entity \"{$entity}\"", $output);
		}
		Assert::notContains(sprintf('Processing entity "%s"', \KdybyTests\Doctrine\Models2\Foo::class), $output);
		Assert::contains('Exporting "xml" mapping information to "' . realpath($destDir) . '"', $output);
	}



	public function testSecondConnectionExportXML()
	{
		$destDir = TEMP_DIR . '/ConvertMappingCommandTest.remote';

		$applicationTester = $this->executeCommand('orm:convert-mapping', [
			'to-type'   => 'xml',
			'dest-path' => $destDir,
			'--em'      => 'remote',
		]);

		$output = $applicationTester->getDisplay();

		Assert::notContains('Processing entity "' . self::$entities[0] . '"', $output);
		Assert::contains(sprintf('Processing entity "%s"', \KdybyTests\Doctrine\Models2\Foo::class), $output);
		Assert::contains('Exporting "xml" mapping information to "' . realpath($destDir) . '"', $output);
	}

}

(new ConvertMappingCommandTest())->run();
