<?php

namespace Kdyby\Doctrine\Exception;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class StaticClassException extends \LogicException implements IException
{

}

class_alias(StaticClassException::class, 'Kdyby\Doctrine\StaticClassException');
