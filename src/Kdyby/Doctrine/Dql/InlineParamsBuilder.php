<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Dql;

use Kdyby;
use Kdyby\Doctrine\Helpers;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InlineParamsBuilder extends Kdyby\Doctrine\QueryBuilder
{

	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function join($join, $alias, $conditionType = NULL, $condition = NULL, $indexBy = NULL)
	{
		return call_user_func_array([$this, 'innerJoin'], func_get_args());
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function innerJoin($join, $alias, $conditionType = NULL, $condition = NULL, $indexBy = NULL)
	{
		if ($condition !== NULL) {
			$beforeArgs = array_slice(func_get_args(), 3);
			$args = array_values(Helpers::separateParameters($this, $beforeArgs));
			if (count($beforeArgs) > count($args)) {
				$indexBy = count($args) === 2 ? $args[1] : NULL;
				$condition = $args[0];
			}
		}

		return parent::innerJoin($join, $alias, $conditionType, $condition, $indexBy);
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function leftJoin($join, $alias, $conditionType = NULL, $condition = NULL, $indexBy = NULL)
	{
		if ($condition !== NULL) {
			$beforeArgs = array_slice(func_get_args(), 3);
			$args = array_values(Helpers::separateParameters($this, $beforeArgs));
			if (count($beforeArgs) > count($args)) {
				$indexBy = count($args) === 2 ? $args[1] : NULL;
				$condition = $args[0];
			}
		}

		return parent::leftJoin($join, $alias, $conditionType, $condition, $indexBy);
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function where($predicates)
	{
		return call_user_func_array('parent::where', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function andWhere()
	{
		return call_user_func_array('parent::andWhere', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function orWhere()
	{
		return call_user_func_array('parent::orWhere', Helpers::separateParameters($this, func_get_args()));
	}

}
