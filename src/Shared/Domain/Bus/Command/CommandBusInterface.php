<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

use App\Shared\Domain\BUS\Command\Command;

interface CommandBusInterface
{
    public function dispatch(Command $command): void;
}
