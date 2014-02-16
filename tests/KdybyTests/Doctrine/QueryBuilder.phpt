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



	public function testWhere_Equals()
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
			->whereCriteria(array('name neq' => 'Filip'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name != :param_1', array('param_1' => 'Filip'), $qb->getQuery());
	}



	public function testWhere_LowerThan()
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



	public function testWhere_LowerOrEqual()
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



	public function testWhere_GreaterThan()
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



	public function testWhere_GreaterOrEqual()
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



	public function testWhere_IsNull()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->whereCriteria(array('name' => NULL));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser e WHERE e.name IS NULL', array(), $qb->getQuery());
	}



	public function testWhere_InArray()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'c')
			->whereCriteria(array('id' => array(1, 2, 3)));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser c WHERE c.id IN (:param_1)', array('param_1' => array(1, 2, 3)), $qb->getQuery());
	}



	public function testWhere_AutoJoin_Equals()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsUser', 'u')
			->whereCriteria(array('groups.name' => 'Devel'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsUser u INNER JOIN u.groups g WHERE g.name = :param_1', array('param_1' => 'Devel'), $qb->getQuery());
	}



	public function testWhere_AutoJoin_Deep()
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->whereCriteria(array('user.groups.name' => 'Devel'));

		self::assertQuery('SELECT e FROM KdybyTests\Doctrine\CmsAddress a INNER JOIN a.user u INNER JOIN u.groups g WHERE g.name = :param_1', array('param_1' => 'Devel'), $qb->getQuery());
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
