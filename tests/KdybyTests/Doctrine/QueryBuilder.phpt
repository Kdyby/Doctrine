<?php

/**
 * Test: Kdyby\Doctrine\QueryBuilder.
 *
 * @testCase Kdyby\Doctrine\QueryBuilderTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
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
class QueryBuilderTest extends KdybyTests\Doctrine\ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = EntityManagerMock::create(new ConnectionMock([], new DriverMock()));
	}



	public function testWhereCriteria_Equals()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name' => 'Filip']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name = :param_1', ['param_1' => 'Filip'], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name =' => 'Filip']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name = :param_1', ['param_1' => 'Filip'], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name eq' => 'Filip']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name = :param_1', ['param_1' => 'Filip'], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name !=' => 'Filip']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name != :param_1', ['param_1' => 'Filip'], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name !=' => [1, 2, 3]]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name NOT IN (:param_1)', ['param_1' => [1, 2, 3]], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name !' => [1, 2, 3]]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name NOT IN (:param_1)', ['param_1' => [1, 2, 3]], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name !=' => NULL]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NOT NULL', [], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name !' => NULL]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NOT NULL', [], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name neq' => 'Filip']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name != :param_1', ['param_1' => 'Filip'], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name not' => [1, 2, 3]]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name NOT IN (:param_1)', ['param_1' => [1, 2, 3]], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name not' => NULL]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NOT NULL', [], $qb->getQuery());
	}



	public function testWhereCriteria_LowerThan()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name <' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name < :param_1', ['param_1' => 10], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name lt' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name < :param_1', ['param_1' => 10], $qb->getQuery());
	}



	public function testWhereCriteria_LowerOrEqual()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name <=' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name <= :param_1', ['param_1' => 10], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name lte' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name <= :param_1', ['param_1' => 10], $qb->getQuery());
	}



	public function testWhereCriteria_GreaterThan()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name >' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name > :param_1', ['param_1' => 10], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name gt' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name > :param_1', ['param_1' => 10], $qb->getQuery());
	}



	public function testWhereCriteria_GreaterOrEqual()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name >=' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name >= :param_1', ['param_1' => 10], $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name gte' => 10]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name >= :param_1', ['param_1' => 10], $qb->getQuery());
	}



	public function testWhereCriteria_IsNull()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'e')
			->whereCriteria(['name' => NULL]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NULL', [], $qb->getQuery());
	}



	public function testWhereCriteria_InArray()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'c')
			->whereCriteria(['id' => [1, 2, 3]]);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser c WHERE c.id IN (:param_1)', ['param_1' => [1, 2, 3]], $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_Equals()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'u')
			->whereCriteria(['groups.name' => 'Devel']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.groups g0 WHERE g0.name = :param_1', ['param_1' => 'Devel'], $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_MultipleConditionsOnTheSameRelation()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'u')
			->whereCriteria(['groups.name' => 'Devel', 'groups.title' => 'Nemam']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.groups g0 WHERE g0.name = :param_1 AND g0.title = :param_2', ['param_1' => 'Devel', 'param_2' => 'Nemam'], $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_Deep()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsAddress::class, 'a')
			->whereCriteria(['user.groups.name' => 'Devel']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u0 INNER JOIN u0.groups g0 WHERE g0.name = :param_1', ['param_1' => 'Devel'], $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_FixKeywords()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(\KdybyTests\Doctrine\CmsUser::class, 'u')
			->whereCriteria(['u.order1.status' => 'draft', 'u.order2.status' => 'draft']);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.order1 o0 INNER JOIN u.order2 o1 WHERE o0.status = :param_1 AND o1.status = :param_2', ['param_1' => 'draft', 'param_2' => 'draft'], $qb->getQuery());
	}


	public function testOrderByCriteria_AutoJoin_Equals()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')->from(\KdybyTests\Doctrine\CmsUser::class, 'u')
			->autoJoinOrderBy('COUNT(groups.id)');

		Assert::same('SELECT u, COUNT(g0.id) as HIDDEN g0id0 FROM KdybyTests\Doctrine\CmsUser u LEFT JOIN u.groups g0 GROUP BY u.id ORDER BY g0id0 ASC', $qb->getDQL());
	}


	public function testOrderByCriteria_AutoJoin_Equals2()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')->from(\KdybyTests\Doctrine\CmsUser::class, 'u')
			->autoJoinOrderBy('COUNT(DISTINCT(groups.id))');

		Assert::same('SELECT u, COUNT(DISTINCT(g0.id)) as HIDDEN g0id0 FROM KdybyTests\Doctrine\CmsUser u LEFT JOIN u.groups g0 GROUP BY u.id ORDER BY g0id0 ASC', $qb->getDQL());
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

(new QueryBuilderTest())->run();
