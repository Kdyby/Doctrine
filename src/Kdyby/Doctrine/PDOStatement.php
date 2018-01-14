<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine\DBAL\Statement;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PDOStatement extends Statement
{

	/**
	 * {@inheritdoc}
	 */
	public function execute($params = NULL)
	{
		try {
			return parent::execute($params);

		} catch (\Exception $e) {
			/** @var \Kdyby\Doctrine\Connection $conn */
			$conn = $this->conn;
			throw $conn->resolveException($e, $this->sql, (is_array($params) ? $params : []) + $this->params);
		}
	}

}
