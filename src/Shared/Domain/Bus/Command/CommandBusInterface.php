<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

interface CommandBusInterface
{
    /**
     * @template TResult
     *
     * @param Command<TResult> $command
     *
     * @return TResult
     */
    public function dispatch(Command $command): mixed;
}
