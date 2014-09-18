<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\DoctrineMocks;

use Doctrine\DBAL\Connection;
use Doctrine;
use KdybyTests\DoctrineMocks\DatabasePlatformMock;



class ConnectionMock extends Doctrine\DBAL\Connection
{

	private $_fetchOneResult;

	private $_platformMock;

	private $_lastInsertId = 0;

	private $_inserts = array();

	private $_executeUpdates = array();



	public function __construct(array $params, $driver, $config = NULL, $eventManager = NULL)
	{
		$this->_platformMock = new DatabasePlatformMock();

		// Override possible assignment of platform to database platform mock
		parent::__construct(array('platform' => $this->_platformMock) + $params, $driver, $config, $eventManager);

	}



	/**
	 * @override
	 */
	public function getDatabasePlatform()
	{
		return $this->_platformMock;
	}



	/**
	 * @override
	 */
	public function insert($tableName, array $data, array $types = array())
	{
		$this->_inserts[$tableName][] = $data;
	}



	/**
	 * @override
	 */
	public function executeUpdate($query, array $params = array(), array $types = array())
	{
		$this->_executeUpdates[] = array('query' => $query, 'params' => $params, 'types' => $types);
	}



	/**
	 * @override
	 */
	public function lastInsertId($seqName = NULL)
	{
		return $this->_lastInsertId;
	}



	/**
	 * @override
	 */
	public function fetchColumn($statement, array $params = array(), $colnum = 0)
	{
		return $this->_fetchOneResult;
	}



	/**
	 * @override
	 */
	public function quote($input, $type = NULL)
	{
		if (is_string($input)) {
			return "'" . $input . "'";
		}

		return $input;
	}



	/* Mock API */

	public function setFetchOneResult($fetchOneResult)
	{
		$this->_fetchOneResult = $fetchOneResult;
	}



	public function setDatabasePlatform($platform)
	{
		$this->_platformMock = $platform;
	}



	public function setLastInsertId($id)
	{
		$this->_lastInsertId = $id;
	}



	public function getInserts()
	{
		return $this->_inserts;
	}



	public function getExecuteUpdates()
	{
		return $this->_executeUpdates;
	}



	public function reset()
	{
		$this->_inserts = array();
		$this->_lastInsertId = 0;
	}
}
