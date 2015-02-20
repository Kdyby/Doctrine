<?php

/**
 * Test: Kdyby\Doctrine\Diagnostics\SimpleParameterFormatter.
 *
 * @testCase Kdyby\Doctrine\Diagnostics\SimpleParameterFormatterTest
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine\Diagnostics;

use Kdyby\Doctrine\Diagnostics\SimpleParameterFormatter;
use Kdyby\Doctrine\Geo\Coordinates;
use Kdyby\Doctrine\Geo\Element;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class SimpleParameterFormatterTest extends Tester\TestCase
{

	public function testNumeric()
	{
		Assert::same(1024, SimpleParameterFormatter::format(1024));
		Assert::same(1024.001, SimpleParameterFormatter::format(1024.001));
		Assert::same(-1024, SimpleParameterFormatter::format(-1024));
		Assert::same(-1024.001, SimpleParameterFormatter::format(-1024.001));
	}



	public function testString()
	{
		Assert::same("'1024'", SimpleParameterFormatter::format("1024"));
		Assert::same("'1024.001'", SimpleParameterFormatter::format("1024.001"));
		Assert::same("'-1024'", SimpleParameterFormatter::format("-1024"));
		Assert::same("'-1024.001'", SimpleParameterFormatter::format("-1024.001"));
		Assert::same("'quick brown fox jumps over lazy dog'", SimpleParameterFormatter::format("quick brown fox jumps over lazy dog"));
		Assert::same("'//\\\\\\\\'", SimpleParameterFormatter::format("//\\\\"));
	}



	public function testNull()
	{
		Assert::same("NULL", SimpleParameterFormatter::format(NULL));
	}



	public function testBoolean()
	{
		Assert::same("TRUE", SimpleParameterFormatter::format(TRUE));
		Assert::same("FALSE", SimpleParameterFormatter::format(FALSE));
	}



	public function testArray()
	{
		Assert::same("1, 2, 3, 4", SimpleParameterFormatter::format(array(1, 2, 3, 4)));
		Assert::same("1, 'dog', NULL, TRUE, '2014-04-18 15:00:00'", SimpleParameterFormatter::format(array(1, "dog", NULL, TRUE,
					new \DateTime("2014-04-18 15:00:00"))));
		Assert::same("1, 2, 3, 4", SimpleParameterFormatter::format(array(array(1, 2), array(3, 4))));
	}



	public function testDateTime()
	{
		Assert::same("'2014-04-18 15:00:00'", SimpleParameterFormatter::format(new \DateTime("2014-04-18 15:00:00")));
		Assert::same("'" . date("Y-m-d") . " 15:00:00'", SimpleParameterFormatter::format(new \DateTime("15:00:00")));
		Assert::same("'2014-04-18 00:00:00'", SimpleParameterFormatter::format(new \DateTime("2014-04-18")));
	}



	public function testGeo()
	{
		$element = new Element();
		$element->addCoordinate(14.000000, 51.000000);
		Assert::same('"POINT(51.0000000000000 14.0000000000000)"', SimpleParameterFormatter::format($element));
	}



	public function testObject()
	{
		Assert::same('stdClass', SimpleParameterFormatter::format(new \stdClass));
		Assert::same('Kdyby\Doctrine\Geo\Coordinates', SimpleParameterFormatter::format(new Coordinates(14.000000, 51.000000)));
	}

}



\run(new SimpleParameterFormatterTest());
