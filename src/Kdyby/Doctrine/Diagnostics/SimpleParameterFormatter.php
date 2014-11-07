<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Kdyby;
use Nette;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class SimpleParameterFormatter extends Nette\Object
{

	/**
	 * @param mixed $param
	 * @return mixed
	 */
	public static function format($param)
	{
		if (is_int($param) || is_float($param)) {
			return $param;

		} elseif (is_string($param)) {
			return "'" . addslashes($param) . "'";

		} elseif (is_null($param)) {
			return "NULL";

		} elseif (is_bool($param)) {
			return $param ? "TRUE" : "FALSE";

		} elseif (is_array($param)) {
			$formatted = array();
			foreach ($param as $value) {
				$formatted[] = self::format($value);
			}
			return implode(', ', $formatted);

		} elseif ($param instanceof \Datetime) {
			/** @var \Datetime $param */
			return "'" . $param->format('Y-m-d H:i:s') . "'";

		} elseif ($param instanceof Kdyby\Doctrine\Geo\Element) {
			return '"' . $param->__toString() . '"';

		} elseif (is_object($param)) {
			return get_class($param) . (method_exists($param, 'getId') ? '(' . $param->getId() . ')' : '');

		} else {
			return @"'$param'";
		}
	}

}
