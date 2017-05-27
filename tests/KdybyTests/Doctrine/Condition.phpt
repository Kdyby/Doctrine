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
		return [
			['id = ?', [1], ["id", 1]],
			['id IS NULL', [], ["id", NULL]],
			['(column < ? OR column > ?)', [1, 2], ['column < ? OR column > ?', [1, 2]]],
			['(column < ? OR column > ?)', [1, 2], ['column < ? OR column > ?', 1, 2]],
			['column IN (?)', [[1, 2]], ['column', [1, 2]]],
			['column IS NULL', [], ['column', []]],
		];
	}



	/**
	 * @dataProvider dataBasic
	 */
	public function testSimpleAnd($expectedCond, $expectedParams, $arguments)
	{
		$condition = new Condition();
		call_user_func_array([$condition, 'addAnd'], $arguments);

		Assert::same($expectedCond, (string) $condition);
		Assert::same($expectedParams, $condition->params);
	}



	/**
	 * @return array
	 */
	public function dataBasic_withRootAlias()
	{
		return [
			['e.id = ?', [1], ["id", 1]],
			['e.id IS NULL', [], ["id", NULL]],
			['(e.column < ? OR e.column > ?)', [1, 2], ['e.column < ? OR column > ?', [1, 2]]],
			['(e.column < ? OR e.column > ?)', [1, 2], ['column < ? OR column > ?', 1, 2]],
			['e.column IN (?)', [[1, 2]], ['column', [1, 2]]],
			['e.column IS NULL', [], ['column', []]],
		];
	}



	/**
	 * @dataProvider dataBasic_withRootAlias
	 */
	public function testSimpleAnd_withRootAlias($expectedCond, $expectedParams, $arguments)
	{
		$condition = new Condition();
		$condition->rootAlias = 'e';
		call_user_func_array([$condition, 'addAnd'], $arguments);

		Assert::same($expectedCond, (string) $condition);
		Assert::same($expectedParams, $condition->params);
	}



	public function testBuild_InExpression()
	{
		$condition = new Condition();
		$condition->addAnd('column', [10, 20, 30]);

		$params = new ArrayCollection();
		Assert::same("column IN (:h9c25e_0_0, :h9c25e_0_1, :h9c25e_0_2)", $condition->build($params));
		Assert::equal([
			':h9c25e_0_0' => new Parameter('h9c25e_0_0', 10),
			':h9c25e_0_1' => new Parameter('h9c25e_0_1', 20),
			':h9c25e_0_2' => new Parameter('h9c25e_0_2', 30),
		], $params->toArray());

		$condition = new Condition();
		$condition->addAnd('column', [10, 20]);

		$params = new ArrayCollection();
		Assert::same("column IN (:h9c25e_0_0, :h9c25e_0_1)", $condition->build($params));
		Assert::equal([
			':h9c25e_0_0' => new Parameter('h9c25e_0_0', 10),
			':h9c25e_0_1' => new Parameter('h9c25e_0_1', 20),
		], $params->toArray());

		$condition = new Condition();
		$condition->addAnd('column', [10]);

		$params = new ArrayCollection();
		Assert::same("column IN (:h9c25e_0_0)", $condition->build($params));
		Assert::equal([
			':h9c25e_0_0' => new Parameter('h9c25e_0_0', 10),
		], $params->toArray());
	}



	public function testBuild_Equals()
	{
		$condition = new Condition();
		$condition->addAnd('column', 10);

		$params = new ArrayCollection();
		Assert::same("column = :h126fc_0_0", $condition->build($params));
		Assert::equal([
			':h126fc_0_0' => new Parameter('h126fc_0_0', 10),
		], $params->toArray());
	}

}

(new ConditionTest())->run();
