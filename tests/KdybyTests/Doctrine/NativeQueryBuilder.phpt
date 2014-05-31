<?php

/**
 * Test: Kdyby\Doctrine\NativeQueryBuilder.
 *
 * @testCase Kdyby\Doctrine\NativeQueryBuilderTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Kdyby;
use Kdyby\Doctrine\NativeQueryBuilder;
use KdybyTests;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/models/cms.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NativeQueryBuilderTest extends KdybyTests\Doctrine\ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = $this->createMemoryManager();
	}



	public function testSelect()
	{
		$qb = (new NativeQueryBuilder($this->em));
		$qb->select('e.*')->from(__NAMESPACE__ . '\\CmsUser', 'e')
			->where('e.name = ' . $qb->createNamedParameter('Filip'));

		self::assertQuery('SELECT e.* FROM cms_users e WHERE e.name = :dcValue1', array('dcValue1' => 'Filip'), $qb->getQuery());
	}



	public function testInlineParameters_Where()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username", 'Filip');

		self::assertQuery('SELECT e.* FROM cms_addresses a WHERE a.user = :username', array('username' => 'Filip'), $qb->getQuery());
	}



	public function testInlineParameters_Where_MultipleParameters()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', array(1, 2, 3));

		self::assertQuery('SELECT e.* FROM cms_addresses a WHERE a.user = :username AND a.city = ?2 AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_Where_MultipleConditions()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->where("a.user = :username AND a.city = ?2", 'Filip', 'Brno', "a.id IN (:ids)", array(1, 2, 3));

		self::assertQuery('SELECT e.* FROM cms_addresses a WHERE (a.user = :username AND a.city = ?2) AND (a.id IN (:ids))', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_Join()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a', 'cms_users', 'u', 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e.* FROM cms_addresses a INNER JOIN cms_users u ON a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_Join_WithIndexBy()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a', 'cms_users', 'u', 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e.* FROM cms_addresses a INNER JOIN cms_users u ON a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_Join_MultipleParameters()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a', 'cms_users', 'u', "a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', array(1, 2, 3));

		self::assertQuery('SELECT e.* FROM cms_addresses a INNER JOIN cms_users u ON a.user = :username AND a.city = ?2 AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_Join_MultipleParameters_WithIndexBy()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->join('a', 'cms_users', 'u', "a.user = :username AND a.city = ?2 AND a.id IN (:ids)", 'Filip', 'Brno', array(1, 2, 3));

		self::assertQuery('SELECT e.* FROM cms_addresses a INNER JOIN cms_users u ON a.user = :username AND a.city = ?2 AND a.id IN (:ids)', array(
			'username' => 'Filip',
			2 => 'Brno',
			'ids' => array(1, 2, 3),
		), $qb->getQuery());
	}



	public function testInlineParameters_InnerJoin()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->innerJoin('a', 'cms_users', 'u', 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e.* FROM cms_addresses a INNER JOIN cms_users u ON a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_LeftJoin()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->leftJoin('a', 'cms_users', 'u', 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e.* FROM cms_addresses a LEFT JOIN cms_users u ON a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	public function testInlineParameters_RightJoin()
	{
		$qb = (new NativeQueryBuilder($this->em))
			->select('e.*')->from(__NAMESPACE__ . '\\CmsAddress', 'a')
			->rightJoin('a', 'cms_users', 'u', 'a.city = :city_name', 'Brno');

		self::assertQuery('SELECT e.* FROM cms_addresses a LEFT JOIN cms_users u ON a.city = :city_name', array(
			'city_name' => 'Brno',
		), $qb->getQuery());
	}



	protected static function assertQuery($expectedDql, $expectedParams, Kdyby\Doctrine\NativeQueryWrapper $query)
	{
		Assert::same($expectedDql, $query->getSQL());

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

\run(new NativeQueryBuilderTest());
