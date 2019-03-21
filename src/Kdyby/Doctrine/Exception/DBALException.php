<?php

namespace Kdyby\Doctrine\Exception;
use Doctrine;

/**
 * @author Filip Procházka <filip@prochazka.su>
 * @deprecated
 */
class DBALException extends \RuntimeException implements IException
{

    /**
     * @var string|NULL
     */
    public $query;

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var \Doctrine\DBAL\Connection|NULL
     */
    public $connection;


    /**
     * @param \Exception|\Throwable          $previous
     * @param string|NULL                    $query
     * @param array                          $params
     * @param \Doctrine\DBAL\Connection|NULL $connection
     * @param string|NULL                    $message
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

class_alias(DBALException::class, 'Kdyby\Doctrine\DBALException');
