<?php

namespace Kdyby\Doctrine\Exception;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NotSupportedException extends \LogicException implements IException
{

}

class_alias(NotSupportedException::class, 'Kdyby\Doctrine\NotSupportedException');
