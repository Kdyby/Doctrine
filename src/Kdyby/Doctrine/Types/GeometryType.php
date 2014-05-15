<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Types;

use Doctrine;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class GeometryType extends Kdyby\Doctrine\DbalType
{

	/**
	 * @return bool
	 */
	public function canRequireSQLConversion()
	{
		return TRUE;
	}



	/**
	 * @param string $sqlExpr
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @throws \Kdyby\Doctrine\NotImplementedException
	 * @return string
	 */
	public function convertToPHPValueSQL($sqlExpr, $platform)
	{
		if (!$platform instanceof Doctrine\DBAL\Platforms\MySqlPlatform) {
			throw new Kdyby\Doctrine\NotImplementedException("Unsupported platform " . $platform->getName());
		}

		return 'AsText(' . $sqlExpr . ')';
	}



	/**
	 * @param string $sqlExpr
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @throws \Kdyby\Doctrine\NotImplementedException
	 * @return string
	 */
	public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
	{
		if (!$platform instanceof Doctrine\DBAL\Platforms\MySqlPlatform) {
			throw new Kdyby\Doctrine\NotImplementedException("Unsupported platform " . $platform->getName());
		}

		return 'GeomFromText(' . $sqlExpr . ')';
	}



	/**
	 * @param mixed|Kdyby\Doctrine\Geo\Element $value
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @return string|NULL
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if (!$value instanceof Kdyby\Doctrine\Geo\Element) {
			return NULL;
		}

		return $value->__toString();
	}



	/**
	 * @param string $value
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @return mixed|Kdyby\Doctrine\Geo\Element
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if ($value === NULL || empty($value)) {
			return NULL;
		}

		return Kdyby\Doctrine\Geo\Element::fromString($value);
	}

}
