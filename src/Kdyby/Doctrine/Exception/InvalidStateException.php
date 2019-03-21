<?php

declare(strict_types=1);

namespace Kdyby\Doctrine\Exception;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InvalidStateException extends \RuntimeException implements IException
{

}

class_alias(InvalidStateException::class, 'Kdyby\Doctrine\InvalidStateException');
