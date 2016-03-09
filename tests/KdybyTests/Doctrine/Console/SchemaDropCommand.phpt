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

	public function testDumpSQL()
	{
		$this->executeCommand('orm:schema-tool:create');

		/** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
		$commandTester = $this->executeCommand('orm:schema-tool:drop', [
			'--dump-sql' => TRUE,
		]);

		$output = $commandTester->getDisplay();

		foreach (self::$tables as $table) {
			Assert::contains("DROP TABLE {$table}", $output);
		}
	}

}

\run(new SchemaDropCommandTest());
