<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\DBAL\SQLParserUtilsException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
final class NativeQueryWrapper extends AbstractQuery
{

	/**
	 * @var \Doctrine\ORM\NativeQuery
	 */
	private $nativeQuery;

	/**
	 * @var int|NULL
	 */
	private $firstResult;

	/**
	 * @var int|NULL
	 */
	private $maxResults;



	/**
	 * @param NativeQuery|AbstractQuery $nativeQuery
	 */
	public function __construct(NativeQuery $nativeQuery)
	{
		$this->nativeQuery = $nativeQuery;
	}



	/**
	 * @return int|NULL
	 */
	public function getFirstResult()
	{
		return $this->firstResult;
	}



	/**
	 * @param int|NULL $firstResult
	 * @return NativeQueryWrapper
	 */
	public function setFirstResult($firstResult)
	{
		$this->firstResult = $firstResult;
		return $this;
	}



	/**
	 * @return int|NULL
	 */
	public function getMaxResults()
	{
		return $this->maxResults;
	}



	/**
	 * @param int|NULL $maxResults
	 * @return NativeQueryWrapper
	 */
	public function setMaxResults($maxResults)
	{
		$this->maxResults = $maxResults;
		return $this;
	}



	/**
	 * @return NativeQuery
	 */
	protected function getLimitedQuery()
	{
		$copy = clone $this->nativeQuery;
		$copy->setParameters(array());

		try {
			$params = $types = array();
			/** @var Query\Parameter $param */
			foreach ($this->nativeQuery->getParameters() as $param) {
				$params[$param->getName()] = $param->getValue();
				$types[$param->getName()] = $param->getType();
			}

			list($query, $params, $types) = SQLParserUtils::expandListParameters($copy->getSQL(), $params, $types);

			$copy->setSQL($query);
			foreach ($params as $i => $value) {
				$copy->setParameter($i, $value, isset($types[$i]) ? $types[$i] : NULL);
			}

		} catch (SQLParserUtilsException $e) {
			$copy->setParameters(clone $this->nativeQuery->getParameters());
		}

		if ($this->maxResults !== NULL || $this->firstResult !== NULL) {
			$em = $this->nativeQuery->getEntityManager();
			$platform = $em->getConnection()->getDatabasePlatform();

			$copy->setSQL($platform->modifyLimitQuery($copy->getSQL(), $this->maxResults, $this->firstResult));
		}

		return $copy;
	}



	public function iterate($parameters = null, $hydrationMode = null)
	{
		return $this->getLimitedQuery()->iterate($parameters, $hydrationMode);
	}



	public function execute($parameters = null, $hydrationMode = null)
	{
		return $this->getLimitedQuery()->execute($parameters, $hydrationMode);
	}



	public function getResult($hydrationMode = self::HYDRATE_OBJECT)
	{
		return $this->getLimitedQuery()->getResult($hydrationMode);
	}



	public function getArrayResult()
	{
		return $this->getLimitedQuery()->getArrayResult();
	}



	public function getScalarResult()
	{
		return $this->getLimitedQuery()->getScalarResult();
	}



	public function getOneOrNullResult($hydrationMode = null)
	{
		return $this->getLimitedQuery()->getOneOrNullResult($hydrationMode);
	}



	public function getSingleResult($hydrationMode = null)
	{
		return $this->getLimitedQuery()->getSingleResult($hydrationMode);
	}



	public function getSingleScalarResult()
	{
		return $this->getLimitedQuery()->getSingleScalarResult();
	}



	public function setSQL($sql)
	{
		$this->nativeQuery->setSQL($sql);
		return $this;
	}



	public function getSQL()
	{
		return $this->nativeQuery->getSQL();
	}



	public function setCacheable($cacheable)
	{
		$this->nativeQuery->setCacheable($cacheable);
		return $this;
	}



	public function isCacheable()
	{
		return $this->nativeQuery->isCacheable();
	}



	public function setCacheRegion($cacheRegion)
	{
		$this->nativeQuery->setCacheRegion($cacheRegion);
		return $this;
	}



	public function getCacheRegion()
	{
		return $this->nativeQuery->getCacheRegion();
	}



	protected function isCacheEnabled()
	{
		return $this->nativeQuery->isCacheEnabled();
	}



