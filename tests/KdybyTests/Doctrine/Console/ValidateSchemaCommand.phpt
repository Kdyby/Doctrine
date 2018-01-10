<?php

/**
 * Test: Kdyby\Doctrine\Console\ValidateSchemaCommand.
 *
 * @testCase Kdyby\Doctrine\Console\ValidateSchemaCommandTest
 * @author Tomáš Jacík <tomas@jacik.cz>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine\Console;

use Nette\Utils\Strings;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 *
 * TODO: Output of both tests is the same. Solutions is to call orm:schema-tool:create command
 * and don't skip sync to check if each connection is in sync with own schema. For some reason
 * it's not working with sqlite in memory.
 */
class ValidateSchemaCommandTest extends CommandTestCase
{

	public function testDefaultConnectionCheckMapping()
	{
		$applicationTester = $this->executeCommand('orm:validate-schema', [
			'--skip-sync' => TRUE,
		]);

		$output = $applicationTester->getDisplay();

		Assert::contains('The mapping files are correct.', $output);
	}



	public function testSecondConnectionCheckMapping()
	{
		$applicationTester = $this->executeCommand('orm:validate-schema', [
			'--skip-sync' => TRUE,
			'--em'        => 'remote'
		]);

		$output = $applicationTester->getDisplay();

		Assert::contains('The mapping files are correct.', $output);
	}

}

(new ValidateSchemaCommandTest())->run();
