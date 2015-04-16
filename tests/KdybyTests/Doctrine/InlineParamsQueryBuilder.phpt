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
		$this->em->getConfiguration()->setQueryBuilderClassName('Kdyby\Doctrine\Dql\InlineParamsBuilder');
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

\run(new InlineParamsQueryBuilderTest());
