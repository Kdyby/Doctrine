<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine\AnnotationDriver\App;

use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @ORM\Entity()
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class FooEntity extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

}
