<?php

/**
 * Test: Kdyby\Doctrine\Geo\Element.
 *
 * @testCase KdybyTests\Doctrine\Geo\LazyElementTest
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine\Geo;

use Kdyby;
use Kdyby\Doctrine\Geo\Element;
use Kdyby\Doctrine\Geo\IElement;
use Kdyby\Doctrine\Geo\LazyElement;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class LazyElementTest extends Tester\TestCase
{

	const TEST_VALUE = "POLYGON((50.0912919021836 14.4280529022217,50.0937698112904 14.42702293396,50.0934944943838 14.4257354736328))";



	public function testConstructor()
	{
		$element = new LazyElement("");
		Assert::true($element instanceof IElement);
		Assert::true($element instanceof LazyElement);
	}



	public function testLazyObjectCreation()
	{
		$element = new LazyElement(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = $element->getReflection()->getProperty("objectValue");
		$property->setAccessible(TRUE);

		Assert::null($property->getValue($element));

		$element->getName();
		Assert::true($property->getValue($element) instanceof IElement);
		Assert::true($property->getValue($element) instanceof Element);
	}



	public function testOnlyOneLazyCreation()
	{
		$element = new LazyElement(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = $element->getReflection()->getProperty("objectValue");
		$property->setAccessible(TRUE);

		$element->getName();
		$value1 = $property->getValue($element);
		$element->getCoordinates();
		$value2 = $property->getValue($element);

		Assert::same($value1, $value2);
	}



	public function testCloneDoesNotNeedObjectValue()
	{
		$element = new LazyElement(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = $element->getReflection()->getProperty("objectValue");
		$property->setAccessible(TRUE);

		$element2 = clone $element;
		Assert::notSame($element, $element2);
		Assert::null($property->getValue($element));
		Assert::null($property->getValue($element2));
	}



	public function testToStringDoesNotNeedObjectValue()
	{
		$element = new LazyElement(self::TEST_VALUE);
		/** @var \ReflectionProperty $property */
		$property = $element->getReflection()->getProperty("objectValue");
		$property->setAccessible(TRUE);

		Assert::equal(self::TEST_VALUE, (string) $element);
		Assert::null($property->getValue($element));
	}

}



\run(new LazyElementTest());
