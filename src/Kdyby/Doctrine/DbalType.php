<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class DbalType extends Doctrine\DBAL\Types\Type
{

	const ENUM = 'enum';
	const POINT = 'point';
	const LINE_STRING = 'lineString';
	const MULTI_LINE_STRING = 'multiLineString';
	const POLYGON = 'polygon';
	const MULTI_POLYGON = 'multiPolygon';
	const GEOMETRY_COLLECTION = 'geometryCollection';

}
