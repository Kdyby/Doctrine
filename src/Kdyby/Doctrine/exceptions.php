<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
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
	public $params = array();



	/**
	 * @param \Exception $previous
	 * @param string $query
	 * @param array $params
	 */
	public function __construct(\Exception $previous, $query = NULL, $params = array())
	{
		parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
		$this->query = $query;
		$this->params = $params;
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
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
	 */
	public function __construct(\Exception $previous, $columns = array(), $query = NULL, $params = array())
	{
		parent::__construct($previous, $query, $params);
		$this->columns = $columns;
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
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
	 */
	public function __construct(\Exception $previous, $column = NULL, $query = NULL, $params = array())
	{
		parent::__construct($previous, $query, $params);
		$this->column = $column;
	}

}
