<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;

final class User
{
    /**
     * @param ClassMetadata<object> $metadata
     */
    public function __invoke(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(['name' => 'users']);

        $metadata->mapField([
            'id' => true,
            'fieldName' => 'id',
            'type' => 'uuid',
        ]);

        $metadata->mapField([
            'fieldName' => 'firstName',
            'type' => 'user_name',
            'column' => 'first_name',
        ]);

        $metadata->mapField([
            'fieldName' => 'lastName',
            'type' => 'last_name',
            'column' => 'last_name',
        ]);

        $metadata->mapField([
            'fieldName' => 'email',
            'type' => 'email',
        ]);

        $metadata->mapField([
            'fieldName' => 'password',
            'type' => 'hashed_password',
        ]);
    }
}
