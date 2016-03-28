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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InvalidStateException extends \RuntimeException implements Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NotSupportedException extends \LogicException implements Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class StaticClassException extends \LogicException implements Exception
{

}



/**
 * The exception that is thrown when a requested method or operation is not implemented.
 */
class NotImplementedException extends \LogicException implements Exception
{

}



/**
 * When class is not found
 */
class MissingClassException extends \LogicException implements Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class UnexpectedValueException extends \UnexpectedValueException implements Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @deprecated
 */
class DBALException extends \RuntimeException implements Exception
{

	/**
	 * @var string
	 */
	public $query;

	/**
	 * @var array
	 */
	public $params = [];

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	public $connection;



	/**
	 * @param \Exception $previous
	 * @param string $query
	 * @param array $params
	 * @param \Doctrine\DBAL\Connection $connection
	 * @param string $message
	 */
	public function __construct($previous, $query = NULL, $params = [], Doctrine\DBAL\Connection $connection = NULL, $message = NULL)
	{
		parent::__construct($message ?: $previous->getMessage(), $previous->getCode(), $previous);
		$this->query = $query;
		$this->params = $params;
		$this->connection = $connection;
	}



	/**
	 * This is just a paranoia, hopes no one actually serializes exceptions.
	 *
	 * @return array
	 */
	public function __sleep()
	{
		return ['message', 'code', 'file', 'line', 'errorInfo', 'query', 'params'];
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @deprecated
 */
class DuplicateEntryException extends DBALException
{

	/**
	 * @var array
	 */
	public $columns;



	/**
	 * @param \Exception $previous
	 * @param array $columns
	 * @param string $query
	 * @param array $params
	 * @param \Doctrine\DBAL\Connection $connection
	 */
	public function __construct($previous, $columns = [], $query = NULL, $params = [], Doctrine\DBAL\Connection $connection = NULL)
	{
		parent::__construct($previous, $query, $params, $connection);
		$this->columns = $columns;
	}



	/**
	 * @return array
	 */
	public function __sleep()
	{
		return array_merge(parent::__sleep(), ['columns']);
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @deprecated
 */
class EmptyValueException extends DBALException
{

	/**
	 * @var string
	 */
	public $column;



	/**
	 * @param \Exception $previous
	 * @param string $column
	 * @param string $query
	 * @param array $params
	 * @param \Doctrine\DBAL\Connection $connection
	 */
	public function __construct($previous, $column = NULL, $query = NULL, $params = [], Doctrine\DBAL\Connection $connection = NULL)
	{
		parent::__construct($previous, $query, $params, $connection);
		$this->column = $column;
	}



	/**
	 * @return array
	 */
	public function __sleep()
	{
		return array_merge(parent::__sleep(), ['column']);
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class QueryException extends \RuntimeException implements Exception
{

	/**
	 * @var \Doctrine\ORM\Query
	 */
	public $query;



	/**
	 * @param \Exception $previous
	 * @param \Doctrine\ORM\AbstractQuery $query
	 * @param string $message
	 */
	public function __construct($previous, Doctrine\ORM\AbstractQuery $query = NULL, $message = "")
	{
		parent::__construct($message ?: $previous->getMessage(), 0, $previous);
		$this->query = $query;
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BatchImportException extends \RuntimeException implements Exception
{

}



/**
 * @author Michael Moravec
 */
class ReadOnlyCollectionException extends NotSupportedException
{
	/**
	 * @throws ReadOnlyCollectionException
	 */
	public static function invalidAccess($what)
	{
		return new static('Could not ' . $what . ' read-only collection, write/modify operations are forbidden.');
	}
}
