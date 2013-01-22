<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Dql;

use Doctrine\ORM\Query\Expr;
use Kdyby\Doctrine\Query;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\Dql\Join and($predicates)
 * @method \Kdyby\Doctrine\Dql\Join or($predicates)
 * @method \Kdyby\Doctrine\Dql\Join with($predicates)
 * @method \Kdyby\Doctrine\Dql\Join on($predicates)
 *
 * @method \Kdyby\Doctrine\Query join($join, $alias, $indexBy = NULL)
 * @method \Kdyby\Doctrine\Query leftJoin($join, $alias, $indexBy = NULL)
 * @method \Kdyby\Doctrine\Query where($predicates)
 * @method \Kdyby\Doctrine\Query group($columns, $having = NULL)
 * @method \Kdyby\Doctrine\Query order($by)
 * @method \Kdyby\Doctrine\Query limit($limit, $offset = NULL)
 * @method \Doctrine\ORM\Query createQuery()
 * @method string getDQL()
 */
class Join extends Expr\Join
{

	/**
	 * @var Query
	 */
	private $query;

	/**
	 * @var DqlBuilder
	 */
	private $builder;



	/**
	 * @param Query $query
	 * @param DqlBuilder $builder
	 */
	public function injectQuery(Query $query, DqlBuilder $builder)
	{
		$this->query = $query;
		$this->builder = $builder;
	}



	/**
	 * @param string $name
	 * @param array $arguments
	 * @return Join|Query
	 */
	public function __call($name, $arguments)
	{
		if (in_array($name = strtolower($name), array('with', 'on'))) {
			$this->conditionType = $name === 'with' ? self::WITH : self::ON;
			$name = 'and';
		}

		if (method_exists('Kdyby\Doctrine\Dql\Condition', $method = 'add' . ucfirst($name))) {
			if (empty($this->condition)) {
				$this->condition = new Condition();
			}

			if (empty($this->conditionType)) {
				$this->conditionType = self::ON;
			}

			call_user_func_array(array($this->condition, $method), $arguments);

			return $this;
		}

		return call_user_func_array(array($this->query, $name), $arguments);
	}

}
