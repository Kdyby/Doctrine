<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 * Copyright (c) 2016 Jaroslav Hranička (hranicka@outlook.com)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\DI;

use Doctrine;
use Kdyby;
use Nette;
use Nette\DI\Container;

class EntityLocator
{

	/** @var array */
	private $map = [];

	public function setup(Container $container, array $queue, array $classNames, array $managers)
	{
		if (empty($queue)) {
			return;
		}

		foreach ($queue as $manager => $items) {
			$repository = $classNames[$manager];
			/** @var Kdyby\Doctrine\EntityManager $entityManager */
			$entityManager = $container->getService($managers[$manager]);
			/** @var Doctrine\ORM\Mapping\ClassMetadata $entityMetadata */
			$metadataFactory = $entityManager->getMetadataFactory();

			$allMetadata = [];
			foreach ($metadataFactory->getAllMetadata() as $entityMetadata) {
				if ($repository === $entityMetadata->customRepositoryClassName || empty($entityMetadata->customRepositoryClassName)) {
					continue;
				}

				$allMetadata[ltrim($entityMetadata->customRepositoryClassName, '\\')] = $entityMetadata;
			}

			foreach ($items as $item) {
				if (!isset($allMetadata[$item[0]])) {
					throw new Nette\Utils\AssertionException(sprintf('Repository class %s have been found in DIC, but no entity has it assigned and it has no entity configured', $item[0]));
				}

				$entityMetadata = $allMetadata[$item[0]];
				$this->map[$item[0]] = $entityMetadata->getName();
			}
		}
	}

	public function get($repositoryName)
	{
		if (isset($this->map[$repositoryName])) {
			return $this->map[$repositoryName];
		} else {
			throw new Kdyby\Doctrine\InvalidArgumentException('Entity for repository %s was not found.', $repositoryName);
		}
	}

}
