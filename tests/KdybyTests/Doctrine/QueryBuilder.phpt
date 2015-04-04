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
use Doctrine\ORM\Query\Expr\Join;
use Kdyby;
use KdybyTests;
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
		$this->em = $this->newInstance('Kdyby\Doctrine\EntityManager');
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



	public function testInlineParameters_Where()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username", 'Filip');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username', array('username' => 'Filip'), $qb->getQuery());
	}



	public function testInlineParameters_Where_MultipleParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', array(1, 2, 3));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username AND a.city = ?2 AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_Where_SameParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username AND a.city = :username", 'Filip');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username AND a.city = :username', array(
			'username' => 'Filip',
		), $qb->getQuery());
	}



	public function testInlineParameters_Where_NullParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username AND a.city = :username", NULL);

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE a.user = :username AND a.city = :username', array(
			'username' => NULL,
		), $qb->getQuery());
	}



	public function testInlineParameters_Where_MultipleConditions()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username AND a.city = ?2", 'Filip', 'Brno', "a.id IN (:ids)", array(1, 2, 3));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a WHERE (a.user = :username AND a.city = ?2) AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_Join()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u WITH a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_Join_WithIndexBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno', 'a.postalCode');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u INDEX BY a.postalCode WITH a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_Join_MultipleParameters()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a.user', 'u', Join::WITH, "a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', array(1, 2, 3));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u WITH a.user = :username AND a.city = ?2 AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_Join_MultipleParameters_WithIndexBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a.user', 'u', Join::WITH, "a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', array(1, 2, 3), "a.postalCode");

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u INDEX BY a.postalCode WITH a.user = :username AND a.city = ?2 AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_InnerJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->innerJoin('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u WITH a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_LeftJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->leftJoin('a.user', 'u', Join::WITH, 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a LEFT JOIN a.user u WITH a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
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
