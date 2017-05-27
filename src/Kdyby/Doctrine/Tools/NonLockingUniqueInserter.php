<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Tools;

use Doctrine;
use Doctrine\DBAL\DBALException;
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
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class NonLockingUniqueInserter
{

	use \Kdyby\StrictObjects\Scream;

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
	 * @var \Doctrine\ORM\UnitOfWork
	 */
	private $uow;



	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		/** @var \Kdyby\Doctrine\Connection $db */
		$db = $em->getConnection();
		$this->db = $db;
		$this->platform = $db->getDatabasePlatform();
		$this->quotes = $em->getConfiguration()->getQuoteStrategy();
		$this->uow = $em->getUnitOfWork();
	}



	/**
	 * When entity have columns for required associations, this will fail.
	 * Calls $em->flush().
	 *
	 * Warning: You must NOT use the passed entity further in your application.
	 * Use the returned one instead!
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

		} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
			$this->db->rollBack();

			return FALSE;

		} catch (Kdyby\Doctrine\DuplicateEntryException $e) {
			$this->db->rollBack();

			return FALSE;

		} catch (DBALException $e) {
			$this->db->rollBack();

			if ($this->isUniqueConstraintViolation($e)) {
				return FALSE;
			}

			throw $this->db->resolveException($e);

		} catch (\Exception $e) {
			$this->db->rollBack();
			throw $e;

		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}



	private function doInsert($entity)
	{
		/** @var \Kdyby\Doctrine\Mapping\ClassMetadata $meta */
		$meta = $this->em->getClassMetadata(get_class($entity));

		// fields that have to be inserted
		$fields = $this->getFieldsWithValues($meta, $entity);
		// associations that have to be inserted
		$associations = $this->getAssociationsWithValues($meta, $entity);
		// discriminator column
		$discriminator = $this->getDiscriminatorColumn($meta);

		// prepare statement && execute
		$this->prepareInsert($meta, array_merge($fields, $associations, $discriminator))->execute();

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
		$this->em->flush([$merged]);

		// when you merge entity, you get a new reference
		return $merged;
	}



	private function prepareInsert(ClassMetadata $meta, array $data)
	{
		// construct sql
		$columns = [];
		foreach ($data as $column) {
			$columns[] = $column['quotedColumn'];
		}

		$insertSql = 'INSERT INTO ' . $this->quotes->getTableName($meta, $this->platform)
			. ' (' . implode(', ', $columns) . ')'
			. ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';

		// create statement
		$statement = new Statement($insertSql, $this->db);

		// bind values
		$paramIndex = 1;
		foreach ($data as $column) {
			$statement->bindValue($paramIndex++, $column['value'], $column['type']);
		}

		return $statement;
	}



	/**
	 * @param \Exception|\PDOException $e
	 * @return bool
	 */
	private function isUniqueConstraintViolation($e)
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



	private function getFieldsWithValues(ClassMetadata $meta, $entity)
	{
		$fields = [];
		foreach ($meta->getFieldNames() as $fieldName) {
			$mapping = $meta->getFieldMapping($fieldName);
			if (!empty($mapping['id']) && $meta->usesIdGenerator()) { // autogenerated id
				continue;
			}
			$fields[$fieldName]['value'] = $meta->getFieldValue($entity, $fieldName);
			$fields[$fieldName]['quotedColumn'] = $this->quotes->getColumnName($fieldName, $meta, $this->platform);
			$fields[$fieldName]['type'] = Type::getType($mapping['type']);
		}

		return $fields;
	}



	private function getAssociationsWithValues(ClassMetadata $meta, $entity)
	{
		$associations = [];
		foreach ($meta->getAssociationNames() as $associationName) {
			$mapping = $meta->getAssociationMapping($associationName);
			if (!empty($mapping['id']) && $meta->usesIdGenerator()) { // autogenerated id
				continue;
			}
			if (!($mapping['type'] & ClassMetadata::TO_ONE)) { // is not to one relation
				continue;
			}
			if (empty($mapping['isOwningSide'])) { // is not owning side
				continue;
			}

			foreach ($mapping['joinColumns'] as $joinColumn) {
				$targetColumn = $joinColumn['referencedColumnName'];
				$targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
				$newVal = $meta->getFieldValue($entity, $associationName);
				$newValId = $newVal !== NULL ? $this->uow->getEntityIdentifier($newVal) : [];

				switch (TRUE) {
					case $newVal === NULL:
						$value = NULL;
						break;

					case $targetClass->containsForeignIdentifier:
						$value = $newValId[$targetClass->getFieldForColumn($targetColumn)];
						break;

					default:
						$value = $newValId[$targetClass->fieldNames[$targetColumn]];
						break;
				}

				$sourceColumn = $joinColumn['name'];
				$quotedColumn = $this->quotes->getJoinColumnName($joinColumn, $meta, $this->platform);
				$associations[$sourceColumn]['value'] = $value;
				$associations[$sourceColumn]['quotedColumn'] = $quotedColumn;
				$associations[$sourceColumn]['type'] = $targetClass->getTypeOfColumn($targetColumn);
			}
		}

		return $associations;
	}



	private function getDiscriminatorColumn(ClassMetadata $meta)
	{
		if (!$meta->isInheritanceTypeSingleTable()) {
			return [];
		}

		$column = $meta->discriminatorColumn;

		return [
			$column['fieldName'] => [
				'value' => $meta->discriminatorValue,
				'quotedColumn' => $this->platform->quoteIdentifier($column['name']),
				'type' => Type::getType($column['type']),
			],
		];
	}

}
