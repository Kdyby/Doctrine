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
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method NativeQueryBuilder setParameter($key, $value, $type = null)
 * @method NativeQueryBuilder setParameters(array $params, array $types = [])
 * @method NativeQueryBuilder setFirstResult($firstResult)
 * @method NativeQueryBuilder setMaxResults($maxResults)
 * @method NativeQueryBuilder select($select = NULL)
 * @method NativeQueryBuilder addSelect($select = NULL)
 * @method NativeQueryBuilder delete($delete = null, $alias = null)
 * @method NativeQueryBuilder update($update = null, $alias = null)
 * @method NativeQueryBuilder groupBy($groupBy)
 * @method NativeQueryBuilder addGroupBy($groupBy)
 * @method NativeQueryBuilder having($having)
 * @method NativeQueryBuilder andHaving($having)
 * @method NativeQueryBuilder orHaving($having)
 * @method NativeQueryBuilder orderBy($sort, $order = null)
 * @method NativeQueryBuilder addOrderBy($sort, $order = null)
 */
class NativeQueryBuilder extends Doctrine\DBAL\Query\QueryBuilder
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var Mapping\ResultSetMappingBuilder
	 */
	private $rsm;

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var int
	 */
	private $defaultRenameMode = Doctrine\ORM\Query\ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT;



	public function __construct(Doctrine\ORM\EntityManager $em)
	{
		parent::__construct($em->getConnection());
		$this->em = $em;
	}



	/**
	 * @return NativeQueryWrapper
	 */
	public function getQuery()
	{
		$query = new Doctrine\ORM\NativeQuery($this->em);
		$query->setResultSetMapping($this->getResultSetMapper());
		$query->setParameters($this->getParameters());

		$wrapped = new NativeQueryWrapper($query);
		$wrapped->setFirstResult($this->getFirstResult());
		$wrapped->setMaxResults($this->getMaxResults());

		$hasSelect = (bool)$this->getQueryPart('select');
		if (!$hasSelect && $this->getType() === self::SELECT) {
			$select = $this->getResultSetMapper()->generateSelectClause();
			$this->select($select ?: '*');
		}

		$query->setSQL($this->getSQL());

		$this->setFirstResult($wrapped->getFirstResult());
		$this->setMaxResults($wrapped->getMaxResults());

		if (!$hasSelect && $this->getType() === self::SELECT) {
			$this->resetQueryPart('select');
		}

		$rsm = $this->getResultSetMapper();
		if (empty($rsm->fieldMappings) && empty($rsm->scalarMappings)) {
			throw new InvalidStateException("No field or columns mapping found, please configure the ResultSetMapper and some fields.");
		}

		return $wrapped;
	}



	/**
	 * @param int $defaultRenameMode
	 * @return NativeQueryBuilder
	 */
	public function setDefaultRenameMode($defaultRenameMode)
	{
		if ($this->rsm !== NULL) {
			throw new InvalidStateException("It's too late for changing rename mode for ResultSetMappingBuilder, it has already been created. Call this method earlier.");
		}

		$this->defaultRenameMode = $defaultRenameMode;
		return $this;
	}



	public function getResultSetMapper()
	{
		if ($this->rsm === NULL) {
			$this->rsm = new Mapping\ResultSetMappingBuilder($this->em, $this->defaultRenameMode);
		}

		return $this->rsm;
	}



	/**
	 * @param string $tableAlias
	 * @param string|array $columns
	 * @return NativeQueryBuilder
	 */
	public function addColumn($tableAlias, $columns)
	{
		$rsm = $this->getResultSetMapper();

		$args = func_get_args();
		array_shift($args); // shit tableAlias

		$class = $this->em->getClassMetadata($rsm->aliasMap[$tableAlias]);

		foreach (is_array($columns) ? $columns : $args as $column) {
			try {
				$field = $class->getFieldForColumn($column);
				if ($class->hasField($field)) {
					$type = $class->getTypeOfField($field);

				} else {
					$type = $class->hasAssociation($field) ? 'integer' : 'string';
				}

			} catch (Doctrine\ORM\Mapping\MappingException $e) {
				$type = 'string';

				if ($class->discriminatorColumn['fieldName'] === $column) {
					$type = $class->discriminatorColumn['type'];
				}
			}

			$this->addSelect("{$tableAlias}.{$column} as {$tableAlias}_{$column}");
			$rsm->addScalarResult("{$tableAlias}_{$column}", "{$tableAlias}_{$column}", $type);
		}

		return $this;
	}



	public function addSelect($select = NULL)
	{
		$selects = is_array($select) ? $select : func_get_args();
		foreach ($selects as &$arg) {
			if ($arg instanceof Doctrine\DBAL\Query\QueryBuilder) {
				$arg = '(' . $arg->getSQL() . ')';
			}
		}

		return parent::addSelect($selects);
	}



	/**
	 * {@inheritdoc}
	 */
	public function from($from, $alias = NULL)
	{
		return parent::from($this->addTableResultMapping($from, $alias), $alias);
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function join($fromAlias, $join, $alias, $condition = null)
	{
		return call_user_func_array([$this, 'innerJoin'], func_get_args());
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function innerJoin($fromAlias, $join, $alias, $condition = null)
	{
		if ($condition !== NULL) {
			list($condition) = array_values(Helpers::separateParameters($this, array_slice(func_get_args(), 3)));
		}

		return parent::innerJoin($fromAlias, $this->addTableResultMapping($join, $alias, $fromAlias), $alias, $condition);
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function leftJoin($fromAlias, $join, $alias, $condition = null)
	{
		if ($condition !== NULL) {
			list($condition) = array_values(Helpers::separateParameters($this, array_slice(func_get_args(), 3)));
		}

		return parent::leftJoin($fromAlias, $this->addTableResultMapping($join, $alias, $fromAlias), $alias, $condition);
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function rightJoin($fromAlias, $join, $alias, $condition = null)
	{
		if ($condition !== NULL) {
			list($condition) = array_values(Helpers::separateParameters($this, array_slice(func_get_args(), 3)));
		}

		return parent::leftJoin($fromAlias, $this->addTableResultMapping($join, $alias, $fromAlias), $alias, $condition);
	}



	/**
	 * @param string $table
	 * @param string|null $alias
	 * @param string $joinedFrom
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 * @return string
	 */
	protected function addTableResultMapping($table, $alias, $joinedFrom = NULL)
	{
		$rsm = $this->getResultSetMapper();
		$class = $relation = NULL;

		if (substr_count($table, '\\')) {
			$class = $this->em->getClassMetadata($table);
			$table = $class->getTableName();

		} elseif (isset($rsm->aliasMap[$joinedFrom])) {
			$fromClass = $this->em->getClassMetadata($rsm->aliasMap[$joinedFrom]);

			foreach (array_merge([$fromClass->getName()], $fromClass->subClasses) as $fromClass) {
				$fromClass = $this->em->getClassMetadata($fromClass);

				if ($fromClass->hasAssociation($table)) {
					$class = $this->em->getClassMetadata($fromClass->getAssociationTargetClass($table));
					$relation = $fromClass->getAssociationMapping($table);
					$table = $class->getTableName();
					break;

				} else {
					foreach ($fromClass->getAssociationMappings() as $mapping) {
						$targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
						if ($targetClass->getTableName() === $table) {
							$class = $targetClass;
							$relation = $mapping;
							$table = $class->getTableName();
							break 2;
						}
					}
				}
			}

		} else {
			/** @var Kdyby\Doctrine\Mapping\ClassMetadata $class */
			foreach ($this->em->getMetadataFactory()->getAllMetadata() as $class) {
				if ($class->getTableName() === $table) {
					break;
				}
			}
		}

		if (!$class instanceof Doctrine\ORM\Mapping\ClassMetadata || $class->getTableName() !== $table) {
			return $table;
		}

		if ($alias === NULL && ($joinedFrom === NULL || $relation !== NULL)) {
			throw new InvalidArgumentException('Parameter alias is required');
		}

		if ($joinedFrom === NULL) {
			$rsm->addEntityResult($class->getName(), $alias);

		} elseif ($relation !== NULL) {
			$rsm->addJoinedEntityResult($class->getName(), $alias, $joinedFrom, $relation);
		}

		return $class->getTableName();
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function where($predicates)
	{
		return call_user_func_array('parent::where', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function andWhere($where)
	{
		return call_user_func_array('parent::andWhere', Helpers::separateParameters($this, func_get_args()));
	}



	/**
	 * {@inheritdoc}
	 * @return NativeQueryBuilder
	 */
	public function orWhere($where)
	{
		return call_user_func_array('parent::orWhere', Helpers::separateParameters($this, func_get_args()));
	}

}
