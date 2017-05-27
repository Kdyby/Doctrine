<?php

namespace KdybyTests\Doctrine;

use Kdyby;

class NewListener implements Kdyby\Events\Subscriber
{

	public $calls = [];

	public function getSubscribedEvents()
	{
		return [Kdyby\Doctrine\Events::onFlush];
	}

	public function onFlush()
	{
		$this->calls[] = func_get_args();
	}

}
