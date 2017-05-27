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
use KdybyTests\Doctrine\SchemaManagerMock;

class DriverMock implements Doctrine\DBAL\Driver
{

	private $_platformMock;

	private $_schemaManagerMock;



	public function connect(array $params, $username = NULL, $password = NULL, array $driverOptions = [])
	{
		return new DriverConnectionMock();
	}



	/**
	 * Constructs the Sqlite PDO DSN.
	 *
	 * @param array $params
	 * @return string
	 */
	protected function _constructPdoDsn(array $params)
	{
		return "";
	}



	/**
	 * @override
	 */
	public function getDatabasePlatform()
	{
		if (!$this->_platformMock) {
			$this->_platformMock = new DatabasePlatformMock;
		}

		return $this->_platformMock;
	}



	/**
	 * @override
	 */
	public function getSchemaManager(Doctrine\DBAL\Connection $conn)
	{
		if ($this->_schemaManagerMock == NULL) {
			return new SchemaManagerMock($conn);
		} else {
			return $this->_schemaManagerMock;
		}
	}



	public function setDatabasePlatform(Doctrine\DBAL\Platforms\AbstractPlatform $platform)
	{
		$this->_platformMock = $platform;
	}



	public function setSchemaManager(Doctrine\DBAL\Schema\AbstractSchemaManager $sm)
	{
		$this->_schemaManagerMock = $sm;
	}



	public function getName()
	{
		return 'mock';
	}



	public function getDatabase(Doctrine\DBAL\Connection $conn)
	{
		return;
	}
}
