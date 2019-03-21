<?php

declare(strict_types=1);

namespace Kdyby\Doctrine\Exception;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InvalidArgumentException extends \InvalidArgumentException implements IException
{

}

class_alias(InvalidArgumentException::class, 'Kdyby\Doctrine\InvalidArgumentException');
