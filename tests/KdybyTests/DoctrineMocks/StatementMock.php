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



/**
 * This class is a mock of the Statement interface.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class StatementMock implements \IteratorAggregate, Doctrine\DBAL\Driver\Statement
{

	public function bindValue($param, $value, $type = NULL)
	{
	}



	public function bindParam($column, &$variable, $type = NULL, $length = NULL)
	{
	}



	public function errorCode()
	{
	}



	public function errorInfo()
	{
	}



	public function execute($params = NULL)
	{
	}



	public function rowCount()
	{
	}



	public function closeCursor()
	{
	}



	public function columnCount()
	{
	}



	public function setFetchMode($fetchStyle, $arg2 = NULL, $arg3 = NULL)
	{
	}



	public function fetch($fetchStyle = NULL, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
	{
	}



	public function fetchAll($fetchStyle = NULL, $fetchArgument = NULL, $ctorArgs = NULL)
	{
	}



	public function fetchColumn($columnIndex = 0)
	{
	}



	public function getIterator()
	{
	}
}
