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
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name' => 'Filip'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name = :param_1', array('param_1' => 'Filip'), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name =' => 'Filip'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name = :param_1', array('param_1' => 'Filip'), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name eq' => 'Filip'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name = :param_1', array('param_1' => 'Filip'), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name !=' => 'Filip'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name != :param_1', array('param_1' => 'Filip'), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name !=' => array(1, 2, 3)));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name NOT IN (:param_1)', array('param_1' => array(1, 2, 3)), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name !' => array(1, 2, 3)));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name NOT IN (:param_1)', array('param_1' => array(1, 2, 3)), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name !=' => NULL));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NOT NULL', array(), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name !' => NULL));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NOT NULL', array(), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name neq' => 'Filip'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name != :param_1', array('param_1' => 'Filip'), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name not' => array(1, 2, 3)));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name NOT IN (:param_1)', array('param_1' => array(1, 2, 3)), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name not' => NULL));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NOT NULL', array(), $qb->getQuery());
	}



	public function testWhereCriteria_LowerThan()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name <' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name < :param_1', array('param_1' => 10), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name lt' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name < :param_1', array('param_1' => 10), $qb->getQuery());
	}



	public function testWhereCriteria_LowerOrEqual()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name <=' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name <= :param_1', array('param_1' => 10), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name lte' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name <= :param_1', array('param_1' => 10), $qb->getQuery());
	}



	public function testWhereCriteria_GreaterThan()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name >' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name > :param_1', array('param_1' => 10), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name gt' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name > :param_1', array('param_1' => 10), $qb->getQuery());
	}



	public function testWhereCriteria_GreaterOrEqual()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name >=' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name >= :param_1', array('param_1' => 10), $qb->getQuery());

		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name gte' => 10));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name >= :param_1', array('param_1' => 10), $qb->getQuery());
	}



	public function testWhereCriteria_IsNull()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name' => NULL));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NULL', array(), $qb->getQuery());
	}



	public function testWhereCriteria_InArray()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'c')
			->whereCriteria(array('id' => array(1, 2, 3)));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser c WHERE c.id IN (:param_1)', array('param_1' => array(1, 2, 3)), $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_Equals()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'u')
			->whereCriteria(array('groups.name' => 'Devel'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.groups g0 WHERE g0.name = :param_1', array('param_1' => 'Devel'), $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_MultipleConditionsOnTheSameRelation()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'u')
			->whereCriteria(array('groups.name' => 'Devel', 'groups.title' => 'Nemam'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.groups g0 WHERE g0.name = :param_1 AND g0.title = :param_2', array('param_1' => 'Devel', 'param_2' => 'Nemam'), $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_Deep()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->whereCriteria(array('user.groups.name' => 'Devel'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u0 INNER JOIN u0.groups g0 WHERE g0.name = :param_1', array('param_1' => 'Devel'), $qb->getQuery());
	}



	public function testWhereCriteria_AutoJoin_FixKeywords()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'u')
			->whereCriteria(array('u.order1.status' => 'draft', 'u.order2.status' => 'draft'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.order1 o0 INNER JOIN u.order2 o1 WHERE o0.status = :param_1 AND o1.status = :param_2', array('param_1' => 'draft', 'param_2' => 'draft'), $qb->getQuery());
	}



	protected static function assertQuery($expectedDql, $expectedParams, Doctrine\ORM\Query $query)
	{
		Assert::same($expectedDql, $query->getDQL());

		$actualParameters = array();
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

\run(new QueryBuilderTest());
