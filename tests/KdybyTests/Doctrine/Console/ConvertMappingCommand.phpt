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

	public function testExportXML()
	{
		/** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
		$commandTester = $this->executeCommand('orm:convert-mapping', [
			'to-type'   => 'xml',
			'dest-path' => TEMP_DIR . '/ConvertMappingCommandTest',
		]);

		$output = $commandTester->getDisplay();

		foreach (self::$entities as $entity) {
			Assert::contains("Processing entity \"{$entity}\"", $output);
		}
		Assert::contains('Exporting "xml" mapping information to "' . realpath(TEMP_DIR) . '/ConvertMappingCommandTest"', $output);
	}

}

\run(new ConvertMappingCommandTest());
