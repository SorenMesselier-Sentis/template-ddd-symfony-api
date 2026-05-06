<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

interface CommandBusInterface
{
    public function dispatch(Command $command): mixed;
}
