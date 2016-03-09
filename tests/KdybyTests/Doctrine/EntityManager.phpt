<?php

/**
 * Test: Kdyby\Doctrine\EntityManager.
 *
 * @testCase Kdyby\Doctrine\EntityManagerTest
 * @author Tomáš Jacík <tomas@jacik.cz>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 */
class EntityManagerTest extends Tester\TestCase
{

    /**
     * @var Nette\DI\Container
     */
    protected $serviceLocator;



    protected function setUp()
    {
        $rootDir = __DIR__ . '/..';

        $config = new Nette\Configurator;
        /** @var Nette\DI\Container $container */
        $container = $config->setTempDirectory(TEMP_DIR)
            ->addConfig(__DIR__ . '/../nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22')
            ->addConfig(__DIR__ . '/config/entitymanager-decorator.neon')
            ->addParameters([
                'appDir' => $rootDir,
                'wwwDir' => $rootDir,
            ])
            ->createContainer();

        $this->serviceLocator = $container;
    }



    public function testInheritance()
    {
        Assert::same(
            $this->serviceLocator->getService('kdyby.doctrine.registry')->getConnection('default'),
            $this->serviceLocator->getByType('Kdyby\Doctrine\EntityManager')->getConnection()
        );
        Assert::same(
            $this->serviceLocator->getService('kdyby.doctrine.registry')->getConnection('remote'),
            $this->serviceLocator->getByType('KdybyTests\Doctrine\RemoteEntityManager')->getConnection()
        );
    }

}


\run(new EntityManagerTest());
