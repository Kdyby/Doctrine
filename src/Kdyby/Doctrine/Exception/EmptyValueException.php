<?php

namespace Kdyby\Doctrine\Exception;

use Doctrine;

/**
 * @author Filip Procházka <filip@prochazka.su>
 * @deprecated
 */
class EmptyValueException extends DBALException
{

    /**
     * @var string|NULL
     */
    public $column;


    /**
     * @param \Exception|\Throwable     $previous
     * @param string|NULL               $column
     * @param string                    $query
     * @param array                     $params
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

class_alias(EmptyValueException::class, 'Kdyby\Doctrine\EmptyValueException');
