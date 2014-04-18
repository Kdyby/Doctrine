<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Kdyby;
use Nette;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class TypeParameterFormatter extends Nette\Object
{

	/**
	 * @param mixed $param
	 * @param string|int $type
	 * @param AbstractPlatform $platform
	 * @return mixed
	 */
	public static function format($param, $type, AbstractPlatform $platform)
	{
		if ($param instanceof Kdyby\Doctrine\Geo\Element || is_object($param)) {
			return $param;

		} elseif (array_key_exists($type, Type::getTypesMap())) {
			return Type::getType($type)->convertToDatabaseValue($param, $platform);

		} else {
			return $param;
		}
	}

}
