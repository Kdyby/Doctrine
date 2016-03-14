<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\DoctrineMocks;

use Doctrine;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Kdyby;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TestTypeMock extends Doctrine\DBAL\Types\Type
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
		throw new Kdyby\Doctrine\InvalidStateException(
			"Please, use the 'columnDefinition' property of @Column() annotation."
		);
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return 'test';
	}

}
