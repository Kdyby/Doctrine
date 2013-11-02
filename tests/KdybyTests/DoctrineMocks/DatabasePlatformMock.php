<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\DoctrineMocks;

use Doctrine;



class DatabasePlatformMock extends Doctrine\DBAL\Platforms\AbstractPlatform
{

	private $_sequenceNextValSql = "";

	private $_prefersIdentityColumns = TRUE;

	private $_prefersSequences = FALSE;



	/**
	 * @override
	 */
	public function getNativeDeclaration(array $field)
	{
	}



	/**
	 * @override
	 */
	public function getPortableDeclaration(array $field)
	{
	}



	/**
	 * @override
	 */
	public function prefersIdentityColumns()
	{
		return $this->_prefersIdentityColumns;
	}



	/**
	 * @override
	 */
	public function prefersSequences()
	{
		return $this->_prefersSequences;
	}



	/** @override */
	public function getSequenceNextValSQL($sequenceName)
	{
		return $this->_sequenceNextValSql;
	}



	/** @override */
	public function getBooleanTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getIntegerTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getBigIntTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getSmallIntTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
	{
	}



	/** @override */
	public function getVarcharTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getClobTypeDeclarationSQL(array $field)
	{
	}



	/* MOCK API */

	public function setPrefersIdentityColumns($bool)
	{
		$this->_prefersIdentityColumns = $bool;
	}



	public function setPrefersSequences($bool)
	{
		$this->_prefersSequences = $bool;
	}



	public function setSequenceNextValSql($sql)
	{
		$this->_sequenceNextValSql = $sql;
	}



	public function getName()
	{
		return 'mock';
	}



	protected function initializeDoctrineTypeMappings()
	{

	}



	/**
	 * Gets the SQL Snippet used to declare a BLOB column type.
	 */
	public function getBlobTypeDeclarationSQL(array $field)
	{
		throw Doctrine\DBAL\DBALException::notSupported(__METHOD__);
	}
}
