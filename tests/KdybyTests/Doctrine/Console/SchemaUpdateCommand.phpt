<?php

/**
 * Test: Kdyby\Doctrine\Console\SchemaUpdateCommand
 *
 * @testCase Kdyby\Doctrine\Console\SchemaUpdateCommandTest
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
class SchemaUpdateCommandTest extends CommandTestCase
{

	public function testDefaultConnectionDumpSQL()
	{
		$applicationTester = $this->executeCommand('orm:schema-tool:update', [
			'--dump-sql' => TRUE,
		]);

		$output = $applicationTester->getDisplay();

		foreach (self::$tables as $table) {
			Assert::contains("CREATE TABLE {$table}", $output);
		}
		Assert::notContains('CREATE TABLE model2_foo', $output);
	}



	public function testSecondConnectionDumpSQL()
	{
		$applicationTester = $this->executeCommand('orm:schema-tool:update', [
			'--dump-sql' => TRUE,
			'--em'       => 'remote',
		]);

		$output = $applicationTester->getDisplay();

		Assert::contains("CREATE TABLE model2_foo", $output);
		Assert::notContains('CREATE TABLE ' . self::$tables[0], $output);
	}

}

(new SchemaUpdateCommandTest())->run();
