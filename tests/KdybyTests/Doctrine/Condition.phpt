<?php

/**
 * Test: Kdyby\Doctrine\Condition.
 *
 * @testCase Kdyby\Doctrine\ConditionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Kdyby;
use Kdyby\Doctrine\Dql\Condition;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ConditionTest extends Tester\TestCase
{

	/**
	 * @return array
	 */
	public function dataBasic()
	{
		return array(
			array('id = ?', array(1), array("id", 1)),
			array('id IS NULL', array(), array("id", NULL)),
			array('(column < ? OR column > ?)', array(1, 2), array('column < ? OR column > ?', array(1, 2))),
			array('(column < ? OR column > ?)', array(1, 2), array('column < ? OR column > ?', 1, 2)),
			array('column IN (?)', array(array(1, 2)), array('column', array(1, 2))),
			array('column = NULL', array(), array('column', array())),
		);
	}



	/**
	 * @dataProvider dataBasic
	 */
	public function testSimpleAnd($expectedCond, $expectedParams, $arguments)
	{
		$condition = new Condition();
		call_user_func_array(array($condition, 'addAnd'), $arguments);

		Assert::same($expectedCond, (string) $condition);
		Assert::same($expectedParams, $condition->params);
	}



	/**
	 * @return array
	 */
	public function dataBasic_withRootAlias()
	{
		return array(
			array('e.id = ?', array(1), array("id", 1)),
			array('e.id IS NULL', array(), array("id", NULL)),
			array('(e.column < ? OR e.column > ?)', array(1, 2), array('e.column < ? OR column > ?', array(1, 2))),
			array('(e.column < ? OR e.column > ?)', array(1, 2), array('column < ? OR column > ?', 1, 2)),
			array('e.column IN (?)', array(array(1, 2)), array('column', array(1, 2))),
			array('e.column = NULL', array(), array('column', array())),
		);
	}



	/**
	 * @dataProvider dataBasic_withRootAlias
	 */
	public function testSimpleAnd_withRootAlias($expectedCond, $expectedParams, $arguments)
	{
		$condition = new Condition();
		$condition->rootAlias = 'e';
		call_user_func_array(array($condition, 'addAnd'), $arguments);

		Assert::same($expectedCond, (string) $condition);
		Assert::same($expectedParams, $condition->params);
	}



	public function testBuild_InExpression()
	{
		$condition = new Condition();
		$condition->addAnd('column', array(10, 20, 30));

		$params = new ArrayCollection();
		Assert::same("column IN (:9c25e_0_0, :9c25e_0_1, :9c25e_0_2)", $condition->build($params));
		Assert::equal(array(
			':9c25e_0_0' => new Parameter('9c25e_0_0', 10),
			':9c25e_0_1' => new Parameter('9c25e_0_1', 20),
			':9c25e_0_2' => new Parameter('9c25e_0_2', 30),
		), $params->toArray());

		$condition = new Condition();
		$condition->addAnd('column', array(10, 20));

		$params = new ArrayCollection();
		Assert::same("column IN (:9c25e_0_0, :9c25e_0_1)", $condition->build($params));
		Assert::equal(array(
			':9c25e_0_0' => new Parameter('9c25e_0_0', 10),
			':9c25e_0_1' => new Parameter('9c25e_0_1', 20),
		), $params->toArray());

		$condition = new Condition();
		$condition->addAnd('column', array(10));

		$params = new ArrayCollection();
		Assert::same("column IN (:9c25e_0_0)", $condition->build($params));
		Assert::equal(array(
			':9c25e_0_0' => new Parameter('9c25e_0_0', 10),
		), $params->toArray());
	}



	public function testBuild_Equals()
	{
		$condition = new Condition();
		$condition->addAnd('column', 10);

		$params = new ArrayCollection();
		Assert::same("column = :126fc_0_0", $condition->build($params));
		Assert::equal(array(
			':126fc_0_0' => new Parameter('126fc_0_0', 10),
		), $params->toArray());
	}

}

\run(new ConditionTest());
