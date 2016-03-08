<?php

/**
 * Test: Kdyby\DibiBridge\DibiExtension.
 *
 * @testCase KdybyTests\DibiBridge\DibiBridgeTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\DibiBridge
 */

namespace KdybyTests\DibiBridge;

use Doctrine;
use Kdyby;
use Kdyby\Doctrine\Connection;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * This test is here just to ensure compatibility of Dibi.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class DibiBridgeTest extends Tester\TestCase
{

	public function testFunctional()
	{
		// create doctrine
		$doctrine = Connection::create([
			'driver' => 'pdo_sqlite',
			'memory' => TRUE,
		], new Doctrine\DBAL\Configuration(), new Kdyby\Events\EventManager());

		// create dibi
		$dibi = new \DibiConnection([
			'driver' => 'pdo',
			'resource' => $doctrine->getWrappedConnection(),
		]);

		// resource is same
		Assert::same($doctrine->getWrappedConnection(), $dibi->getDriver()->getResource());

		// test
		$result = $dibi->query("SELECT lower('ABC')");
		Assert::same('abc', $result->fetchSingle());
	}

}

\run(new DibiBridgeTest());
