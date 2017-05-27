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

	public function testDefaultConnectionInfo()
	{
		$applicationTester = $this->executeCommand('orm:info');

		$output = $applicationTester->getDisplay();

		foreach (self::$entities as $entity) {
			Assert::contains("[OK]   {$entity}", $output);
		}
		Assert::notContains('[OK]   ' . \KdybyTests\Doctrine\Models2\Foo::class, $output);
	}



	public function testSecondConnectionInfo()
	{
		$applicationTester = $this->executeCommand('orm:info', ['--em' => 'remote']);

		$output = $applicationTester->getDisplay();

		Assert::contains('[OK]   ' . \KdybyTests\Doctrine\Models2\Foo::class, $output);
		Assert::notContains('[OK]   ' . self::$entities[0], $output);
	}

}

(new ValidateSchemaCommandTest())->run();
