<?php

/**
 * Test: Kdyby\Doctrine\Console\SchemaDropCommand
 *
 * @testCase Kdyby\Doctrine\Console\SchemaDropCommandTest
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
class SchemaDropCommandTest extends CommandTestCase
{

	public function testDefaultConnectionDumpSQL()
	{
		$this->executeCommand('orm:schema-tool:create');

		$applicationTester = $this->executeCommand('orm:schema-tool:drop', [
			'--dump-sql' => TRUE,
		]);

		$output = $applicationTester->getDisplay();

		foreach (self::$tables as $table) {
			Assert::contains("DROP TABLE {$table}", $output);
		}
		Assert::notContains('DROP TABLE model2_foo', $output);
	}



	public function testSecondConnectionDumpSQL()
	{
		$this->executeCommand('orm:schema-tool:create', ['--em' => 'remote']);

		$applicationTester = $this->executeCommand('orm:schema-tool:drop', [
			'--dump-sql' => TRUE,
			'--em'       => 'remote',
		]);

		$output = $applicationTester->getDisplay();

		Assert::contains("DROP TABLE model2_foo", $output);
		Assert::notContains('DROP TABLE ' . self::$tables[0], $output);
	}

}

(new SchemaDropCommandTest())->run();
