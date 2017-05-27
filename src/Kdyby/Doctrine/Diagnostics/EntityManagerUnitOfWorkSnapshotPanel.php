<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Doctrine;
use Kdyby;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\Helpers;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityManagerUnitOfWorkSnapshotPanel
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \Throwable[]
	 */
	private $whitelistExceptions = [];

	/**
	 * @var \Doctrine\ORM\UnitOfWork|NULL
	 */
	private $unitOfWorkSnapshot;

	public function markExceptionOwner(Doctrine\ORM\EntityManager $em, $exception)
	{
		if ($this->em !== $em) {
			return;
		}

		$this->whitelistExceptions[] = $exception;
	}

	public function snapshotUnitOfWork(Doctrine\ORM\EntityManager $em)
	{
		if ($this->em !== $em) {
			return;
		}

		$this->unitOfWorkSnapshot = clone $em->getUnitOfWork();
	}

	/**
	 * @param \Exception|\Throwable $e
	 * @return array|NULL
	 */
	public function renderEntityManagerException($e)
	{
		if (!in_array($e, $this->whitelistExceptions, TRUE)) {
			return NULL; // ignore
		}

		if (strpos(get_class($e), 'Doctrine\\ORM\\') !== FALSE && Helpers::findTrace($e->getTrace(), Doctrine\ORM\EntityManager::class . '::flush')) {
			$UoW = $this->unitOfWorkSnapshot ?: $this->em->getUnitOfWork();

			$panel = '<div class="inner"><p><b>IdentityMap</b></p>' .
				Dumper::toHtml($UoW->getIdentityMap(), [Dumper::COLLAPSE => TRUE]) .
				'</div>';

			if ($scheduled = $UoW->getScheduledEntityInsertions()) {
				$panel .= '<div class="inner"><p><b>Scheduled entity insertions</b></p>' .
					Dumper::toHtml($scheduled, [Dumper::COLLAPSE => TRUE]) .
					'</div>';
			}

			if ($scheduled = $UoW->getScheduledEntityDeletions()) {
				$panel .= '<div class="inner"><p><b>Scheduled entity deletions</b></p>' .
					Dumper::toHtml($scheduled, [Dumper::COLLAPSE => TRUE]) .
					'</div>';
			}

			if ($scheduled = $UoW->getScheduledEntityUpdates()) {
				$panel .= '<div class="inner"><p><b>Scheduled entity updates</b></p>' .
					Dumper::toHtml($scheduled, [Dumper::COLLAPSE => TRUE]) .
					'</div>';
			}

			return [
				'tab' => Doctrine\ORM\UnitOfWork::class,
				'panel' => $panel,
			];
		}
	}

	/**
	 * @param Doctrine\ORM\EntityManager $em
	 * @return Panel
	 */
	public function bindEntityManager(Doctrine\ORM\EntityManager $em)
	{
		if ($this->em !== NULL) {
			throw new Kdyby\Doctrine\InvalidStateException(sprintf('%s is already bound to an entity manager.', __CLASS__));
		}

		$this->em = $em;
		if ($this->em instanceof Kdyby\Doctrine\EntityManager) {
			$this->em->bindTracyPanel($this);
		}

		Debugger::getBlueScreen()->addPanel([$this, 'renderEntityManagerException']);
	}

}
