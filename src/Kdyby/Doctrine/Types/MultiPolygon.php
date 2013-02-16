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
class MultiPolygon extends GeometryType
{

	/**
	 * @param array $fieldDeclaration
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 *
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return mixed
	 */
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		return 'MultiPolygon';
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return Kdyby\Doctrine\DbalType::MULTI_POLYGON;
	}

}
