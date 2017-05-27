<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine\Models2;

use Kdyby\Doctrine\DI\IEntityProvider;
use Nette\DI\CompilerExtension;

class Models2Extension extends CompilerExtension implements IEntityProvider
{

	public function getEntityMappings()
	{
		return [
			__NAMESPACE__ => __DIR__,
		];
	}

}
