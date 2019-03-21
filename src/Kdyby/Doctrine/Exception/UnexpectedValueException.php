<?php

namespace Kdyby\Doctrine\Exception;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class UnexpectedValueException extends \UnexpectedValueException implements IException
{

}

class_alias(UnexpectedValueException::class, 'Kdyby\Doctrine\UnexpectedValueException');
