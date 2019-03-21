<?php

namespace Kdyby\Doctrine\Exception;

use Doctrine;
use Kdyby\Doctrine\Exception;

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
     * @param \Exception|\Throwable     $previous
     * @param array                     $columns
     * @param string                    $query
     * @param array                     $params
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

class_alias(DuplicateEntryException::class, 'Kdyby\Doctrine\DuplicateEntryException');
