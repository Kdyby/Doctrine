<?php

use KdybyTests\Doctrine\CmsOrder;

require_once __DIR__ . '/../../../../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
	echo "wrong sapi, srybro\n";
	exit(1);
}

\Tracy\Debugger::enable(FALSE, getenv('TEMP_DIR'));

if ($sessionId = getenv('SESSION_ID')) {
	$GLOBALS['_COOKIE'][session_name()] = $sessionId;
}

$TEMP_DIR = empty(getenv('TEMP_DIR')) ? __DIR__ . '/../../../tmp' : getenv('TEMP_DIR');

$config = new Nette\Configurator();
$config->setTempDirectory($TEMP_DIR);
$config->addParameters([
	'appDir' => __DIR__,
	'wwwDir' => __DIR__,
]);
$config->addConfig(__DIR__ . '/../../nette-reset.neon');
$config->addConfig(__DIR__ . '/../config/proxiesSessionAutoloading.neon');

$container = $config->createContainer();

// requires disabled autostart
/** @var \Nette\Http\Session $session */
$session = $container->getByType(\Nette\Http\Session::class);

if ($_SERVER['argv'][1] === 'compile') {
	$session->start();

	/** @var \Doctrine\ORM\EntityManager $em */
	$em = $container->getByType(\Doctrine\ORM\EntityManager::class);
	$allMetadata = $em->getMetadataFactory()->getAllMetadata();
	echo 'compiled,';

	$em->getProxyFactory()->generateProxyClasses($allMetadata);
	echo 'proxies generated,';

	/** @var \Doctrine\ORM\Tools\SchemaTool $schemaTool */
	$schemaTool = $container->getByType(\Doctrine\ORM\Tools\SchemaTool::class);
	$schemaTool->createSchema($allMetadata);
	echo "schema generated\n";

	echo $session->getId();

} elseif ($_SERVER['argv'][1] === 'store') {
	$session->start();

	/** @var \Doctrine\ORM\EntityManager $em */
	$em = $container->getByType(\Doctrine\ORM\EntityManager::class);

	$em->persist($order = new CmsOrder());
	$order->status = 'new';
	$em->flush();
	$orderId = $order->id;
	$em->clear();

	$orderSession = $session->getSection('order');
	/** @var KdybyTests\Doctrine\CmsOrder $proxy */
	$proxy = $em->getReference(\KdybyTests\Doctrine\CmsOrder::class, $orderId);
	$proxy->dummyMethodForProxyInitialize();
	$orderSession->entity = $proxy;

	echo \Tracy\Dumper::toText($proxy);

} elseif ($_SERVER['argv'][1] === 'read') {
	$session->start();

	$orderSession = $session->getSection('order');
	echo \Tracy\Dumper::toText($orderSession->entity);
}

$session->close();
