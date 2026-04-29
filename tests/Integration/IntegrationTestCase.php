<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class IntegrationTestCase extends TestCase
{
    protected static ?KernelInterface $kernel = null;
    protected static ?ContainerInterface $container = null;

    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        if (null === self::$kernel) {
            self::$kernel = new Kernel('test', true);
            self::$kernel->boot();
            self::$container = self::$kernel->getContainer();
        }

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::$container->get('doctrine');
        $doctrine->resetManager();
        $this->em = $doctrine->getManager();

        // Clean user-related data to keep integration tests isolated
        $connection = $this->em->getConnection();
        $connection->executeStatement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    }

    protected function tearDown():void
    {
        $this->em->close();
    }

    protected function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
