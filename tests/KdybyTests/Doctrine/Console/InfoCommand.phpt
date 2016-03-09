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
 */
class ValidateSchemaCommandTest extends CommandTestCase
{

	public function testInfo()
	{
		/** @var \Symfony\Component\Console\Tester\CommandTester $commandTester */
		$commandTester = $this->executeCommand('orm:info');

		$output = $commandTester->getDisplay();

		foreach (self::$models as $model) {
			Assert::contains("[OK]   {$model}", $output);
		}
	}

}

\run(new ValidateSchemaCommandTest());
