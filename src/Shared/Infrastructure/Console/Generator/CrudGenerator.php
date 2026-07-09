<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\Generator;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class CrudGenerator
{
    use GeneratorTrait;

    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function generate(string $context, string $entity, SymfonyStyle $io): bool
    {
        $srcDir = $this->projectDir.'/src/'.$context;
        $testsDir = $this->projectDir.'/tests';
        $entityPath = $srcDir.'/Domain/Entity/'.$entity.'.php';

        if (!$this->filesystem->exists($srcDir)) {
            $io->error(sprintf('Bounded Context "%s" does not exist. Run make:bounded-context first.', $context));

            return false;
        }

        if ($this->filesystem->exists($entityPath)) {
            $io->error(sprintf('Entity "%s" already exists in Bounded Context "%s".', $entity, $context));

            return false;
        }

        $this->generateDomain($srcDir, $context, $entity, $io);
        $this->generateApplication($srcDir, $context, $entity, $io);
        $this->generateInfrastructure($srcDir, $context, $entity, $io);
        $this->generateTests($testsDir, $context, $entity, $io);
        $this->registerConfiguration($context, $entity, $io);
        $this->printNextSteps($context, $entity, $io);

        return true;
    }

    private function generateDomain(string $srcDir, string $context, string $entity, SymfonyStyle $io): void
    {
        $io->section('Domain');

        $files = [
            // Entity
            "Domain/Entity/{$entity}.php" => $this->templateEntity($context, $entity),

            // ValueObject
            "Domain/ValueObject/{$entity}Id.php" => $this->templateValueObjectId($context, $entity),

            // Repository
            "Domain/Repository/{$entity}RepositoryInterface.php" => $this->templateRepositoryInterface($context, $entity),

            // Event
            "Domain/Event/{$entity}Created.php" => $this->templateDomainEvent($context, $entity, 'Created'),
            "Domain/Event/{$entity}Updated.php" => $this->templateDomainEvent($context, $entity, 'Updated'),
            "Domain/Event/{$entity}Replaced.php" => $this->templateDomainEvent($context, $entity, 'Replaced'),
            "Domain/Event/{$entity}Deleted.php" => $this->templateDomainEvent($context, $entity, 'Deleted'),

            // Exception
            "Domain/Exception/{$entity}NotFoundException.php" => $this->templateNotFoundException($context, $entity),
            "Domain/Exception/{$entity}AlreadyExistsException.php" => $this->templateAlreadyExistsException($context, $entity),

            // Status
            "Domain/ValueObject/{$entity}Status.php" => $this->templateStatusEnum($context, $entity),
        ];

        $this->writeFiles($this->filesystem, $srcDir, $files, $io);
    }

    private function generateApplication(string $srcDir, string $context, string $entity, SymfonyStyle $io): void
    {
        $io->section('Application');

        $files = [
            // Create
            "Application/Command/Create{$entity}/Create{$entity}Command.php" => $this->templateCommand($context, $entity, 'Create'),
            "Application/Command/Create{$entity}/Create{$entity}CommandHandler.php" => $this->templateCreateCommandHandler($context, $entity),

            // Update (PATCH)
            "Application/Command/Update{$entity}/Update{$entity}Command.php" => $this->templateCommand($context, $entity, 'Update'),
            "Application/Command/Update{$entity}/Update{$entity}CommandHandler.php" => $this->templateUpdateCommandHandler($context, $entity),

            // Replace (PUT)
            "Application/Command/Replace{$entity}/Replace{$entity}Command.php" => $this->templateCommand($context, $entity, 'Replace'),
            "Application/Command/Replace{$entity}/Replace{$entity}CommandHandler.php" => $this->templateReplaceCommandHandler($context, $entity),

            // Delete
            "Application/Command/Delete{$entity}/Delete{$entity}Command.php" => $this->templateCommand($context, $entity, 'Delete'),
            "Application/Command/Delete{$entity}/Delete{$entity}CommandHandler.php" => $this->templateDeleteCommandHandler($context, $entity),

            // Get one
            "Application/Query/Get{$entity}/Get{$entity}Query.php" => $this->templateQuery($context, $entity),
            "Application/Query/Get{$entity}/Get{$entity}QueryHandler.php" => $this->templateQueryHandler($context, $entity),
            "Application/Query/Get{$entity}/{$entity}Response.php" => $this->templateResponse($context, $entity),

            // Get collection
            "Application/Query/Get{$entity}s/Get{$entity}sQuery.php" => $this->templateCollectionQuery($context, $entity),
            "Application/Query/Get{$entity}s/Get{$entity}sQueryHandler.php" => $this->templateCollectionQueryHandler($context, $entity),
            "Application/Query/Get{$entity}s/{$entity}sResponse.php" => $this->templateCollectionResponse($context, $entity),
            "Application/Query/Get{$entity}s/{$entity}ItemResponse.php" => $this->templateItemResponse($context, $entity),
        ];

        $this->writeFiles($this->filesystem, $srcDir, $files, $io);
    }

    private function generateInfrastructure(string $srcDir, string $context, string $entity, SymfonyStyle $io): void
    {
        $io->section('Infrastructure');

        $lower = strtolower($entity);
        $table = $lower.'s';

        $files = [
            // Doctrine Mapping
            "Infrastructure/Persistence/Doctrine/Mapping/{$entity}.orm.xml" => $this->templateDoctrineMapping($context, $entity, $table),

            // Doctrine Repository
            "Infrastructure/Persistence/Doctrine/Repository/Doctrine{$entity}Repository.php" => $this->templateDoctrineRepository($context, $entity),

            // Doctrine Types
            "Infrastructure/Persistence/Doctrine/Type/{$entity}IdType.php" => $this->templateDoctrineType($context, $entity),

            // Http Controllers
            "Infrastructure/Http/Controller/Create{$entity}Controller.php" => $this->templateCreateController($context, $entity, $table),
            "Infrastructure/Http/Controller/Get{$entity}Controller.php" => $this->templateGetController($context, $entity, $table),
            "Infrastructure/Http/Controller/Get{$entity}sController.php" => $this->templateCollectionController($context, $entity, $table),
            "Infrastructure/Http/Controller/Patch{$entity}Controller.php" => $this->templatePatchController($context, $entity, $table),
            "Infrastructure/Http/Controller/Put{$entity}Controller.php" => $this->templatePutController($context, $entity, $table),
            "Infrastructure/Http/Controller/Delete{$entity}Controller.php" => $this->templateDeleteController($context, $entity, $table),

            // Http Requests
            "Infrastructure/Http/Request/Patch{$entity}Request.php" => $this->templatePatchRequest($context, $entity),

            // Fixture
            "Infrastructure/Fixture/{$entity}Fixture.php" => $this->templateFixture($context, $entity),

            // Messaging
            "Infrastructure/Messaging/{$entity}CreatedMessageHandler.php" => $this->templateMessageHandler($context, $entity),
        ];

        $this->writeFiles($this->filesystem, $srcDir, $files, $io);
    }

    private function generateTests(string $testsDir, string $context, string $entity, SymfonyStyle $io): void
    {
        $io->section('Tests');

        $files = [
            // Unit
            "Unit/{$context}/Domain/Entity/{$entity}Test.php" => $this->templateEntityTest($context, $entity),
            "Unit/{$context}/Domain/Mother/{$entity}Mother.php" => $this->templateMother($context, $entity),
            "Unit/{$context}/Domain/Mother/{$entity}IdMother.php" => $this->templateIdMother($context, $entity),
            "Unit/{$context}/Application/Create{$entity}CommandHandlerTest.php" => $this->templateCreateCommandHandlerTest($context, $entity),
            "Unit/{$context}/Application/Update{$entity}CommandHandlerTest.php" => $this->templateUpdateCommandHandlerTest($context, $entity),
            "Unit/{$context}/Application/Delete{$entity}CommandHandlerTest.php" => $this->templateDeleteCommandHandlerTest($context, $entity),

            // Integration
            "Integration/{$context}/Infrastructure/Doctrine{$entity}RepositoryTest.php" => $this->templateRepositoryTest($context, $entity),
        ];

        $this->writeFiles($this->filesystem, $testsDir, $files, $io);
    }

    private function openApiTag(string $context): string
    {
        return $context.'s';
    }

    // =========================================================
    // Templates — Domain
    // =========================================================
    private function templateEntity(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\Entity;

        use App\\Shared\\Domain\\Bus\\Event\\DomainEvent;
        use App\\{$context}\\Domain\\Event\\{$entity}Created;
        use App\\{$context}\\Domain\\Event\\{$entity}Deleted;
        use App\\{$context}\\Domain\\Event\\{$entity}Replaced;
        use App\\{$context}\\Domain\\Event\\{$entity}Updated;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Status;

        final class {$entity}
        {
            /** @var DomainEvent[] */
            private array \$domainEvents = [];

            private function __construct(
                private readonly {$entity}Id \$id,
                private {$entity}Status \$status,
                private \\DateTimeImmutable \$createdAt,
                private \\DateTimeImmutable \$updatedAt,
            ) {}

            public static function create({$entity}Id \$id): self
            {
                \$now    = new \\DateTimeImmutable();
                \$entity = new self(\$id, {$entity}Status::Active, \$now, \$now);

                \$entity->record(new {$entity}Created(aggregateId: \$id->value()));

                return \$entity;
            }

            public function update(): void
            {
                \$this->touch();
                \$this->record(new {$entity}Updated(aggregateId: \$this->id->value()));
            }

            public function replace(): void
            {
                \$this->touch();
                \$this->record(new {$entity}Replaced(aggregateId: \$this->id->value()));
            }

            public function delete(): void
            {
                \$this->touch();
                \$this->record(new {$entity}Deleted(aggregateId: \$this->id->value()));
            }

            public function pullDomainEvents(): array
            {
                \$events            = \$this->domainEvents;
                \$this->domainEvents = [];

                return \$events;
            }

            private function record(DomainEvent \$event): void
            {
                \$this->domainEvents[] = \$event;
            }

            private function touch(): void
            {
                \$this->updatedAt = new \\DateTimeImmutable();
            }

            public function id(): {$entity}Id                    { return \$this->id; }
            public function status(): {$entity}Status           { return \$this->status; }
            public function createdAt(): \\DateTimeImmutable { return \$this->createdAt; }
            public function updatedAt(): \\DateTimeImmutable { return \$this->updatedAt; }
        }
        PHP;
    }

    private function templateStatusEnum(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\ValueObject;

        enum {$entity}Status: string
        {
            case Active   = 'active';
            case Inactive = 'inactive';
            case Deleted  = 'deleted';
        }
        PHP;
    }

    private function templateValueObjectId(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\ValueObject;

        use App\\Shared\\Domain\\ValueObject\\Uuid;

        final class {$entity}Id extends Uuid {}
        PHP;
    }

    private function templateRepositoryInterface(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\Repository;

        use App\\Shared\\Domain\\Filter\\Filters;
        use App\\{$context}\\Domain\\Entity\\{$entity};
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;

        interface {$entity}RepositoryInterface
        {
            public function save({$entity} \$entity): void;
            public function delete({$entity} \$entity): void;
            public function findById({$entity}Id \$id): ?{$entity};
            public function findByFilters(Filters \$filters): array;
            public function countByFilters(Filters \$filters): int;
        }
        PHP;
    }

    private function templateDomainEvent(string $context, string $entity, string $action): string
    {
        $eventName = $this->toSnakeCase($entity).'.'.strtolower($action);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\Event;

        use App\\Shared\\Domain\\Bus\\Event\\DomainEvent;

        final class {$entity}{$action} extends DomainEvent
        {
            public function __construct(string \$aggregateId)
            {
                parent::__construct(\$aggregateId);
            }

            public static function eventName(): string
            {
                return '{$eventName}';
            }
        }
        PHP;
    }

    private function templateNotFoundException(string $context, string $entity): string
    {
        $lower = $this->toSnakeCase($entity);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\Exception;

        use App\\Shared\\Domain\\Exception\\NotFoundException;

        final class {$entity}NotFoundException extends NotFoundException
        {
            public static function withId(string \$id): self
            {
                return new self(sprintf('{$entity} with id "%s" was not found.', \$id));
            }

            public function errorCode(): string
            {
                return '{$lower}.not_found';
            }
        }
        PHP;
    }

    private function templateAlreadyExistsException(string $context, string $entity): string
    {
        $lower = $this->toSnakeCase($entity);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Domain\\Exception;

        use App\\Shared\\Domain\\Exception\\AlreadyExistsException;

        final class {$entity}AlreadyExistsException extends AlreadyExistsException
        {
            public static function withField(string \$field, string \$value): self
            {
                return new self(sprintf('{$entity} with %s "%s" already exists.', \$field, \$value));
            }

            public function errorCode(): string
            {
                return '{$lower}.already_exists';
            }
        }
        PHP;
    }

    // =========================================================
    // Templates — Application
    // =========================================================

    private function templateCommand(string $context, string $entity, string $action): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Command\\{$action}{$entity};

        use App\\Shared\\Domain\\Bus\\Command\\Command;

        final class {$action}{$entity}Command implements Command
        {
            public function __construct(
                public readonly string \$id,
            ) {}
        }
        PHP;
    }

    private function templateCreateCommandHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Command\\Create{$entity};

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$context}\\Domain\\Entity\\{$entity};
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'command.bus')]
        final class Create{$entity}CommandHandler
        {
            public function __construct(
                private readonly {$entity}RepositoryInterface \$repository,
                private readonly EventBusInterface          \$eventBus,
                private readonly LoggerInterface            \$logger,
            ) {}

            public function __invoke(Create{$entity}Command \$command): void
            {
                \$this->logger->info('Creating {$entity}', ['id' => \$command->id]);

                \$entity = {$entity}::create({$entity}Id::fromString(\$command->id));

                \$this->repository->save(\$entity);
                \$this->eventBus->publish(...\$entity->pullDomainEvents());

                \$this->logger->info('{$entity} created', ['id' => \$command->id]);
            }
        }
        PHP;
    }

    private function templateUpdateCommandHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Command\\Update{$entity};

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$context}\\Domain\\Exception\\{$entity}NotFoundException;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'command.bus')]
        final class Update{$entity}CommandHandler
        {
            public function __construct(
                private readonly {$entity}RepositoryInterface \$repository,
                private readonly EventBusInterface          \$eventBus,
                private readonly LoggerInterface            \$logger,
            ) {}

            public function __invoke(Update{$entity}Command \$command): void
            {
                \$this->logger->info('Updating {$entity}', ['id' => \$command->id]);

                \$entity = \$this->repository->findById({$entity}Id::fromString(\$command->id));

                if (null === \$entity) {
                    throw {$entity}NotFoundException::withId(\$command->id);
                }

                \$entity->update();

                \$this->repository->save(\$entity);
                \$this->eventBus->publish(...\$entity->pullDomainEvents());

                \$this->logger->info('{$entity} updated', ['id' => \$command->id]);
            }
        }
        PHP;
    }

    private function templateReplaceCommandHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Command\\Replace{$entity};

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$context}\\Domain\\Exception\\{$entity}NotFoundException;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'command.bus')]
        final class Replace{$entity}CommandHandler
        {
            public function __construct(
                private readonly {$entity}RepositoryInterface \$repository,
                private readonly EventBusInterface          \$eventBus,
                private readonly LoggerInterface            \$logger,
            ) {}

            public function __invoke(Replace{$entity}Command \$command): void
            {
                \$this->logger->info('Replacing {$entity}', ['id' => \$command->id]);

                \$entity = \$this->repository->findById({$entity}Id::fromString(\$command->id));

                if (null === \$entity) {
                    throw {$entity}NotFoundException::withId(\$command->id);
                }

                \$entity->replace();

                \$this->repository->save(\$entity);
                \$this->eventBus->publish(...\$entity->pullDomainEvents());

                \$this->logger->info('{$entity} replaced', ['id' => \$command->id]);
            }
        }
        PHP;
    }

    private function templateDeleteCommandHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Command\\Delete{$entity};

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$context}\\Domain\\Exception\\{$entity}NotFoundException;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'command.bus')]
        final class Delete{$entity}CommandHandler
        {
            public function __construct(
                private readonly {$entity}RepositoryInterface \$repository,
                private readonly EventBusInterface          \$eventBus,
                private readonly LoggerInterface            \$logger,
            ) {}

            public function __invoke(Delete{$entity}Command \$command): void
            {
                \$this->logger->info('Deleting {$entity}', ['id' => \$command->id]);

                \$entity = \$this->repository->findById({$entity}Id::fromString(\$command->id));

                if (null === \$entity) {
                    throw {$entity}NotFoundException::withId(\$command->id);
                }

                \$entity->delete();
                \$events = \$entity->pullDomainEvents();

                \$this->repository->delete(\$entity);
                \$this->eventBus->publish(...\$events);

                \$this->logger->info('{$entity} deleted', ['id' => \$command->id]);
            }
        }
        PHP;
    }

    private function templateQuery(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity};

        use App\\Shared\\Domain\\Bus\\Query\\Query;

        final class Get{$entity}Query implements Query
        {
            public function __construct(
                public readonly string \$id,
            ) {}
        }
        PHP;
    }

    private function templateQueryHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity};

        use App\\{$context}\\Domain\\Exception\\{$entity}NotFoundException;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'query.bus')]
        final class Get{$entity}QueryHandler
        {
            public function __construct(
                private readonly {$entity}RepositoryInterface \$repository,
            ) {}

            public function __invoke(Get{$entity}Query \$query): {$entity}Response
            {
                \$entity = \$this->repository->findById({$entity}Id::fromString(\$query->id));

                if (\$entity === null) {
                    throw {$entity}NotFoundException::withId(\$query->id);
                }

                return new {$entity}Response(\$entity);
            }
        }
        PHP;
    }

    private function templateResponse(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity};

        use App\\Shared\\Domain\\Bus\\Query\\Response;
        use App\\{$context}\\Domain\\Entity\\{$entity};

        final class {$entity}Response implements Response
        {
            public readonly string \$id;
            public readonly string \$createdAt;
            public readonly string \$updatedAt;

            public function __construct({$entity} \$entity)
            {
                \$this->id        = \$entity->id()->value();
                \$this->createdAt = \$entity->createdAt()->format(\\DateTimeInterface::ATOM);
                \$this->updatedAt = \$entity->updatedAt()->format(\\DateTimeInterface::ATOM);
            }
        }
        PHP;
    }

    private function templateCollectionQuery(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity}s;

        use App\\Shared\\Domain\\Bus\\Query\\Query;
        use App\\Shared\\Domain\\Filter\\Filters;

        final class Get{$entity}sQuery implements Query
        {
            public function __construct(
                public readonly Filters \$filters,
            ) {}
        }
        PHP;
    }

    private function templateCollectionQueryHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity}s;

        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'query.bus')]
        final class Get{$entity}sQueryHandler
        {
            public function __construct(
                private readonly {$entity}RepositoryInterface \$repository,
            ) {}

            public function __invoke(Get{$entity}sQuery \$query): {$entity}sResponse
            {
                \$entities = \$this->repository->findByFilters(\$query->filters);
                \$total    = \$this->repository->countByFilters(\$query->filters);

                return new {$entity}sResponse(
                    items:   array_map(fn(\$e) => new {$entity}ItemResponse(\$e), \$entities),
                    total:   \$total,
                    page:    \$query->filters->pagination->page,
                    limit: \$query->filters->pagination->limit,
                );
            }
        }
        PHP;
    }

    private function templateCollectionResponse(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity}s;

        use App\\Shared\\Domain\\Bus\\Query\\Response;

        final class {$entity}sResponse implements Response
        {
            public function __construct(
                public readonly array \$items,
                public readonly int   \$total,
                public readonly int   \$page,
                public readonly int   \$limit,
            ) {}
        }
        PHP;
    }

    private function templateItemResponse(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Application\\Query\\Get{$entity}s;

        use App\\Shared\\Domain\\Bus\\Query\\Response;
        use App\\{$context}\\Domain\\Entity\\{$entity};

        final class {$entity}ItemResponse implements Response
        {
            public readonly string \$id;
            public readonly string \$createdAt;

            public function __construct({$entity} \$entity)
            {
                \$this->id        = \$entity->id()->value();
                \$this->createdAt = \$entity->createdAt()->format(\\DateTimeInterface::ATOM);
            }
        }
        PHP;
    }

    // =========================================================
    // Templates — Infrastructure
    // =========================================================
    private function templateDoctrineMapping(string $context, string $entity, string $table): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping
            xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd"
        >
            <entity name="App\\{$context}\\Domain\\Entity\\{$entity}" table="{$table}">

                <id name="id" type="{$this->toSnakeCase($entity)}_id" column="id"/>

                <field name="status" type="string" column="status" length="20" nullable="false"/>
                <field name="createdAt" type="datetime_immutable" column="created_at" nullable="false"/>
                <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="false"/>

            </entity>
        </doctrine-mapping>
        XML;
    }

    private function templateDoctrineRepository(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Persistence\\Doctrine\\Repository;

        use App\\Shared\\Domain\\Filter\\Filters;
        use App\\Shared\\Infrastructure\\Persistence\\Doctrine\\DoctrineFilterApplier;
        use App\\Shared\\Infrastructure\\Persistence\\Doctrine\\Trait\\DoctrineRepositoryTrait;
        use App\\{$context}\\Domain\\Entity\\{$entity};
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Doctrine\\ORM\\EntityManagerInterface;

        final class Doctrine{$entity}Repository implements {$entity}RepositoryInterface
        {
            use DoctrineRepositoryTrait;

            public function __construct(
                private readonly EntityManagerInterface \$em,
            ) {}

            public function save({$entity} \$entity): void
            {
                \$this->saveEntity(\$this->em, \$entity);
            }

            public function delete({$entity} \$entity): void
            {
                \$this->deleteEntity(\$this->em, \$entity);
            }

            public function findById({$entity}Id \$id): ?{$entity}
            {
                return \$this->em->find({$entity}::class, \$id);
            }

            public function findByFilters(Filters \$filters): array
            {
                \$qb = \$this->em->getRepository({$entity}::class)
                    ->createQueryBuilder('e');

                DoctrineFilterApplier::apply(\$qb, \$filters, 'e');

                return \$qb->getQuery()->getResult();
            }

            public function countByFilters(Filters \$filters): int
            {
                \$qb = \$this->em->getRepository({$entity}::class)
                    ->createQueryBuilder('e')
                    ->select('COUNT(e.id)');

                DoctrineFilterApplier::applyFilters(\$qb, \$filters, 'e');

                return (int) \$qb->getQuery()->getSingleScalarResult();
            }
        }
        PHP;
    }

    private function templateDoctrineType(string $context, string $entity): string
    {
        $typeName = $this->toSnakeCase($entity).'_id';

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Persistence\\Doctrine\\Type;

        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Doctrine\\DBAL\\Platforms\\AbstractPlatform;
        use Doctrine\\DBAL\\Types\\Type;

        final class {$entity}IdType extends Type
        {
            public const NAME = '{$typeName}';

            public function getSQLDeclaration(array \$column, AbstractPlatform \$platform): string
            {
                return 'UUID';
            }

            public function convertToPHPValue(mixed \$value, AbstractPlatform \$platform): ?{$entity}Id
            {
                return \$value ? {$entity}Id::fromString(\$value) : null;
            }

            public function convertToDatabaseValue(mixed \$value, AbstractPlatform \$platform): ?string
            {
                return \$value instanceof {$entity}Id ? \$value->value() : \$value;
            }

            public function getName(): string
            {
                return self::NAME;
            }

            public function requiresSQLCommentHint(AbstractPlatform \$platform): bool
            {
                return true;
            }
        }
        PHP;
    }

    private function templateCreateController(string $context, string $entity, string $table): string
    {
        $tag = $this->openApiTag($context);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Command\\CommandBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$context}\\Application\\Command\\Create{$entity}\\Create{$entity}Command;
        use OpenApi\\Attributes as OA;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\Routing\\Attribute\\Route;
        use Symfony\\Component\\Uid\\Uuid;

        #[Route('/{$table}', methods: ['POST'])]
        #[OA\\Post(
            path: '/api/v1/{$table}',
            operationId: 'post{$entity}s',
            summary: 'Create a {$entity}',
            tags: ['{$tag}'],
        )]
        #[OA\\Response(
            response: 201,
            description: '{$entity} created',
            content: new OA\\JsonContent(
                properties: [
                    new OA\\Property(
                        property: 'data',
                        properties: [new OA\\Property(property: 'id', type: 'string', format: 'uuid')],
                        type: 'object',
                    ),
                ],
            ),
        )]
        final class Create{$entity}Controller
        {
            public function __construct(
                private readonly CommandBusInterface \$commandBus,
                private readonly ApiResponse         \$apiResponse,
            ) {}

            public function __invoke(): JsonResponse
            {
                \$id = Uuid::v4()->toRfc4122();

                \$this->commandBus->dispatch(new Create{$entity}Command(id: \$id));

                return \$this->apiResponse->created(['id' => \$id]);
            }
        }
        PHP;
    }

    private function templateGetController(string $context, string $entity, string $table): string
    {
        $tag = $this->openApiTag($context);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Query\\QueryBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$context}\\Application\\Query\\Get{$entity}\\Get{$entity}Query;
        use App\\{$context}\\Application\\Query\\Get{$entity}\\{$entity}Response;
        use OpenApi\\Attributes as OA;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('/{$table}/{id}', methods: ['GET'])]
        #[OA\\Get(
            path: '/api/v1/{$table}/{id}',
            operationId: 'get{$entity}',
            summary: 'Get a {$entity}',
            tags: ['{$tag}'],
        )]
        #[OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'string', format: 'uuid'))]
        #[OA\\Response(response: 200, description: '{$entity} found')]
        #[OA\\Response(response: 404, description: '{$entity} not found')]
        final class Get{$entity}Controller
        {
            public function __construct(
                private readonly QueryBusInterface \$queryBus,
                private readonly ApiResponse       \$apiResponse,
            ) {}

            public function __invoke(string \$id): JsonResponse
            {
                /** @var {$entity}Response \$response */
                \$response = \$this->queryBus->ask(new Get{$entity}Query(\$id));

                return \$this->apiResponse->success(\$response);
            }
        }
        PHP;
    }

    private function templatePatchController(string $context, string $entity, string $table): string
    {
        $tag = $this->openApiTag($context);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Command\\CommandBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$context}\\Application\\Command\\Update{$entity}\\Update{$entity}Command;
        use OpenApi\\Attributes as OA;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('/{$table}/{id}', methods: ['PATCH'])]
        #[OA\\Patch(
            path: '/api/v1/{$table}/{id}',
            operationId: 'patch{$entity}',
            summary: 'Partially update a {$entity}',
            tags: ['{$tag}'],
        )]
        #[OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'string', format: 'uuid'))]
        #[OA\\Response(response: 204, description: '{$entity} updated')]
        #[OA\\Response(response: 404, description: '{$entity} not found')]
        final class Patch{$entity}Controller
        {
            public function __construct(
                private readonly CommandBusInterface \$commandBus,
                private readonly ApiResponse         \$apiResponse,
            ) {}

            public function __invoke(string \$id): JsonResponse
            {
                \$this->commandBus->dispatch(new Update{$entity}Command(id: \$id));

                return \$this->apiResponse->noContent();
            }
        }
        PHP;
    }

    private function templatePutController(string $context, string $entity, string $table): string
    {
        $tag = $this->openApiTag($context);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Command\\CommandBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$context}\\Application\\Command\\Replace{$entity}\\Replace{$entity}Command;
        use OpenApi\\Attributes as OA;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('/{$table}/{id}', methods: ['PUT'])]
        #[OA\\Put(
            path: '/api/v1/{$table}/{id}',
            operationId: 'put{$entity}',
            summary: 'Replace a {$entity}',
            tags: ['{$tag}'],
        )]
        #[OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'string', format: 'uuid'))]
        #[OA\\Response(response: 204, description: '{$entity} replaced')]
        #[OA\\Response(response: 404, description: '{$entity} not found')]
        final class Put{$entity}Controller
        {
            public function __construct(
                private readonly CommandBusInterface \$commandBus,
                private readonly ApiResponse         \$apiResponse,
            ) {}

            public function __invoke(string \$id): JsonResponse
            {
                \$this->commandBus->dispatch(new Replace{$entity}Command(id: \$id));

                return \$this->apiResponse->noContent();
            }
        }
        PHP;
    }

    private function templateDeleteController(string $context, string $entity, string $table): string
    {
        $tag = $this->openApiTag($context);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Command\\CommandBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$context}\\Application\\Command\\Delete{$entity}\\Delete{$entity}Command;
        use OpenApi\\Attributes as OA;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('/{$table}/{id}', methods: ['DELETE'])]
        #[OA\\Delete(
            path: '/api/v1/{$table}/{id}',
            operationId: 'delete{$entity}',
            summary: 'Delete a {$entity}',
            tags: ['{$tag}'],
        )]
        #[OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'string', format: 'uuid'))]
        #[OA\\Response(response: 204, description: '{$entity} deleted')]
        #[OA\\Response(response: 404, description: '{$entity} not found')]
        final class Delete{$entity}Controller
        {
            public function __construct(
                private readonly CommandBusInterface \$commandBus,
                private readonly ApiResponse         \$apiResponse,
            ) {}

            public function __invoke(string \$id): JsonResponse
            {
                \$this->commandBus->dispatch(new Delete{$entity}Command(\$id));

                return \$this->apiResponse->noContent();
            }
        }
        PHP;
    }

    private function templateCollectionController(string $context, string $entity, string $table): string
    {
        $tag = $this->openApiTag($context);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Query\\QueryBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Filter\\FiltersBuilder;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$context}\\Application\\Query\\Get{$entity}s\\Get{$entity}sQuery;
        use App\\{$context}\\Application\\Query\\Get{$entity}s\\{$entity}sResponse;
        use OpenApi\\Attributes as OA;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\HttpFoundation\\Request;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('/{$table}', methods: ['GET'])]
        #[OA\\Get(
            path: '/api/v1/{$table}',
            operationId: 'get{$entity}s',
            summary: 'List {$entity}s',
            tags: ['{$tag}'],
        )]
        #[OA\\Parameter(name: 'page', in: 'query', schema: new OA\\Schema(type: 'integer', default: 1, minimum: 1))]
        #[OA\\Parameter(name: 'limit', in: 'query', schema: new OA\\Schema(type: 'integer', default: 20, maximum: 100, minimum: 1))]
        #[OA\\Response(response: 200, description: 'Paginated list of {$entity}s')]
        final class Get{$entity}sController
        {
            private const ALLOWED_FILTERS = [
                // 'field' => 'equal|range|in',
            ];

            public function __construct(
                private readonly QueryBusInterface \$queryBus,
                private readonly ApiResponse       \$apiResponse,
            ) {}

            public function __invoke(Request \$request): JsonResponse
            {
                \$filters = FiltersBuilder::fromRequest(\$request, self::ALLOWED_FILTERS);

                /** @var {$entity}sResponse \$result */
                \$result = \$this->queryBus->ask(new Get{$entity}sQuery(\$filters));

                return \$this->apiResponse->paginated(
                    data:    \$result->items,
                    total:   \$result->total,
                    page:    \$result->page,
                    limit:   \$result->limit,
                    request: \$request,
                );
            }
        }
        PHP;
    }

    private function templateFixture(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Fixture;

        use App\\{$context}\\Domain\\Entity\\{$entity};
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use Doctrine\\Bundle\\FixturesBundle\\Fixture;
        use Doctrine\\Persistence\\ObjectManager;

        final class {$entity}Fixture extends Fixture
        {
            public const REFERENCE = '{$this->toSnakeCase($entity)}.default';

            public function load(ObjectManager \$manager): void
            {
                \$entity = {$entity}::create(id: {$entity}Id::random());

                \$manager->persist(\$entity);
                \$manager->flush();

                \$this->addReference(self::REFERENCE, \$entity);
            }
        }
        PHP;
    }

    private function templateMessageHandler(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Messaging;

        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$context}\\Domain\\Event\\{$entity}Created;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'event.bus')]
        final class {$entity}CreatedMessageHandler
        {
            public function __construct(
                private readonly LoggerInterface \$logger,
            ) {}

            public function __invoke({$entity}Created \$event): void
            {
                \$this->logger->info('{$entity} created event received', [
                    'aggregateId' => \$event->aggregateId(),
                ]);

                // TODO: implement side effects
            }
        }
        PHP;
    }

    // =========================================================
    // Templates — Tests
    // =========================================================

    private function templateEntityTest(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$context}\\Domain\\Entity;

        use App\\Tests\\Unit\\UnitTestCase;
        use App\\Tests\\Unit\\{$context}\\Domain\\Mother\\{$entity}Mother;
        use App\\{$context}\\Domain\\Event\\{$entity}Created;
        use App\\{$context}\\Domain\\Event\\{$entity}Deleted;
        use App\\{$context}\\Domain\\Event\\{$entity}Replaced;
        use App\\{$context}\\Domain\\Event\\{$entity}Updated;

        final class {$entity}Test extends UnitTestCase
        {
            public function test_it_creates_a_{$this->toSnakeCase($entity)}(): void
            {
                \$entity = {$entity}Mother::create();
                \$events = \$entity->pullDomainEvents();

                \$this->assertCount(1, \$events);
                \$this->assertInstanceOf({$entity}Created::class, \$events[0]);
            }

            public function test_it_records_an_updated_event(): void
            {
                \$entity = {$entity}Mother::create();
                \$entity->pullDomainEvents();

                \$entity->update();
                \$events = \$entity->pullDomainEvents();

                \$this->assertCount(1, \$events);
                \$this->assertInstanceOf({$entity}Updated::class, \$events[0]);
            }

            public function test_it_records_a_replaced_event(): void
            {
                \$entity = {$entity}Mother::create();
                \$entity->pullDomainEvents();

                \$entity->replace();
                \$events = \$entity->pullDomainEvents();

                \$this->assertCount(1, \$events);
                \$this->assertInstanceOf({$entity}Replaced::class, \$events[0]);
            }

            public function test_it_records_a_deleted_event(): void
            {
                \$entity = {$entity}Mother::create();
                \$entity->pullDomainEvents();

                \$entity->delete();
                \$events = \$entity->pullDomainEvents();

                \$this->assertCount(1, \$events);
                \$this->assertInstanceOf({$entity}Deleted::class, \$events[0]);
            }

            public function test_it_clears_domain_events_after_pull(): void
            {
                \$entity = {$entity}Mother::create();
                \$entity->pullDomainEvents();

                \$this->assertEmpty(\$entity->pullDomainEvents());
            }
        }
        PHP;
    }

    private function templateMother(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$context}\\Domain\\Mother;

        use App\\{$context}\\Domain\\Entity\\{$entity};
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;

        final class {$entity}Mother
        {
            public static function create(?{$entity}Id \$id = null): {$entity}
            {
                return {$entity}::create(id: \$id ?? {$entity}IdMother::random());
            }
        }
        PHP;
    }

    private function templateIdMother(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$context}\\Domain\\Mother;

        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;

        final class {$entity}IdMother
        {
            public static function random(): {$entity}Id
            {
                return {$entity}Id::random();
            }

            public static function create(string \$value): {$entity}Id
            {
                return {$entity}Id::fromString(\$value);
            }
        }
        PHP;
    }

    private function templateCreateCommandHandlerTest(string $context, string $entity): string
    {
        $lower = $this->toSnakeCase($entity);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$context}\\Application;

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\Tests\\Unit\\UnitTestCase;
        use App\\{$context}\\Application\\Command\\Create{$entity}\\Create{$entity}Command;
        use App\\{$context}\\Application\\Command\\Create{$entity}\\Create{$entity}CommandHandler;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use PHPUnit\\Framework\\MockObject\\MockObject;

        final class Create{$entity}CommandHandlerTest extends UnitTestCase
        {
            /** @var {$entity}RepositoryInterface&MockObject */
            private {$entity}RepositoryInterface \$repository;

            /** @var EventBusInterface&MockObject */
            private EventBusInterface \$eventBus;

            private LoggerInterface \$logger;
            private Create{$entity}CommandHandler \$handler;

            protected function setUp(): void
            {
                \$this->repository = \$this->createMock({$entity}RepositoryInterface::class);
                \$this->eventBus   = \$this->createMock(EventBusInterface::class);
                \$this->logger     = \$this->createStub(LoggerInterface::class);

                \$this->handler = new Create{$entity}CommandHandler(
                    \$this->repository,
                    \$this->eventBus,
                    \$this->logger,
                );
            }

            public function test_it_creates_a_{$lower}(): void
            {
                \$command = new Create{$entity}Command(id: {$entity}Id::random()->value());

                \$this->repository->expects(\$this->once())->method('save');
                \$this->eventBus->expects(\$this->once())->method('publish');

                (\$this->handler)(\$command);
            }
        }
        PHP;
    }

    private function templateUpdateCommandHandlerTest(string $context, string $entity): string
    {
        $lower = $this->toSnakeCase($entity);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$context}\\Application;

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\Tests\\Unit\\UnitTestCase;
        use App\\Tests\\Unit\\{$context}\\Domain\\Mother\\{$entity}Mother;
        use App\\{$context}\\Application\\Command\\Update{$entity}\\Update{$entity}Command;
        use App\\{$context}\\Application\\Command\\Update{$entity}\\Update{$entity}CommandHandler;
        use App\\{$context}\\Domain\\Exception\\{$entity}NotFoundException;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use PHPUnit\\Framework\\MockObject\\MockObject;

        final class Update{$entity}CommandHandlerTest extends UnitTestCase
        {
            /** @var {$entity}RepositoryInterface&MockObject */
            private {$entity}RepositoryInterface \$repository;

            private EventBusInterface \$eventBus;
            private LoggerInterface \$logger;
            private Update{$entity}CommandHandler \$handler;

            protected function setUp(): void
            {
                \$this->repository = \$this->createMock({$entity}RepositoryInterface::class);
                \$this->eventBus   = \$this->createStub(EventBusInterface::class);
                \$this->logger     = \$this->createStub(LoggerInterface::class);

                \$this->handler = new Update{$entity}CommandHandler(
                    \$this->repository,
                    \$this->eventBus,
                    \$this->logger,
                );
            }

            public function test_it_updates_a_{$lower}(): void
            {
                \$entity  = {$entity}Mother::create();
                \$command = new Update{$entity}Command(id: \$entity->id()->value());

                \$this->repository
                    ->expects(\$this->once())
                    ->method('findById')
                    ->willReturn(\$entity);

                \$this->repository->expects(\$this->once())->method('save');

                (\$this->handler)(\$command);
            }

            public function test_it_throws_when_{$lower}_not_found(): void
            {
                \$this->expectException({$entity}NotFoundException::class);

                \$command = new Update{$entity}Command(id: {$entity}Mother::create()->id()->value());

                \$this->repository
                    ->expects(\$this->once())
                    ->method('findById')
                    ->willReturn(null);

                (\$this->handler)(\$command);
            }
        }
        PHP;
    }

    private function templateDeleteCommandHandlerTest(string $context, string $entity): string
    {
        $lower = $this->toSnakeCase($entity);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$context}\\Application;

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\Tests\\Unit\\UnitTestCase;
        use App\\Tests\\Unit\\{$context}\\Domain\\Mother\\{$entity}Mother;
        use App\\{$context}\\Application\\Command\\Delete{$entity}\\Delete{$entity}Command;
        use App\\{$context}\\Application\\Command\\Delete{$entity}\\Delete{$entity}CommandHandler;
        use App\\{$context}\\Domain\\Exception\\{$entity}NotFoundException;
        use App\\{$context}\\Domain\\Repository\\{$entity}RepositoryInterface;
        use PHPUnit\\Framework\\MockObject\\MockObject;

        final class Delete{$entity}CommandHandlerTest extends UnitTestCase
        {
            /** @var {$entity}RepositoryInterface&MockObject */
            private {$entity}RepositoryInterface \$repository;

            private EventBusInterface \$eventBus;
            private LoggerInterface \$logger;
            private Delete{$entity}CommandHandler \$handler;

            protected function setUp(): void
            {
                \$this->repository = \$this->createMock({$entity}RepositoryInterface::class);
                \$this->eventBus   = \$this->createStub(EventBusInterface::class);
                \$this->logger     = \$this->createStub(LoggerInterface::class);

                \$this->handler = new Delete{$entity}CommandHandler(
                    \$this->repository,
                    \$this->eventBus,
                    \$this->logger,
                );
            }

            public function test_it_deletes_a_{$lower}(): void
            {
                \$entity  = {$entity}Mother::create();
                \$command = new Delete{$entity}Command(id: \$entity->id()->value());

                /** @var EventBusInterface&MockObject \$eventBus */
                \$eventBus = \$this->createMock(EventBusInterface::class);
                \$handler = new Delete{$entity}CommandHandler(
                    \$this->repository,
                    \$eventBus,
                    \$this->logger,
                );

                \$this->repository
                    ->expects(\$this->once())
                    ->method('findById')
                    ->willReturn(\$entity);

                \$this->repository->expects(\$this->once())->method('delete');

                \$eventBus->expects(\$this->once())->method('publish');

                (\$handler)(\$command);
            }

            public function test_it_throws_when_{$lower}_not_found(): void
            {
                \$this->expectException({$entity}NotFoundException::class);

                \$command = new Delete{$entity}Command(id: {$entity}Mother::create()->id()->value());

                \$this->repository
                    ->expects(\$this->once())
                    ->method('findById')
                    ->willReturn(null);

                (\$this->handler)(\$command);
            }
        }
        PHP;
    }

    private function templateRepositoryTest(string $context, string $entity): string
    {
        $lower = $this->toSnakeCase($entity);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Integration\\{$context}\\Infrastructure;

        use App\\Tests\\Integration\\IntegrationTestCase;
        use App\\Tests\\Unit\\{$context}\\Domain\\Mother\\{$entity}Mother;
        use App\\{$context}\\Domain\\ValueObject\\{$entity}Id;
        use App\\{$context}\\Infrastructure\\Persistence\\Doctrine\\Repository\\Doctrine{$entity}Repository;

        final class Doctrine{$entity}RepositoryTest extends IntegrationTestCase
        {
            private Doctrine{$entity}Repository \$repository;

            protected function setUp(): void
            {
                parent::setUp();
                \$this->repository = static::getContainer()->get(Doctrine{$entity}Repository::class);
            }

            public function test_it_saves_and_finds_a_{$lower}(): void
            {
                \$entity = {$entity}Mother::create();
                \$this->repository->save(\$entity);

                \$found = \$this->repository->findById(\$entity->id());

                \$this->assertNotNull(\$found);
                \$this->assertTrue(\$entity->id()->equals(\$found->id()));
            }

            public function test_it_returns_null_when_not_found(): void
            {
                \$this->assertNull(\$this->repository->findById({$entity}Id::random()));
            }
        }
        PHP;
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function templatePatchRequest(string $context, string $entity): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$context}\\Infrastructure\\Http\\Request;

        use App\\Shared\\Infrastructure\\Http\\Request\\JsonRequest;

        final class Patch{$entity}Request extends JsonRequest
        {
            /** @return array<string, bool|array{required?: bool, type?: string}> */
            protected function rules(): array
            {
                return [];
            }
        }
        PHP;
    }

    private function registerConfiguration(string $context, string $entity, SymfonyStyle $io): void
    {
        $io->section('Configuration');

        $this->registerDoctrineType($context, $entity, $io);
        $this->registerRepositoryAlias($context, $entity, $io);
        $this->registerMessengerBinding($context, $entity, $io);
    }

    private function registerDoctrineType(string $context, string $entity, SymfonyStyle $io): void
    {
        $path = $this->projectDir.'/config/packages/doctrine.yaml';
        $content = file_get_contents($path);
        $typeName = $this->toSnakeCase($entity).'_id';
        $entry = sprintf(
            "            %s: App\\%s\\Infrastructure\\Persistence\\Doctrine\\Type\\%sIdType\n",
            $typeName,
            $context,
            $entity,
        );

        if (str_contains($content, $typeName.':')) {
            $io->writeln(sprintf('  <comment>skipped</comment> Doctrine type %s (already registered)', $typeName));

            return;
        }

        $updated = preg_replace(
            '/(        types:\n(?:            .+\n)+)/',
            '$1'.$entry,
            $content,
            1,
        );

        if (null === $updated || $updated === $content) {
            $io->error('Could not register Doctrine type automatically.');

            return;
        }

        file_put_contents($path, $updated);
        $io->writeln(sprintf('  <info>updated</info> config/packages/doctrine.yaml (type %s)', $typeName));
    }

    private function registerRepositoryAlias(string $context, string $entity, SymfonyStyle $io): void
    {
        $path = $this->projectDir.'/config/services.yaml';
        $content = file_get_contents($path);
        $interface = sprintf('App\\%s\\Domain\\Repository\\%sRepositoryInterface', $context, $entity);

        if (str_contains($content, $interface)) {
            $io->writeln(sprintf('  <comment>skipped</comment> Repository alias %s (already registered)', $entity));

            return;
        }

        $entry = sprintf(
            "    %s:\n        alias: App\\%s\\Infrastructure\\Persistence\\Doctrine\\Repository\\Doctrine%sRepository\n",
            $interface,
            $context,
            $entity,
        );

        $updated = preg_replace(
            '/(    # Repositories\n)/',
            '$1'.$entry,
            $content,
            1,
        );

        if (null === $updated || $updated === $content) {
            $io->error('Could not register repository alias automatically.');

            return;
        }

        file_put_contents($path, $updated);
        $io->writeln(sprintf('  <info>updated</info> config/services.yaml (repository %s)', $entity));
    }

    private function registerMessengerBinding(string $context, string $entity, SymfonyStyle $io): void
    {
        $path = $this->projectDir.'/config/packages/messenger.yaml';
        $content = file_get_contents($path);
        $lower = $this->toSnakeCase($entity);
        $queueName = 'events.'.$lower;

        if (str_contains($content, $queueName.':')) {
            $io->writeln(sprintf('  <comment>skipped</comment> Messenger binding %s (already registered)', $queueName));

            return;
        }

        $entry = sprintf(
            "                        %s:\n                            binding_keys: ['%s.#']\n",
            $queueName,
            $lower,
        );

        $updated = preg_replace(
            '/(                        events\.user:\n                            binding_keys:.+\n)/',
            '$1'.$entry,
            $content,
            1,
        );

        if (null === $updated || $updated === $content) {
            $io->error('Could not register Messenger binding automatically.');

            return;
        }

        file_put_contents($path, $updated);
        $io->writeln(sprintf('  <info>updated</info> config/packages/messenger.yaml (binding %s.#)', $lower));
    }

    private function printNextSteps(string $context, string $entity, SymfonyStyle $io): void
    {
        $lower = $this->toSnakeCase($entity);

        $io->section('Next steps');
        $io->text('Add to config/packages/doctrine.yaml if not auto-registered:');
        $io->block([
            'dbal:',
            '    types:',
            "        {$lower}_id: App\\{$context}\\Infrastructure\\Persistence\\Doctrine\\Type\\{$entity}IdType",
        ], null, 'fg=cyan');
        $io->listing([
            'Add your fields to the entity, repository interface, and XML mapping',
            'Add request DTOs and validation when you introduce writable fields',
            sprintf('Run <info>make db-diff</info> to generate the migration'),
            sprintf('Run <info>make db-migrate</info> to apply it'),
        ]);
    }
}
