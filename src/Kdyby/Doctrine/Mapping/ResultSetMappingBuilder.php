<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Mapping;

use Doctrine;
use Doctrine\ORM\EntityManager;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ResultSetMappingBuilder extends Doctrine\ORM\Query\ResultSetMappingBuilder
{

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var int
	 */
	private $sqlCounter = 0;

	/**
	 * @var \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	private $platform;

	/**
	 * @var int
	 */
	private $defaultRenameMode;



	public function __construct(EntityManager $em, $defaultRenameMode = Doctrine\ORM\Query\ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT)
	{
		parent::__construct($em, $defaultRenameMode);
		$this->em = $em;
		$this->platform = $this->em->getConnection()->getDatabasePlatform();
		$this->defaultRenameMode = $defaultRenameMode;
	}



	protected function addAllClassFields($class, $alias, $columnAliasMap = array())
	{
		$classMetadata = $this->em->getClassMetadata($class);

		foreach ($classMetadata->parentClasses as $parentClass) {
			$parentClass = $this->em->getClassMetadata($parentClass);
			$parentAliasMap = $this->getColumnAliasMap($parentClass->getName());
			$this->addFieldsFromClass($parentClass, $alias, $parentAliasMap);
			$this->addAssociationsFromClass($parentClass, $alias, $parentAliasMap);
		}

		$this->addFieldsFromClass($classMetadata, $alias, $columnAliasMap);
		$this->addAssociationsFromClass($classMetadata, $alias, $columnAliasMap);

		if ($classMetadata->isInheritanceTypeSingleTable() || $classMetadata->isInheritanceTypeJoined()) {
			$discrColumn = $classMetadata->discriminatorColumn['name'];
			$resultColumnName = $this->getColumnAlias($discrColumn);

			$this->setDiscriminatorColumn($alias, $resultColumnName);
			$this->addMetaResult($alias, $resultColumnName, $discrColumn);

			foreach ($classMetadata->subClasses as $subClass) {
				$subClass = $this->em->getClassMetadata($subClass);
				$subAliasMap = $this->getColumnAliasMap($subClass->getName());
				$this->addFieldsFromClass($subClass, $alias, $subAliasMap);
				$this->addAssociationsFromClass($subClass, $alias, $subAliasMap);
			}
		}
	}



	protected function addFieldsFromClass(Doctrine\ORM\Mapping\ClassMetadata $class, $alias, $columnAliasMap)
	{
		foreach ($class->getColumnNames() as $columnName) {
			$propertyName = $class->getFieldName($columnName);
			if ($class->isInheritedField($propertyName)) {
				continue;
			}

			$columnAlias = $this->platform->getSQLResultCasing($columnAliasMap[$columnName]);

			if (isset($this->fieldMappings[$columnAlias])) {
				throw new \InvalidArgumentException("The column '$columnName' conflicts with another column in the mapper.");
			}

			$this->addFieldResult($alias, $columnAlias, $propertyName, $class->getName());
		}
	}



	protected function addAssociationsFromClass(Doctrine\ORM\Mapping\ClassMetadata $class, $alias, $columnAliasMap)
	{
		foreach ($class->associationMappings as $fieldName => $associationMapping) {
			if ($class->isInheritedAssociation($fieldName)) {
				continue;
			}

			if (!$associationMapping['isOwningSide'] || !$associationMapping['type'] & ClassMetadata::TO_ONE) {
				continue;
			}

			if (empty($associationMapping['joinColumns'])) { // todo: joinTableColumns
				continue;
			}

			foreach ($associationMapping['joinColumns'] as $joinColumn) {
				$columnName = $joinColumn['name'];
				$columnAlias = $this->platform->getSQLResultCasing($columnAliasMap[$columnName]);

				if (isset($this->metaMappings[$columnAlias])) {
					throw new \InvalidArgumentException("The column '$columnAlias' conflicts with another column in the mapper.");
				}

				$this->addMetaResult(
					$alias,
					$columnAlias,
					$columnName,
					(isset($associationMapping['id']) && $associationMapping['id'] === true)
				);
			}
		}
	}



	/**
	 * Gets column alias for a given column.
	 *
	 * @param string $columnName
	 * @param int $mode
	 * @param array $customRenameColumns
	 *
	 * @return string
	 */
	private function getColumnAlias($columnName, $mode = NULL, array $customRenameColumns = array())
	{
		$mode = $mode ?: $this->defaultRenameMode;
		switch ($mode) {
			case self::COLUMN_RENAMING_INCREMENT:
				return $columnName . '_' . $this->sqlCounter++;

			case self::COLUMN_RENAMING_CUSTOM:
				return isset($customRenameColumns[$columnName]) ? $customRenameColumns[$columnName] : $columnName;

			case self::COLUMN_RENAMING_NONE:
			default:
				return $columnName;
		}
	}



	/**
	 * Retrieves a class columns and join columns aliases that are used in the SELECT clause.
	 *
	 * This depends on the renaming mode selected by the user.
	 *
	 * @param string $className
	 * @param int $mode
	 * @param array $customRenameColumns
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 * @return array
	 */
	private function getColumnAliasMap($className, $mode = NULL, array $customRenameColumns = array())
	{
		$mode = $mode ? : $this->defaultRenameMode;

		if ($customRenameColumns) { // for BC with 2.2-2.3 API
			$mode = self::COLUMN_RENAMING_CUSTOM;
		}

		$columnAlias = array();
		$class = $this->em->getClassMetadata($className);

		foreach ($class->getColumnNames() as $columnName) {
			if ($class->isInheritedField($class->getFieldForColumn($columnName))) {
				continue;
			}

			$columnAlias[$columnName] = $this->getColumnAlias($columnName, $mode, $customRenameColumns);
		}

		foreach ($class->associationMappings as $fieldName => $associationMapping) {
			if ($class->isInheritedAssociation($fieldName)) {
				continue;
			}

			if (!$associationMapping['isOwningSide'] || !$associationMapping['type'] & ClassMetadata::TO_ONE) {
				continue;
			}

			if (empty($associationMapping['joinColumns'])) {
				continue;
			}

			foreach ($associationMapping['joinColumns'] as $joinColumn) { // todo: joinTableColumns
				$columnName = $joinColumn['name'];
				$columnAlias[$columnName] = $this->getColumnAlias($columnName, $mode, $customRenameColumns);
			}
		}

		return $columnAlias;
	}



	/**
	 * Generates the Select clause from this ResultSetMappingBuilder.
	 *
	 * Works only for all the entity results. The select parts for scalar
	 * expressions have to be written manually.
	 *
	 * @param array $tableAliases
	 *
	 * @return string
	 */
	public function generateSelectClause($tableAliases = array())
	{
		$sql = "";

		foreach ($this->columnOwnerMap as $columnName => $dqlAlias) {
			$tableAlias = isset($tableAliases[$dqlAlias]) ? $tableAliases[$dqlAlias] : $dqlAlias;

			if ($sql) {
				$sql .= ", ";
			}

			$sql .= $tableAlias . ".";

			if (isset($this->fieldMappings[$columnName])) {
				$class = $this->em->getClassMetadata($this->declaringClasses[$columnName]);
				$fieldName = $this->fieldMappings[$columnName];

				if (!$class->hasField($fieldName)) {
					if (!$class->isInheritanceTypeSingleTable() && !$class->isInheritanceTypeJoined()) {
						throw new Kdyby\Doctrine\UnexpectedValueException("Entity " . $class->getName() . " has no field '$fieldName' for column '$columnName'.");
					}

					foreach ($class->subClasses as $subClass) {
						$subClass = $this->em->getClassMetadata($subClass);
						if (!$subClass->hasField($fieldName)) {
							continue;
						}

						$sql .= $subClass->fieldMappings[$fieldName]['columnName'];
						break;
					}

				} else {
					$sql .= $class->fieldMappings[$fieldName]['columnName'];
				}

			} elseif (isset($this->metaMappings[$columnName])) {
				$sql .= $this->metaMappings[$columnName];

			} elseif (isset($this->discriminatorColumns[$columnName])) {
				$sql .= $this->discriminatorColumns[$columnName];
			}

			$sql .= " AS " . $columnName;
		}

		return $sql;
	}

}
