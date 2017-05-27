<?php

/**
 * Test: Kdyby\Doctrine\Types\GeometryType.
 *
 * @testCase KdybyTests\Doctrine\GeometryTypeTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class GeometryTypeTest extends Tester\TestCase
{

	/**
	 * @var Kdyby\Doctrine\Types\Polygon
	 */
	private $polygon;



	protected function setUp()
	{
		$this->polygon = Code\Helpers::createObject(\Kdyby\Doctrine\Types\Polygon::class, []);
	}



	public function testInheritance()
	{
		Assert::true(is_subclass_of(\Kdyby\Doctrine\Types\GeometryCollection::class, \Kdyby\Doctrine\Types\GeometryType::class));
		Assert::true(is_subclass_of(\Kdyby\Doctrine\Types\LineString::class, \Kdyby\Doctrine\Types\GeometryType::class));
		Assert::true(is_subclass_of(\Kdyby\Doctrine\Types\MultiLineString::class, \Kdyby\Doctrine\Types\GeometryType::class));
		Assert::true(is_subclass_of(\Kdyby\Doctrine\Types\MultiPolygon::class, \Kdyby\Doctrine\Types\GeometryType::class));
		Assert::true(is_subclass_of(\Kdyby\Doctrine\Types\Point::class, \Kdyby\Doctrine\Types\GeometryType::class));
		Assert::true(is_subclass_of(\Kdyby\Doctrine\Types\Polygon::class, \Kdyby\Doctrine\Types\GeometryType::class));
	}



	public function testPhpToSql()
	{
		$result = $this->polygon->convertToDatabaseValueSQL('?', new MySqlPlatform());
		Assert::same('GeomFromText(?)', $result);
	}



	public function testSqlToPhp()
	{
		$result = $this->polygon->convertToPHPValueSQL('table.column', new MySqlPlatform());
		Assert::same('AsText(table.column)', $result);
	}



	public function testConvertToPhp()
	{
		$point = $this->polygon->convertToPHPValue('POINT((1 2))', new MySqlPlatform());
		Assert::true($point instanceof Kdyby\Doctrine\Geo\Element);
	}



	public function testConvertToSql()
	{
		$point = new Kdyby\Doctrine\Geo\Element(Kdyby\Doctrine\Geo\Element::POINT);
		$point->addCoordinate(1, 2);

		$sql = $this->polygon->convertToDatabaseValue($point, new MySqlPlatform());
		Assert::same('POINT(2.0000000000000 1.0000000000000)', $sql);
	}

}

(new GeometryTypeTest())->run();
