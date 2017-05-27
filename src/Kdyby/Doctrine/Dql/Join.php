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
use Kdyby\Doctrine\DqlSelection;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\Dql\Join and($predicates)
 * @method \Kdyby\Doctrine\Dql\Join or($predicates)
 * @method \Kdyby\Doctrine\Dql\Join with($predicates)
 * @method \Kdyby\Doctrine\Dql\Join on($predicates)
 *
 * @method \Kdyby\Doctrine\DqlSelection join($join, $alias, $indexBy = NULL)
 * @method \Kdyby\Doctrine\DqlSelection leftJoin($join, $alias, $indexBy = NULL)
 * @method \Kdyby\Doctrine\DqlSelection where($predicates)
 * @method \Kdyby\Doctrine\DqlSelection group($columns, $having = NULL)
 * @method \Kdyby\Doctrine\DqlSelection order($by)
 * @method \Kdyby\Doctrine\DqlSelection limit($limit, $offset = NULL)
 * @method \Doctrine\ORM\Query createQuery()
 * @method string getDQL()
 */
class Join extends Expr\Join
{

	/**
	 * @var DqlSelection
	 */
	private $query;

	/**
	 * @var DqlBuilder
	 */
	private $builder;

	/**
	 * @var string|NULL|\Kdyby\Doctrine\Dql\Condition
	 */
	protected $condition;

	/**
	 * @var string|NULL
	 */
	protected $conditionType;



	/**
	 * @param DqlSelection $query
	 * @param DqlBuilder $builder
	 */
	public function injectQuery(DqlSelection $query, DqlBuilder $builder)
	{
		$this->query = $query;
		$this->builder = $builder;
	}



	/**
	 * @param string $name
	 * @param array $arguments
	 * @return Join|DqlSelection
	 */
	public function __call($name, $arguments)
	{
		if (in_array($name = strtolower($name), ['with', 'on'])) {
			$this->conditionType = $name === 'with' ? self::WITH : self::ON;
			$name = 'and';
		}

		if (method_exists(\Kdyby\Doctrine\Dql\Condition::class, $method = 'add' . ucfirst($name))) {
			if ($this->condition === NULL) {
				$this->condition = new Condition();
			}

			if ($this->conditionType === NULL) {
				$this->conditionType = self::ON;
			}

			call_user_func_array([$this->condition, $method], $arguments);

			return $this;
		}

		return call_user_func_array([$this->query, $name], $arguments);
	}

}
