<?php

namespace Kdyby\Doctrine\Exception;

/**
 * The exception that is thrown when a requested method or operation is not implemented.
 */
class NotImplementedException extends \LogicException implements IException
{

}

class_alias(NotImplementedException::class, 'Kdyby\Doctrine\NotImplementedException');
