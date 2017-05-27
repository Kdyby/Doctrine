<?php

/**
 * Test: Kdyby\Doctrine\Dql\InlineParamsBuilder.
 *
 * @testCase Kdyby\Doctrine\InlineParamsQueryBuilderTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Doctrine\ORM\Query\Expr\Join;
use Kdyby;
use KdybyTests;
use KdybyTests\DoctrineMocks\ConnectionMock;
use KdybyTests\DoctrineMocks\DriverMock;
use KdybyTests\DoctrineMocks\EntityManagerMock;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/models/cms.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InlineParamsQueryBuilderTest extends KdybyTests\Doctrine\ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = EntityManagerMock::create(new ConnectionMock([], new DriverMock()));
		$this->em->getConfiguration()->setQueryBuilderClassName(\Kdyby\Doctrine\Dql\InlineParamsBuilder::class);
	}



	public function testInlineParameters_Where()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->where("a.user = :username", 'Filip');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username', ['username' => 'Filip'], $qb->getQuery());
	}



	public function testInlineParameters_Where_MultipleParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->where("a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', [1, 2, 3]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username AND a.city = ?2 AND a.id IN (:ids)', [
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => [1, 2, 3],
		], $qb->getQuery());
	}



	public function testInlineParameters_Where_SameParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->where("a.user = :username AND a.city = :username", 'Filip');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username AND a.city = :username', [
			'username' => 'Filip',
		], $qb->getQuery());
	}



	public function testInlineParameters_Where_NullParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->where("a.user = :username AND a.city = :username", NULL);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username AND a.city = :username', [
			'username' => NULL,
		], $qb->getQuery());
	}



	public function testInlineParameters_Where_MultipleConditions()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->where("a.user = :username AND a.city = ?2", 'Filip', 'Brno', "a.id IN (:ids)", [1, 2, 3]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE (a.user = :username AND a.city = ?2) AND a.id IN (:ids)', [
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => [1, 2, 3],
		], $qb->getQuery());
	}



	public function testInlineParameters_Join()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->join('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u WITH a.city = :city_name', [
			'city_name' => 'Brno',
		], $qb->getQuery());
	}



	public function testInlineParameters_Join_WithIndexBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->join('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno', 'a.postalCode');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u INDEX BY a.postalCode WITH a.city = :city_name', [
			'city_name' => 'Brno',
		], $qb->getQuery());
	}



	public function testInlineParameters_Join_MultipleParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->join('a.user', 'u', Join::WITH, "a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', [1, 2, 3]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u WITH a.user = :username AND a.city = ?2 AND a.id IN (:ids)', [
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => [1, 2, 3],
		], $qb->getQuery());
	}



	public function testInlineParameters_Join_MultipleParameters_WithIndexBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->join('a.user', 'u', Join::WITH, "a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', [1, 2, 3], "a.postalCode");

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u INDEX BY a.postalCode WITH a.user = :username AND a.city = ?2 AND a.id IN (:ids)', [
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => [1, 2, 3],
		], $qb->getQuery());
	}



	public function testInlineParameters_InnerJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->innerJoin('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u WITH a.city = :city_name', [
			'city_name' => 'Brno',
		], $qb->getQuery());
	}



	public function testInlineParameters_LeftJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->leftJoin('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a LEFT JOIN a.user u WITH a.city = :city_name', [
			'city_name' => 'Brno',
		], $qb->getQuery());
	}



	protected static function assertQuery($expectedDql, $expectedParams, Doctrine\ORM\Query $query)
	{
		Assert::same($expectedDql, $query->getDQL());

		$actualParameters = [];
		foreach ($query->getParameters() as $key => $value) {
			if ($value instanceof Doctrine\ORM\Query\Parameter) {
				$actualParameters[$value->getName()] = $value->getValue();
				continue;
			}
			$actualParameters[$key] = $value;
		}
		Assert::same($expectedParams, $actualParameters);
	}

}

(new InlineParamsQueryBuilderTest())->run();
