<?php

/**
 * Test: Kdyby\Doctrine\Geo\Element.
 *
 * @testCase KdybyTests\Doctrine\Geo\ElementTest
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine\Geo;

use Kdyby;
use Kdyby\Doctrine\Geo\Element;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class ElementTest extends Tester\TestCase
{

	const TEST_VALUE = "POLYGON((50.0912919021836 14.4280529022217,50.0937698112904 14.42702293396,50.0934944943838 14.4257354736328))";



	public function testConstructor()
	{
		$element = Element::fromString(self::TEST_VALUE);
		Assert::true($element instanceof Element);
	}



	public function testLazyObjectCreation()
	{
		$element = Element::fromString(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = new \ReflectionProperty($element, 'stringValue');
		$property->setAccessible(TRUE);

		Assert::type("string", $property->getValue($element));

		$element->getName();
		Assert::null($property->getValue($element));
	}



	public function testOnlyOneLazyCreation()
	{
		$element = Element::fromString(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = new \ReflectionProperty($element, 'stringValue');
		$property->setAccessible(TRUE);

		$element->getName();
		$value1 = $property->getValue($element);
		$element->getCoordinates();
		$value2 = $property->getValue($element);

		Assert::null($value1);
		Assert::null($value2);
	}



	public function testCloneDoesNotNeedObjectValue()
	{
		$element = Element::fromString(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = new \ReflectionProperty($element, 'stringValue');
		$property->setAccessible(TRUE);

		$element2 = clone $element;
		Assert::notSame($element, $element2);
		Assert::type("string", $property->getValue($element));
		Assert::type("string", $property->getValue($element2));
	}



	public function testLongStringValue()
	{
		$stringValue = 'POLYGON((50.1049501 14.4862063' . str_repeat(', 50.1049501 14.4862063', 100000) . '))';
		$element = Element::fromString($stringValue);

		Assert::noError(function () use ($element) {
			// force string validation
			$element->freeze();
		});
	}

}



(new ElementTest())->run();
