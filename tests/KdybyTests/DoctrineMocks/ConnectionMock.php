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
use Kdyby;



class ConnectionMock extends Kdyby\Doctrine\Connection
{

	private $_fetchOneResult;

	private $_platformMock;

	private $_lastInsertId = 0;

	private $_inserts = [];

	private $_executeUpdates = [];



	public function __construct(array $params, $driver, $config = NULL, $eventManager = NULL)
	{
		$this->_platformMock = new DatabasePlatformMock();

		// Override possible assignment of platform to database platform mock
		parent::__construct(['platform' => $this->_platformMock] + $params, $driver, $config, $eventManager);

	}



	/**
	 * @override
	 */
	public function getDatabasePlatform()
	{
		if ($this->_platformMock !== NULL) {
			return $this->_platformMock;
		}

		return parent::getDatabasePlatform();
	}



	/**
	 * @override
	 */
	public function insert($tableName, array $data, array $types = [])
	{
		$this->_inserts[$tableName][] = $data;
	}



	/**
	 * @override
	 */
	public function executeUpdate($query, array $params = [], array $types = [])
	{
		$this->_executeUpdates[] = ['query' => $query, 'params' => $params, 'types' => $types];
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
	public function fetchColumn($statement, array $params = [], $column = 0, array $types = [])
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
		$this->_inserts = [];
		$this->_lastInsertId = 0;
	}
}
