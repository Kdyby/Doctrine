<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console;

use Kdyby;
use Nette;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ColoredSqlOutput extends Nette\Object implements OutputInterface
{

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	private $output;



	public function __construct(OutputInterface $output)
	{
		$this->output = $output;
	}



	protected function formatSqls($message)
	{
		$message = Nette\Utils\Strings::replace($message, "~((?:CREATE|ALTER|DROP) TABLE|(?:DROP|CREATE) INDEX)[^;]+;~i", function (array $match) {
			$output = Nette\Utils\Strings::replace($match[0], '~(?<=\b)([^\s]*[a-z]+[^\s]*)(?=\b)~', function ($id) {
				return '<info>' . $id[0] . '</info>';
			});

			return $output;
		});

		return $message;
	}



	/**
	 * {@inheritdoc}
	 */
	public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
	{
		return $this->output->write($this->formatSqls($messages), $newline, $type);
	}



	/**
	 * {@inheritdoc}
	 */
	public function writeln($messages, $type = self::OUTPUT_NORMAL)
	{
		return $this->output->writeln($this->formatSqls($messages), $type);
	}



	/**
	 * {@inheritdoc}
	 */
	public function setVerbosity($level)
	{
		return $this->output->setVerbosity($level);
	}



	/**
	 * {@inheritdoc}
	 */
	public function getVerbosity()
	{
		return $this->output->getVerbosity();
	}



	/**
	 * {@inheritdoc}
	 */
	public function setDecorated($decorated)
	{
		return $this->output->setDecorated($decorated);
	}



	/**
	 * {@inheritdoc}
	 */
	public function isDecorated()
	{
		return $this->output->isDecorated();
	}



	/**
	 * {@inheritdoc}
	 */
	public function setFormatter(OutputFormatterInterface $formatter)
	{
		return $this->output->setFormatter($formatter);
	}



	/**
	 * {@inheritdoc}
	 */
	public function getFormatter()
	{
		return $this->output->getFormatter();
	}

}