	public function getLifetime()
	{
		return $this->nativeQuery->getLifetime();
	}



	public function setLifetime($lifetime)
	{
		$this->nativeQuery->setLifetime($lifetime);
		return $this;
	}



	public function getCacheMode()
	{
		return $this->nativeQuery->getCacheMode();
	}



	public function setCacheMode($cacheMode)
	{
		$this->nativeQuery->setCacheMode($cacheMode);
		return $this;
	}



	public function getEntityManager()
	{
		return $this->nativeQuery->getEntityManager();
	}



	public function free()
	{
		$this->nativeQuery->free();
	}



	public function getParameters()
	{
		return $this->nativeQuery->getParameters();
	}



	public function getParameter($key)
	{
		return $this->nativeQuery->getParameter($key);
	}



	public function setParameters($parameters)
	{
		$this->nativeQuery->setParameters($parameters);
		return $this;
	}



	public function setParameter($key, $value, $type = null)
	{
		$this->nativeQuery->setParameter($key, $value, $type);
		return $this;
	}



	public function processParameterValue($value)
	{
		return $this->nativeQuery->processParameterValue($value);
	}



	public function setResultSetMapping(Query\ResultSetMapping $rsm)
	{
		$this->nativeQuery->setResultSetMapping($rsm);
		return $this;
	}



	protected function getResultSetMapping()
	{
		return $this->nativeQuery->getResultSetMapping();
	}



	public function setHydrationCacheProfile(QueryCacheProfile $profile = null)
	{
		$this->nativeQuery->setHydrationCacheProfile($profile);
		return $this;
	}



	public function getHydrationCacheProfile()
	{
		return $this->nativeQuery->getHydrationCacheProfile();
	}



	public function setResultCacheProfile(QueryCacheProfile $profile = null)
	{
		$this->nativeQuery->setResultCacheProfile($profile);
		return $this;
	}



	public function setResultCacheDriver($resultCacheDriver = null)
	{
		$this->nativeQuery->setResultCacheDriver($resultCacheDriver);
		return $this;
	}



	public function getResultCacheDriver()
	{
		return $this->nativeQuery->getResultCacheDriver();
	}



	public function useResultCache($bool, $lifetime = null, $resultCacheId = null)
	{
		$this->nativeQuery->useResultCache($bool, $lifetime, $resultCacheId);
		return $this;
	}



	public function setResultCacheLifetime($lifetime)
	{
		$this->nativeQuery->setResultCacheLifetime($lifetime);
		return $this;
	}



	public function getResultCacheLifetime()
	{
		return $this->nativeQuery->getResultCacheLifetime();
	}



	public function expireResultCache($expire = true)
	{
		$this->nativeQuery->expireResultCache($expire);
		return $this;
	}



	public function getExpireResultCache()
	{
		return $this->nativeQuery->getExpireResultCache();
	}



	public function getQueryCacheProfile()
	{
		return $this->nativeQuery->getQueryCacheProfile();
	}



	public function setFetchMode($class, $assocName, $fetchMode)
	{
		$this->nativeQuery->setFetchMode($class, $assocName, $fetchMode);
		return $this;
	}



	public function setHydrationMode($hydrationMode)
	{
		$this->nativeQuery->setHydrationMode($hydrationMode);
		return $this;
	}



	public function getHydrationMode()
	{
		return $this->nativeQuery->getHydrationMode();
	}



	public function setHint($name, $value)
	{
		$this->nativeQuery->setHint($name, $value);
		return $this;
	}



	public function getHint($name)
	{
		return $this->nativeQuery->getHint($name);
	}



	public function hasHint($name)
	{
		return $this->nativeQuery->hasHint($name);
	}



	public function getHints()
	{
		return $this->nativeQuery->getHints();
	}



	protected function getHydrationCacheId()
	{
		return $this->nativeQuery->getHydrationCacheId();
	}



	public function setResultCacheId($id)
	{
		$this->nativeQuery->setResultCacheId($id);
		return $this;
	}



	public function getResultCacheId()
	{
		return $this->nativeQuery->getResultCacheId();
	}



	protected function getHash()
	{
		return $this->nativeQuery->getHash();
	}



	protected function _doExecute()
	{
		throw new NotImplementedException;
	}



	public function __clone()
	{
		$this->nativeQuery = clone $this->nativeQuery;
	}

}
