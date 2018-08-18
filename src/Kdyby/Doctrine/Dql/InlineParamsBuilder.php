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
		$args = func_get_args();
		$this->innerJoin(...$args);
		return $this;
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

		parent::innerJoin($join, $alias, $conditionType, $condition, $indexBy);
		return $this;
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

		parent::leftJoin($join, $alias, $conditionType, $condition, $indexBy);
		return $this;
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function where($predicates)
	{
		$args = Helpers::separateParameters($this, func_get_args());
		parent::where(...$args);
		return $this;
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function andWhere()
	{
		$args = Helpers::separateParameters($this, func_get_args());
		parent::andWhere(...$args);
		return $this;
	}



	/**
	 * {@inheritdoc}
	 * @return InlineParamsBuilder
	 */
	public function orWhere()
	{
		$args = Helpers::separateParameters($this, func_get_args());
		parent::orWhere(...$args);
		return $this;
	}

}
