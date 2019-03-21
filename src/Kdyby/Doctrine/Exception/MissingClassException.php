<?php

namespace Kdyby\Doctrine\Exception;

/**
 * When class is not found
 */
class MissingClassException extends \LogicException implements IException
{

}

class_alias(MissingClassException::class, 'Kdyby\Doctrine\MissingClassException');
