<?php

/**
 * Test: Kdyby\Doctrine\Geo\Element.
 *
 * @testCase KdybyTests\Doctrine\GeoElementTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class GeoElementTest extends Tester\TestCase
{

	public function dataValidConversion()
	{
		return [
			[
				'POINT(1 2)',
				$this->stubElement('POINT', [[1.0, 2.0]]),
				'POINT(1.0000000000000 2.0000000000000)'
			],
			[
				'LINESTRING(1 2,3 4)',
				$this->stubElement('LINESTRING', [[1.0, 2.0], [3.0, 4.0]]),
				'LINESTRING(1.0000000000000 2.0000000000000,3.0000000000000 4.0000000000000)',
			],
			[
				"POLYGON((1.0000000000000  2.0000000000000, 3.0000000000000 \t4.0000000000000))",
				$this->stubElement('POLYGON', [[1.0, 2.0], [3.0, 4.0]]),
				'POLYGON((1.0000000000000 2.0000000000000,3.0000000000000 4.0000000000000))',
			],
		];
	}



	/**
	 * @dataProvider dataValidConversion
	 */
	public function testPhpToSql($inputText, $object, $formattedText)
	{
		$input = Kdyby\Doctrine\Geo\Element::fromString($inputText);
		$input->getName();
		Assert::equal($object, $input);
		Assert::same($formattedText, (string) $object);
	}



	/**
	 * @param string $name
	 * @param array $coordsList
	 * @return Kdyby\Doctrine\Geo\Element
	 */
	private function stubElement($name, array $coordsList)
	{
		$el = new Kdyby\Doctrine\Geo\Element($name);
		foreach ($coordsList as $coords) {
			$el->addCoordinate($coords[1], $coords[0]);
		}

		return $el->freeze();
	}

}

(new GeoElementTest())->run();
