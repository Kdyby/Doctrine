<?php

/**
 * Test: Kdyby\Doctrine\Console\SchemaCreateCommand.
 *
 * @testCase Kdyby\Doctrine\Console\SchemaCreateCommandTest
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
class SchemaCreateCommandTest extends CommandTestCase
{

	public function testDumpSQL()
	{
		/** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
		$commandTester = $this->executeCommand('orm:schema-tool:create', [
			'--dump-sql' => TRUE,
		]);

		$output = $commandTester->getDisplay();

		foreach (self::$tables as $table) {
			Assert::contains("CREATE TABLE {$table}", $output);
		}
	}

}

\run(new SchemaCreateCommandTest());
