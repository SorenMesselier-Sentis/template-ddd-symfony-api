<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var ManagerRegistry $doctrine */
$doctrine = $kernel->getContainer()->get('doctrine');

/** @var ObjectManager $objectManager */
$objectManager = $doctrine->getManager();

return $objectManager;
