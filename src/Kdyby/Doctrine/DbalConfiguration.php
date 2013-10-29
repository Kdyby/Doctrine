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
use Doctrine\DBAL\Configuration as BaseConfiguration;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class DbalConfiguration extends BaseConfiguration
{

	/**
	 * @param bool $allow
	 */
	public function setAllowRepeatOnDeadlock($allow = TRUE)
	{
		$this->_attributes['allowRepeatOnDeadlock'] = (bool) $allow;
	}



	/**
	 * @return bool
	 */
	public function getAllowRepeatOnDeadlock()
	{
		return isset($this->_attributes['allowRepeatOnDeadlock']) ?
			$this->_attributes['allowRepeatOnDeadlock'] : FALSE;
	}

}
