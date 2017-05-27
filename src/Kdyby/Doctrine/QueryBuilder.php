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



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\QueryBuilder select(array|string $select = null)
 * @method \Kdyby\Doctrine\QueryBuilder addSelect(array|string $select = null)
 * @method \Kdyby\Doctrine\QueryBuilder from($from, $alias, $indexBy = null)
 * @method \Kdyby\Doctrine\QueryBuilder setMaxResults(int|NULL $maxResults)
 * @method \Kdyby\Doctrine\QueryBuilder setFirstResult(int|NULL $maxResults)
 * @method \Kdyby\Doctrine\QueryBuilder resetDQLPart(string $part)
 */
class QueryBuilder extends Doctrine\ORM\QueryBuilder implements \IteratorAggregate
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var array
	 */
	private $criteriaJoins = [];



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
				$operator = strtr(strtolower($m['operator']), [
					'neq' => '!=',
					'eq' => '=',
					'lt' => '<',
					'lte' => '<=',
					'gt' => '>',
					'gte' => '>=',
				]);
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
	 * @param string|array $sort
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
			$reg = '~[^()]+(?=\))~';
			if (preg_match($reg, $sort, $matches)) {
				$sortMix = $sort;
				$sort = $matches[0];
				$alias = $this->autoJoin($sort, 'leftJoin');
				$hiddenAlias = $alias . $sort . count($this->getDQLPart('orderBy'));

				$this->addSelect(preg_replace($reg, $alias . '.' . $sort, $sortMix) . ' as HIDDEN ' . $hiddenAlias);
				$rootAliases = $this->getRootAliases();
				$this->addGroupBy(reset($rootAliases) . '.id');
				$sort = $hiddenAlias;

			} else {
				$alias = $this->autoJoin($sort);
				$sort = $alias . '.' . $sort;
			}
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



	private function autoJoin(&$key, $methodJoin = "innerJoin")
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

			$j = 0;
			do {
				$joinAs = substr($property, 0, 1) . (string) $j++;
			} while (isset($this->criteriaJoins[$joinAs]));
			$this->criteriaJoins[$joinAs] = [];

			$this->{$methodJoin}("$alias.$property", $joinAs);
			$this->criteriaJoins[$alias][$property] = $joinAs;
			$alias = $joinAs;
		}

		return $alias;
	}

}
