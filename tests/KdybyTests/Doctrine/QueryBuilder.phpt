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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Kdyby;
use Kdyby\Doctrine\Query;
use KdybyTests;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class QueryBuilderTest extends KdybyTests\ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = $this->createTestEntityManager();
	}



	public function testSimpleSelect()
	{
		$qb = $this->em->createQueryBuilder()
			->from('test:CmsUser', 'u')
			->select('u.id', 'u.username');

		Assert::match('SELECT u.id, u.username FROM test:CmsUser u', $qb->getDQL());
	}



	public function testSimpleDelete()
	{
		$qb = $this->em->createQueryBuilder()
			->delete('test:CmsUser', 'u');

		Assert::match('DELETE test:CmsUser u', $qb->getDQL());
	}



	public function testSimpleUpdate()
	{
		$qb = $this->em->createQueryBuilder()
			->update('test:CmsUser', 'u', array('username' => ':username'));

		Assert::match('UPDATE test:CmsUser u SET u.username = :%S%username', $qb->getDQL());
	}



	public function testInnerJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'a')
			->from('test:CmsUser', 'u')
			->join('u.articles', 'a');

		Assert::match('SELECT u, a FROM test:CmsUser u INNER JOIN u.articles a', $qb->getDQL());
	}



	public function testComplexInnerJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'a')
			->from('test:CmsUser', 'u')
			->join('u.articles', 'a')->on('u.id = a.author_id');

		Assert::match('SELECT u, a FROM test:CmsUser u INNER JOIN u.articles a ON u.id = a.author_id', $qb->getDQL());
	}



	public function testLeftJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'a')
			->from('test:CmsUser', 'u')
			->leftJoin('u.articles', 'a');

		Assert::match('SELECT u, a FROM test:CmsUser u LEFT JOIN u.articles a', $qb->getDQL());
	}



	public function testLeftJoinWithIndexBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'a')
			->from('test:CmsUser', 'u')
			->leftJoin('u.articles', 'a', 'a.name');

		Assert::match('SELECT u, a FROM test:CmsUser u LEFT JOIN u.articles a INDEX BY a.name', $qb->getDQL());
	}



	public function testMultipleFrom()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'g')
			->from('test:CmsUser', 'u')
			->from('test:CmsGroup', 'g');

		Assert::match('SELECT u, g FROM test:CmsUser u, test:CmsGroup g', $qb->getDQL());
	}



	public function testMultipleFromWithJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'g')
			->from('test:CmsUser', 'u')
			->from('test:CmsGroup', 'g')
			->join('u.articles', 'a')
				->on('u.id = a.author_id');

		Assert::match('SELECT u, g FROM test:CmsUser u, test:CmsGroup g INNER JOIN u.articles a ON u.id = a.author_id', $qb->getDQL());
	}



	public function testMultipleFromWithMultipleJoin()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u', 'g')
			->from('test:CmsUser', 'u')
			->from('test:CmsArticle', 'a')
			->join('u.groups', 'g')
			->leftJoin('u.address', 'ad')
			->join('a.comments', 'c');

		Assert::match('SELECT u, g FROM test:CmsUser u, test:CmsArticle a INNER JOIN u.groups g LEFT JOIN u.address ad INNER JOIN a.comments c', $qb->getDQL());
	}



	public function testWhere()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid');

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid', $qb->getDQL());
	}



	public function testComplexAndWhere()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid OR u.id = :uid2 OR u.id = :uid3')
			->where('u.name = :name');

		Assert::match('SELECT u FROM test:CmsUser u WHERE (u.id = :uid OR u.id = :uid2 OR u.id = :uid3) AND u.name = :name', $qb->getDQL());
	}



	public function testAndWhere()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->where('u.id = :uid2');

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid AND u.id = :uid2', $qb->getDQL());
	}



	public function testOrWhere()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->orWhere('u.id = :uid2');

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid OR u.id = :uid2', $qb->getDQL());
	}



	public function testComplexAndWhereOrWhereNesting()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->orWhere('u.id = :uid2')
			->where('u.id = :uid3')
			->orWhere('u.name = :name1')
			->orWhere('u.name = :name2')
			->where('u.name <> :noname');

		Assert::match('SELECT u FROM test:CmsUser u WHERE (((u.id = :uid OR u.id = :uid2) AND u.id = :uid3) OR u.name = :name1 OR u.name = :name2) AND u.name <> :noname', $qb->getDQL());
	}



	public function testAndWhereIn()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->where($qb->expr()->in('u.id', array(1, 2, 3)));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid AND u.id IN(1, 2, 3)', $qb->getDQL());
	}



	public function testOrWhereIn()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->orWhere($qb->expr()->in('u.id', array(1, 2, 3)));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid OR u.id IN(1, 2, 3)', $qb->getDQL());
	}



	public function testAndWhereNotIn()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->where($qb->expr()->notIn('u.id', array(1, 2, 3)));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid AND u.id NOT IN(1, 2, 3)', $qb->getDQL());
	}



	public function testOrWhereNotIn()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->orWhere($qb->expr()->notIn('u.id', array(1, 2, 3)));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid OR u.id NOT IN(1, 2, 3)', $qb->getDQL());
	}



	public function testGroupBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->group('u.id, u.username');

		Assert::match('SELECT u FROM test:CmsUser u GROUP BY u.id, u.username', $qb->getDQL());
	}



	public function testHaving()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->group('u.id', 'COUNT(u.id) > 1');

		Assert::match('SELECT u FROM test:CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1', $qb->getDQL());
	}



	public function testAndHaving()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->group('u.id', 'COUNT(u.id) > 1 AND COUNT(u.id) < 1');

		Assert::match('SELECT u FROM test:CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1 AND COUNT(u.id) < 1', $qb->getDQL());
	}



	public function testOrHaving()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->group('u.id', '(COUNT(u.id) > 1 AND COUNT(u.id) < 1) OR COUNT(u.id) > 1');

		Assert::match('SELECT u FROM test:CmsUser u GROUP BY u.id HAVING (COUNT(u.id) > 1 AND COUNT(u.id) < 1) OR COUNT(u.id) > 1', $qb->getDQL());
	}



	public function testOrderBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->order('u.username ASC');

		Assert::match('SELECT u FROM test:CmsUser u ORDER BY u.username ASC', $qb->getDQL());
	}



	public function testOrderByWithExpression()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->order($qb->expr()->asc('u.username'));

		Assert::match('SELECT u FROM test:CmsUser u ORDER BY u.username ASC', $qb->getDQL());
	}



	public function testAddOrderBy()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->order('u.username ASC', 'u.username DESC');

		Assert::match('SELECT u FROM test:CmsUser u ORDER BY u.username ASC, u.username DESC', $qb->getDQL());
	}



	public function testGetQuery()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u');
		$q = $qb->createQuery();

		Assert::equal('Doctrine\ORM\Query', get_class($q));
	}



	public function testSetParameter()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :id')
			->setParameter('id', 1);

		$parameter = new Parameter('id', 1, Doctrine\ORM\Query\ParameterTypeInferer::inferType(1));

		Assert::equal($parameter, $qb->getParameter('id'));
	}



	public function testSetParameters()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($qb->expr()->orx('u.username = :username', 'u.username = :username2'));

		$parameters = new ArrayCollection();
		$parameters[':username'] = new Parameter('username', 'jwage');
		$parameters[':username2'] = new Parameter('username2', 'jonwage');

		$qb->setParameters($parameters);

		Assert::equal($parameters, $qb->createQuery()->getParameters());
	}



	public function testGetParameters()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :id');

		$parameters = new ArrayCollection();
		$parameters[':id'] = new Parameter('id', 1);

		$qb->setParameters($parameters);

		Assert::equal($parameters, $qb->getParameters());
	}



	public function testGetParameter()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :id');

		$parameters = new ArrayCollection();
		$parameters[':id'] = new Parameter('id', 1);

		$qb->setParameters($parameters);

		Assert::equal($parameters->first(), $qb->getParameter('id'));
	}



	public function testMultipleWhere()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->where('u.id = :uid2');

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid AND u.id = :uid2', $qb->getDQL());
	}



	public function testMultipleAndWhere()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.id = :uid')
			->where('u.id = :uid2');

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid AND u.id = :uid2', $qb->getDQL());
	}



	public function testMultipleOrWhere()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->orWhere('u.id = :uid')
			->orWhere($qb->expr()->eq('u.id', ':uid2'));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id = :uid OR u.id = :uid2', $qb->getDQL());
	}



	public function testComplexWhere()
	{
		$qb = $this->em->createQueryBuilder();

		$orExpr = $qb->expr()->orX();
		$orExpr->add($qb->expr()->eq('u.id', ':uid3'));
		$orExpr->add($qb->expr()->in('u.id', array(1)));

		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($orExpr);

		Assert::match('SELECT u FROM test:CmsUser u WHERE (u.id = :uid3 OR u.id IN(1))', $qb->getDQL());
	}



	public function testWhereInWithStringLiterals()
	{
		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($qb->expr()->in('u.name', array('one', 'two', 'three')));

		Assert::match("SELECT u FROM test:CmsUser u WHERE u.name IN('one', 'two', 'three')", $qb->getDQL());

		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($qb->expr()->in('u.name', array("O'Reilly", "O'Neil", 'Smith')));

		Assert::match("SELECT u FROM test:CmsUser u WHERE u.name IN('O''Reilly', 'O''Neil', 'Smith')", $qb->getDQL());
	}



	public function testWhereInWithObjectLiterals()
	{
		$qb = $this->em->createQueryBuilder();
		$expr = $this->em->getExpressionBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($expr->in('u.name', array($expr->literal('one'), $expr->literal('two'), $expr->literal('three'))));

		Assert::match("SELECT u FROM test:CmsUser u WHERE u.name IN('one', 'two', 'three')", $qb->getDQL());

		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($expr->in('u.name', array($expr->literal("O'Reilly"), $expr->literal("O'Neil"), $expr->literal('Smith'))));

		Assert::match("SELECT u FROM test:CmsUser u WHERE u.name IN('O''Reilly', 'O''Neil', 'Smith')", $qb->getDQL());
	}



	public function testNegation()
	{
		$expr = $this->em->getExpressionBuilder();
		$orExpr = $expr->orX();
		$orExpr->add($expr->eq('u.id', ':uid3'));
		$orExpr->add($expr->not($expr->in('u.id', array(1))));

		$qb = $this->em->createQueryBuilder();
		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($orExpr);

		Assert::match('SELECT u FROM test:CmsUser u WHERE (u.id = :uid3 OR NOT(u.id IN(1)))', $qb->getDQL());
	}



	public function testSomeAllAny()
	{
		$qb = $this->em->createQueryBuilder();
		$expr = $this->em->getExpressionBuilder();

		$qb->select('u')
			->from('test:CmsUser', 'u')
			->where($expr->gt('u.id', $expr->all('SELECT a.id FROM test:CmsArticle a')));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.id > ALL(SELECT a.id FROM test:CmsArticle a)', $qb->getDQL());

	}



	public function testMultipleIsolatedQueryConstruction()
	{
		$qb = $this->em->createQueryBuilder();
		$expr = $this->em->getExpressionBuilder();

		$qb->select('u')->from('test:CmsUser', 'u');
		$qb->where($expr->eq('u.name', ':name'));
		$qb->setParameter('name', 'romanb');

		$q1 = $qb->createQuery();

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.name = :name', $q1->getDql());
		Assert::equal(1, count($q1->getParameters()));

		// add another condition and construct a second query
		$qb->where($expr->eq('u.id', ':id'));
		$qb->setParameter('id', 42);

		$q2 = $qb->createQuery();

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.name = :name AND u.id = :id', $q2->getDql());
		Assert::true($q1 !== $q2); // two different, independent queries
		Assert::equal(2, count($q2->getParameters()));
		Assert::equal(1, count($q1->getParameters())); // $q1 unaffected
	}



	public function testGetEntityManager()
	{
		$qb = $this->em->createQueryBuilder();
		Assert::same($this->em, $qb->getEntityManager());
	}



	public function testSelectWithFuncExpression()
	{
		$qb = $this->em->createQueryBuilder();
		$expr = $qb->expr();
		$qb->select($expr->count('e.id'));

		Assert::match('SELECT COUNT(e.id)', $qb->getDQL());
	}



	/**
	 * @group DDC-867
	 */
	public function testDeepClone()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where('u.username = ?1')
			->where('u.status = ?2');

		$qb2 = clone $qb;
		$qb2->where('u.name = ?3');

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.username = ?1 AND u.status = ?2', $qb->getDql());
		Assert::match('SELECT u FROM test:CmsUser u WHERE u.username = ?1 AND u.status = ?2 AND u.name = ?3', $qb2->getDql());
	}



	/**
	 * @group DDC-1933
	 */
	public function testParametersAreCloned()
	{
		$originalQb = new Query($this->em);
		$originalQb->setParameter('parameter1', 'value1');

		$copy = clone $originalQb;
		$copy->setParameter('parameter2', 'value2');

		Assert::same(1, count($originalQb->getParameters()));
		Assert::same('value1', $copy->getParameter('parameter1')->getValue());
		Assert::same('value2', $copy->getParameter('parameter2')->getValue());
	}



	public function testGetRootAlias()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u');

		Assert::equal('u', $qb->getRootAlias());
	}



	public function testGetRootAliases()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u');

		Assert::equal(array('u'), $qb->getRootAliases());
	}



	public function testGetRootEntities()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u');

		Assert::equal(array('u' => 'test:CmsUser'), $qb->getRootEntities());
	}



	public function testGetSeveralRootAliases()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->from('test:CmsUser', 'u2');

		Assert::equal(array('u', 'u2'), $qb->getRootAliases());
		Assert::equal('u', $qb->getRootAlias());
	}



	public function testBCAddJoinWithoutRootAlias()
	{
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->join('u.groups', 'g');

		Assert::match('SELECT u FROM test:CmsUser u INNER JOIN u.groups g', $qb->getDQL());
	}



	/**
	 * @group DDC-1211
	 */
	public function testEmptyStringLiteral()
	{
		$expr = $this->em->getExpressionBuilder();
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where($expr->eq('u.username', $expr->literal("")));

		Assert::match("SELECT u FROM test:CmsUser u WHERE u.username = ''", $qb->getDQL());
	}



	/**
	 * @group DDC-1211
	 */
	public function testEmptyNumericLiteral()
	{
		$expr = $this->em->getExpressionBuilder();
		$qb = $this->em->createQueryBuilder()
			->select('u')
			->from('test:CmsUser', 'u')
			->where($expr->eq('u.username', $expr->literal(0)));

		Assert::match('SELECT u FROM test:CmsUser u WHERE u.username = 0', $qb->getDQL());
	}



	/**
	 * @group DDC-1619
	 */
	public function testAddDistinct()
	{
		$qb = $this->em->createQueryBuilder()
			->select('DISTINCT u')
			->from('test:CmsUser', 'u');

		Assert::match('SELECT DISTINCT u FROM test:CmsUser u', $qb->getDQL());
	}

}

\run(new QueryBuilderTest());
