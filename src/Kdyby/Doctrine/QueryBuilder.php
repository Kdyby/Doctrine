<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine;
use Doctrine\ORM\Query\Expr;
use Kdyby;
use Nette;
use Nette\Utils\ObjectMixin;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method QueryBuilder select($select = null)
 * @method QueryBuilder addSelect($select = null)
 * @method QueryBuilder from($from, $alias, $indexBy = null)
 * @method QueryBuilder setMaxResults($maxResults)
 * @method QueryBuilder setFirstResult($maxResults)
 * @method QueryBuilder resetDQLPart($parts = null)
 */
class QueryBuilder extends Doctrine\ORM\QueryBuilder implements \IteratorAggregate
{

	/**
	 * @var array
	 */
	private $criteriaJoins = array();



	/**
	 * {@inheritdoc}
	 * @return QueryBuilder
	 */
	public function join($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
	{
		return call_user_func_array(array($this, 'innerJoin'), func_get_args());
	}



	/**
	 * {@inheritdoc}
	 * @return QueryBuilder
	 */
	public function innerJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
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
	 * @return QueryBuilder
	 */
	public function leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
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
	 * @return QueryBuilder
	 */
	public function where($predicates)
	{
		return call_user_func_array('parent::where', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * {@inheritdoc}
	 * @return QueryBuilder
	 */
	public function andWhere($where)
	{
		return call_user_func_array('parent::andWhere', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * {@inheritdoc}
	 * @return QueryBuilder
	 */
	public function orWhere($where)
	{
		return call_user_func_array('parent::orWhere', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * @param array $criteria
	 * @return QueryBuilder
	 */
	public function whereCriteria(array $criteria)
	{
		foreach ($criteria as $key => $val) {
			$alias = $this->autoJoin($key);

			$operator = '=';
			if (preg_match('~(?P<key>[^\\s]+)\\s+(?P<operator>.+)\\s*~', $key, $m)) {
				$key = $m['key'];
				$operator = strtr(strtolower($m['operator']), array(
					'neq' => '!=',
					'eq' => '=',
					'lt' => '<',
					'lte' => '<=',
					'gt' => '>',
					'gte' => '>=',
				));
			}

			$not = substr($operator, 0, 1) === '!';
			if (substr($operator, 0, 3) === 'not') {
				$operator = substr($operator, 4);
				$not = TRUE;
			}

			$paramName = 'param_' . (count($this->getParameters()) + 1);

			if (is_array($val)) {
				$this->andWhere("$alias.$key " . ($not ? 'NOT ' : '') . "IN (:$paramName)");
				$this->setParameter($paramName, $val, is_integer(reset($val)) ? Connection::PARAM_INT_ARRAY : Connection::PARAM_STR_ARRAY);

			} elseif ($val === NULL) {
				$this->andWhere("$alias.$key IS " . ($not ? 'NOT ' : '') . 'NULL');

			} else {
				$this->andWhere(sprintf('%s.%s %s :%s', $alias, $key, strtoupper($operator), $paramName));
				$this->setParameter($paramName, $val);
			}
		}

		return $this;
	}



	/**
	 * @internal
	 * @param string $sort
	 * @param string $order
	 * @return Doctrine\ORM\QueryBuilder
	 */
	public function autoJoinOrderBy($sort, $order = NULL)
	{
		if (is_array($sort)) {
			foreach (func_get_arg(0) as $sort => $order) {
				if (!is_string($sort)) {
					$sort = $order;
					$order = NULL;
				}
				$this->autoJoinOrderBy($sort, $order);
			}

			return $this;
		}

		if (is_string($sort)) {
			$alias = $this->autoJoin($sort);
			$sort = $alias . '.' . $sort;
		}

		return $this->addOrderBy($sort, $order);
	}



	/**
	 * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\Traversable
	 */
	public function getIterator()
	{
		return $this->getQuery()->iterate();
	}



	private function autoJoin(&$key)
	{
		$rootAliases = $this->getRootAliases();
		$alias = reset($rootAliases);

		if (($i = strpos($key, '.')) === FALSE || !in_array(substr($key, 0, $i), $rootAliases)) {
			// there is no root alias to join from, assume first root alias
			$key = $alias . '.' . $key;
		}

		while (preg_match('~([^\\.]+)\\.(.+)~', $key, $m)) {
			$key = $m[2];
			$property = $m[1];

			if (in_array($property, $rootAliases)) {
				$alias = $property;
				continue;
			}

			if (isset($this->criteriaJoins[$alias][$property])) {
				$alias = $this->criteriaJoins[$alias][$property];
				continue;
			}

			$aliasLength = 1;
			do {
				$joinAs = substr($property, 0, $aliasLength++);
			} while (isset($this->criteriaJoins[$joinAs]));
			$this->criteriaJoins[$joinAs] = array();

			$this->innerJoin("$alias.$property", $joinAs);
			$this->criteriaJoins[$alias][$property] = $joinAs;
			$alias = $joinAs;
		}

		return $alias;
	}



	/*************************** Nette\Object ***************************/



	/**
	 * Access to reflection.
	 * @return \Nette\Reflection\ClassType
	 */
	public static function getReflection()
	{
		return new Nette\Reflection\ClassType(get_called_class());
	}



	/**
	 * Call to undefined method.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return ObjectMixin::call($this, $name, $args);
	}



	/**
	 * Call to undefined static method.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		return ObjectMixin::callStatic(get_called_class(), $name, $args);
	}



	/**
	 * Adding method to class.
	 *
	 * @param $name
	 * @param null $callback
	 *
	 * @throws \Nette\MemberAccessException
	 * @return callable|null
	 */
	public static function extensionMethod($name, $callback = NULL)
	{
		if (strpos($name, '::') === FALSE) {
			$class = get_called_class();
		} else {
			list($class, $name) = explode('::', $name);
		}
		if ($callback === NULL) {
			return ObjectMixin::getExtensionMethod($class, $name);
		} else {
			ObjectMixin::setExtensionMethod($class, $name, $callback);
		}
	}



	/**
	 * Returns property value. Do not call directly.
	 *
	 * @param string $name
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		return ObjectMixin::get($this, $name);
	}



	/**
	 * Sets value of a property. Do not call directly.
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws \Nette\MemberAccessException
	 * @return void
	 */
	public function __set($name, $value)
	{
		ObjectMixin::set($this, $name, $value);
	}



	/**
	 * Is property defined?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return ObjectMixin::has($this, $name);
	}



	/**
	 * Access to undeclared property.
	 *
	 * @param string $name
	 *
	 * @throws \Nette\MemberAccessException
	 * @return void
	 */
	public function __unset($name)
	{
		ObjectMixin::remove($this, $name);
	}

}
