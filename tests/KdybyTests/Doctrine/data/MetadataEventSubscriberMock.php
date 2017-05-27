<?php

namespace KdybyTests\Doctrine;

use Doctrine;
use Kdyby\Doctrine\Events;

class MetadataEventSubscriberMock implements Doctrine\Common\EventSubscriber
{

	/**
	 * @var \Doctrine\ORM\Event\LoadClassMetadataEventArgs[]
	 */
	public $loadClassMetadataCalled = [];

	/**
	 * @var \Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs[]
	 */
	public $onClassMetadataNotFoundCalled = [];

	public function getSubscribedEvents()
	{
		return [
			Events::loadClassMetadata,
			Events::onClassMetadataNotFound,
		];
	}

	public function loadClassMetadata(Doctrine\ORM\Event\LoadClassMetadataEventArgs $args)
	{
		$this->loadClassMetadataCalled[] = $args;
	}

	public function onClassMetadataNotFound(Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs $args)
	{
		$this->onClassMetadataNotFoundCalled[] = $args;
	}
}
