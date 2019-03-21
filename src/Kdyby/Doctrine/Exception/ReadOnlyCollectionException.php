<?php

namespace Kdyby\Doctrine\Exception;

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

class_alias(ReadOnlyCollectionException::class, 'Kdyby\Doctrine\ReadOnlyCollectionException');
