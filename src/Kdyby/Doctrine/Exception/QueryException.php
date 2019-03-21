<?php

namespace Kdyby\Doctrine\Exception;
use Doctrine;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class QueryException extends \RuntimeException implements IException
{

    /**
     * @var \Doctrine\ORM\AbstractQuery|NULL
     */
    public $query;


    /**
     * @param \Exception|\Throwable            $previous
     * @param \Doctrine\ORM\AbstractQuery|NULL $query
     * @param string|NULL                      $message
     */
    public function __construct($previous, Doctrine\ORM\AbstractQuery $query = NULL, $message = NULL)
    {
        parent::__construct($message ?: $previous->getMessage(), 0, $previous);
        $this->query = $query;
    }

}

class_alias(QueryException::class, 'Kdyby\Doctrine\QueryException');
