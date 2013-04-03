<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Proxy;

use Doctrine;
use Doctrine\ORM\Proxy\Proxy;
use Kdyby;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class JitProxyWarmer extends Nette\Object
{

	public function warmUp(Kdyby\Doctrine\EntityManager $em)
	{
		$conf = $em->getConfiguration();

		if (!is_dir($conf->getProxyDir())) {
			umask(0);
			@mkdir($conf->getProxyDir());
		}

		$keep = $regenerate = array();
		$proxies = Nette\Utils\Finder::find(Proxy::MARKER . '*.php')->in($conf->getProxyDir());
		foreach ($proxies as $proxyFile) {
			/** @var \SplFileInfo $proxyFile */

			if (!$proxyFile->getRealPath()) {
				continue;
			}

			$entity = NULL;
			$h = fopen($proxyFile->getPathname(), 'r');
			while ($line = fgets($h)) {
				if ($m = Strings::match($line, '~class\s+(.*?)\s+extends\s+(?P<entity>.*?)\s+implements\s+.*?Proxy~')) {
					$entity = ltrim($m['entity'], '\\');
					break;
				}
			}
			fclose($h);

			if (!$entity || !class_exists($entity)) {
				@unlink($proxyFile->getPathname());
				continue;
			}

			$refl = Nette\Reflection\ClassType::from($entity);
			do {
				if (filemtime($proxyFile->getPathname()) < filemtime($refl->getFileName())) {
					@unlink($proxyFile->getPathname());
					$regenerate[] = $entity;
					continue 2;
				}

			} while ($refl = $refl->getParentClass());

			$keep[] = $entity;
		}

		// load all metadata
		$available = array_map(function (ClassMetadata $meta) {
			return $meta->getName();
		}, $em->getMetadataFactory()->getAllMetadata());

		// compute diff
		$regenerate = array_merge($regenerate, array_diff($available, $keep, $regenerate));

		// to metadata
		$regenerate = array_map(function ($class) use ($em) {
			return $em->getClassMetadata($class);
		}, $regenerate);

		// generate code
		$em->getProxyFactory()->generateProxyClasses($regenerate);
	}

}
