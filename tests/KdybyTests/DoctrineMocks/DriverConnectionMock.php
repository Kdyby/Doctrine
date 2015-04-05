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



class DriverConnectionMock implements Doctrine\DBAL\Driver\Connection
{

	public function prepare($prepareString)
	{
	}



	public function query()
	{
		return new StatementMock;
	}



	public function quote($input, $type = \PDO::PARAM_STR)
	{
	}



	public function exec($statement)
	{
	}



	public function lastInsertId($name = NULL)
	{
	}



	public function beginTransaction()
	{
	}



	public function commit()
	{
	}



	public function rollBack()
	{
	}



	public function errorCode()
	{
	}



	public function errorInfo()
	{
	}
}
