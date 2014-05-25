<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Doctrine\DBAL\Logging\SQLLogger;
use Kdyby;
use Nette;
use Tracy\Debugger;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FileLogger extends Nette\Object implements SQLLogger
{

	/**
	 * @var string
	 */
	private $file;



	public function __construct($file)
	{
		if (!file_exists($file)) {
			$dir = dirname($file);
			if (!is_dir($dir)) {
				@mkdir($dir, 0777, TRUE);
			}
			touch($file);
		}

		$this->file = $file;
	}



	public function startQuery($sql, array $params = null, array $types = null)
	{
		$highlighted = Panel::highlightQuery(Panel::formatQuery($sql, (array) $params, (array) $types));
		$formatted = html_entity_decode(strip_tags($highlighted));
		$formatted = preg_replace('#^[\t ]+#m', '', Nette\Utils\Strings::normalize($formatted));

		$message =
			'-- process ' . getmypid() . '; ' . Debugger::$source . "\n" .
			$formatted . "\n\n";

		file_put_contents($this->file, $message, FILE_APPEND);
	}



	public function stopQuery()
	{

	}

}
