<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Dql;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Kdyby\Doctrine\EntityManager;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DqlBuilder extends Nette\Object
{

	/**
	 * @var array
	 */
	public $select = array();

	/**
	 * @var array
	 */
	public $from = array();

	/**
	 * @var array
	 */
	public $join = array();

	/**
	 * @var array
	 */
	public $set = array();

	/**
	 * @var Condition
	 */
	public $where;

	/**
	 * @var string
	 */
	public $groupBy;

	/**
	 * @var Condition
	 */
	public $having;

	/**
	 * @var array
	 */
	public $orderBy = array();

	/**
	 * The query parameters.
	 * @var ArrayCollection
	 */
	public $parameters;

	/**
	 * The EntityManager used by this QueryBuilder.
	 * @var EntityManager
	 */
	private $em;



	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->parameters = new ArrayCollection();
		$this->where = new Condition();
		$this->having = new Condition();
	}



	/**
	 * @return string
	 */
	public function buildDeleteDQL()
	{
		return 'DELETE ' . implode(', ', $this->from)
			. ' ' . implode(' ', $this->join)
			. $this->buildConditions();
	}



	/**
	 * @return string
	 */
	public function buildUpdateDQL()
	{
		$set = array();
		foreach ($this->set as $alias => $values) {
			foreach ($values as $column => $value) {
				$this->parameters[$param = $alias . '_' . $column] = $value;
				$set[] = new Expr\Comparison($alias . '.' . $column, Expr\Comparison::EQ, ':' . $param);
			}
		}

		return 'UPDATE ' . implode(', ', $this->from) . ' '
			. 'SET ' . implode(', ', $set)
			. ' ' . implode(' ', $this->join)
			. $this->buildConditions();
	}



	/**
	 * @return string
	 */
	public function buildSelectDQL()
	{
		$cols = implode(', ', $this->select);

		return "SELECT $cols"
			. ($this->from ? " FROM " : '') . implode(', ', $this->from)
			. ($this->join ? ' ' : '') . implode(' ', $this->join)
			. $this->buildConditions();
	}



	/**
	 * @return string
	 */
	protected function buildConditions()
	{
		$return = '';
		if (!$this->where->isEmpty()) {
			$return .= ' WHERE ' . $this->where->build($this->parameters);
		}
		if ($this->groupBy) {
			$return .= ' GROUP BY ' . Condition::prefixWithAlias($this->groupBy, $this->where->rootAlias);
		}
		if (!$this->having->isEmpty()) {
			$return .= ' HAVING ' . $this->having->build($this->parameters);
		}
		if ($this->orderBy) {
			$return .= ' ORDER BY ' . Condition::prefixWithAlias(implode(', ', $this->orderBy), $this->where->rootAlias);
		}

		return $return;
	}



	/**
	 * Deep clone of all expression objects in the DQL parts.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->where = clone $this->where;
		$this->having = clone $this->having;

		$parameters = new ArrayCollection();
		foreach ($this->parameters as $key => $parameter) {
			$parameters[$key] = clone $parameter;
		}
		$this->parameters = $parameters;
	}



	/**
	 * @param string $rootAlias
	 */
	public function refreshAliases($rootAlias)
	{
		$this->where->rootAlias = $rootAlias;
		$this->having->rootAlias = $rootAlias;
	}

}
