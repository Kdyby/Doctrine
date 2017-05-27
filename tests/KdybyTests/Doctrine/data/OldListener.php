<?php

namespace KdybyTests\Doctrine;

use Kdyby;

class OldListener implements Kdyby\Events\Subscriber
{

	public $calls = [];

	public function getSubscribedEvents()
	{
		return ['onFlush'];
	}

	public function onFlush()
	{
		$this->calls[] = func_get_args();
	}

}
