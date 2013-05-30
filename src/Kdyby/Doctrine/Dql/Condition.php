<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Dql;

use Doctrine;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Condition extends Nette\Object
{

	const COND_AND = 'Doctrine\ORM\Query\Expr\Andx';
	const COND_OR = 'Doctrine\ORM\Query\Expr\Orx';

	/**
	 * @var array
	 */
	public $conds = array();

	/**
	 * @var array
	 */
	public $params = array();

	/**
	 * @var string
	 */
	public $last;

	/**
	 * @var string
	 */
	public $rootAlias;



	/**
	 * @param string $cond
	 */
	public function addAnd($cond)
	{
		if ($this->last === self::COND_OR) {
			$tmp = implode(' OR ', $this->conds);
			$this->conds = array(count($this->conds) > 1 ? '(' . $tmp . ')' : $tmp);
		}
		$this->last = self::COND_AND;

		if (!is_array($cond)) {
			$args = func_get_args();
			call_user_func_array(array($this, 'where'), $args);
			return;
		}

		foreach ($cond as $key => $val) { // where(array('column1' => 1, 'column2 > ?' => 2))
			if (is_int($key)) {
				$this->addAnd($val); // where('full condition')
				continue;
			}

			$this->addAnd($key, $val); // where('column', 1)
		}
	}



	/**
	 * @param string $cond
	 */
	public function addOr($cond)
	{
		if ($this->last === self::COND_AND) {
			$tmp = implode(' AND ', $this->conds);
			$this->conds = array(count($this->conds) > 1 ? '(' . $tmp . ')' : $tmp);
		}
		$this->last = self::COND_OR;

		if (!is_array($cond)) {
			$args = func_get_args();
			call_user_func_array(array($this, 'where'), $args);
			return;
		}

		foreach ($cond as $key => $val) { // where(array('column1' => 1, 'column2 > ?' => 2))
			if (is_int($key)) {
				$this->addOr($val); // where('full condition')
				continue;
			}

			$this->addOr($key, $val); // where('column', 1)
		}
	}



	/**
	 * @param $cond
	 * @param array $params
	 */
	protected function where($cond, $params = array())
	{
		if ($this->rootAlias !== NULL) {
			$cond = self::prefixWithAlias($cond, $this->rootAlias);
		}

		$args = func_get_args();
		if (count($args) !== 2 || strpbrk($cond, '?:')) { // where('column < ? OR column > ?', array(1, 2))
			if (count($args) !== 2 || !is_array($params)) { // where('column < ? OR column > ?', 1, 2)
				$params = $args;
				array_shift($params);
			}

			$this->params = array_merge($this->params, $params);

		} elseif ($params === NULL) { // where('column', NULL)
			$cond .= ' IS NULL';

		} elseif ($params instanceof Kdyby\Doctrine\DqlSelection
			|| $params instanceof Doctrine\ORM\Query) { // where('column', $qb))
			if ($params instanceof Kdyby\Doctrine\DqlSelection) {
				$params = $params->createQuery();
			}

			$cond .= " IN ({$params->getDQL()})";

		} elseif (!is_array($params)) { // where('column', 'x')
			$cond .= ' = ?';
			$this->params[] = $params;

		} else { // where('column', array(1, 2))
			if ($params) {
				$cond .= " IN (?)";
				$this->params[] = $params;

			} else {
				$cond .= " IS NULL"; // this seems wrong anyway
			}
		}

		if (strpos($cond, 'AND') !== FALSE || strpos($cond, 'OR') !== FALSE) {
			$cond = "($cond)";
		}

		$this->conds[] = $cond;
	}



	/**
	 * @param ArrayCollection $parameters
	 */
	public function build(ArrayCollection $parameters)
	{
		$cond = explode('?', $serialised = $this->__toString());
		$prefix = 'h' . substr(md5($serialised), 0, 5); // must start with letter otherwise doctrine lexer won't take it correctly

		$result = array_shift($cond);
		foreach ($this->params as $i => $value) {
			$placeholders = array();
			foreach (is_array($value) ? $value : array($value) as $l => $item) {
				$placeholders[] = ':' . ($param = $prefix . '_' . $i . '_' . $l);
				$parameters[':' . $param] = new Parameter($param, $item);
			}

			$result .= implode(', ', $placeholders) . array_shift($cond);
		}

		return $result;
	}



	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		return !$this->conds;
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return implode($this->last === self::COND_AND ? ' AND ' : ' OR ', $this->conds);
	}



	/**
	 * Try to prefix expression with alias, if not prefixed yet
	 *
	 * @param string $cond
	 * @param string $alias
	 * @return string
	 */
	public static function prefixWithAlias($cond, $alias)
	{
		$cond = Nette\Utils\Strings::replace($cond, '~(?<=[^:\w`\'"\\[\\.\\\\]|^)[a-z_][a-z0-9_\\.]+(?=[^:\w`\'")\\]\\.\\\\]|\z)~i', function ($m) use ($alias) {
			return (strpos($m[0], '.') !== FALSE || strtoupper($m[0]) === $m[0]) ? $m[0] : $alias . '.' . $m[0];
		});

		return $cond;
	}

}
