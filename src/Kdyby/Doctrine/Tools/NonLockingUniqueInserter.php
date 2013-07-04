<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Tools;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform as Platform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Kdyby;
use Kdyby\Doctrine\Connection;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NonLockingUniqueInserter extends Nette\Object
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;

	/**
	 * @var \Kdyby\Doctrine\Connection
	 */
	private $db;

	/**
	 * @var \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	private $platform;

	/**
	 * @var \Doctrine\ORM\Mapping\QuoteStrategy
	 */
	private $quotes;



	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->db = $em->getConnection();
		$this->platform = $this->db->getDatabasePlatform();
		$this->quotes = $em->getConfiguration()->getQuoteStrategy();
	}



	/**
	 * When entity have columns for required associations, this will fail.
	 * Calls $em->flush().
	 *
	 * @todo fix error codes! PDO is returning database-specific codes
	 *
	 * @param object $entity
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Exception
	 * @return bool|object
	 */
	public function persist($entity)
	{
		$this->db->beginTransaction();

		try {
			$persisted = $this->doInsert($entity);
			$this->db->commit();

			return $persisted;

		} catch (Kdyby\Doctrine\DuplicateEntryException $e) {
			$this->db->rollback();

			return FALSE;

		} catch (DBALException $e) {
			$this->db->rollback();

			if ($this->isUniqueConstraintViolation($e)) {
				return FALSE;
			}

			throw $this->db->resolveException($e);

		} catch (\Exception $e) {
			$this->db->rollback();
			throw $e;
		}
	}



	private function doInsert($entity)
	{
		// get entity metadata
		$meta = $this->em->getClassMetadata(get_class($entity));

		// fields that have to be inserted
		$fields = $this->getUniqueAndRequiredFields($meta);

		// read values to insert
		$values = $this->getInsertValues($meta, $entity, $fields);

		// prepare statement && execute
		$this->prepareInsert($meta, $values)->execute();

		// assign ID to entity
		if ($idGen = $meta->idGenerator) {
			if ($idGen->isPostInsertGenerator()) {
				$id = $idGen->generate($this->em, $entity);
				$identifierFields = $meta->getIdentifierFieldNames();
				$meta->setFieldValue($entity, reset($identifierFields), $id);
			}
		}

		// entity is now safely inserted to database, merge now
		$merged = $this->em->merge($entity);
		$this->em->flush(array($merged));

		// when you merge entity, you get a new reference
		return $merged;
	}



	private function prepareInsert(ClassMetadata $meta, array $values)
	{
		// construct sql
		$columns = array();
		foreach (array_keys($values) as $column) {
			$columns[] = $this->quotes->getColumnName($column, $meta, $this->platform);
		}

		$insertSql = 'INSERT INTO ' . $this->quotes->getTableName($meta, $this->platform)
			. ' (' . implode(', ', $columns) . ')'
			. ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';

		// create statement
		$statement = new Statement($insertSql, $this->db);

		// fetch column types
		$types = $this->getColumnsTypes($meta, array_keys($values));

		// bind values
		$paramIndex = 1;
		foreach ($values as $field => $value) {
			$statement->bindValue($paramIndex++, $value, $types[$field]);
		}

		return $statement;
	}



	/**
	 * @param \Exception|\PDOException $e
	 * @return bool
	 */
	private function isUniqueConstraintViolation(\Exception $e)
	{
		if (!$e instanceof \PDOException && !(($e = $e->getPrevious()) instanceof \PDOException)) {
			return FALSE;
		}
		/** @var \PDOException $e */

		return
			($this->platform instanceof MySqlPlatform && $e->errorInfo[1] === Connection::MYSQL_ERR_UNIQUE) ||
			($this->platform instanceof SqlitePlatform && $e->errorInfo[1] === Connection::SQLITE_ERR_UNIQUE) ||
			($this->platform instanceof PostgreSqlPlatform && $e->errorInfo[1] === Connection::POSTGRE_ERR_UNIQUE);
	}



	private function getUniqueAndRequiredFields(ClassMetadata $meta)
	{
		$fields = array();
		foreach ($meta->getFieldNames() as $fieldName) {
			$mapping = $meta->getFieldMapping($fieldName);
			if (!empty($mapping['id'])) { // not an id
				continue;
			}

			if (empty($mapping['nullable'])) { // is not nullable
				$fields[] = $fieldName;
				continue;
			}

			if (!empty($mapping['unique'])) { // is unique
				$fields[] = $fieldName;
				continue;
			}
		}

		return $fields;
	}



	private function getInsertValues(ClassMetadata $meta, $entity, array $fields)
	{
		$values = array();
		foreach ($fields as $fieldName) {
			$values[$fieldName] = $meta->getFieldValue($entity, $fieldName);
		}

		return $values;
	}



	private function getColumnsTypes(ClassMetadata $meta, array $fields)
	{
		$columnTypes = array();
		foreach ($fields as $fieldName) {
			$columnTypes[$fieldName] = Type::getType($meta->fieldMappings[$fieldName]['type']);
		}

		return $columnTypes;
	}

}
