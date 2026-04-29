<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'make:bounded-context', description: 'Generate the base structure of a DDD context')]
final class MakeBoundedContextCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'name',
            mode: InputArgument::REQUIRED,
            description: 'The name of the Bounded Context (e.g. Product, Order)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $srcDir = $this->projectDir.'/src/'.$name;
        $testsDir = $this->projectDir.'/tests';

        $this->io->title(sprintf('Generating Bounded Context: %s', $name));

        if ($this->filesystem->exists($srcDir)) {
            $this->io->error(sprintf('Bounded Context "%s" already exists.', $name));

            return Command::FAILURE;
        }

        $this->generateDomain($srcDir, $name);
        $this->generateApplication($srcDir, $name);
        $this->generateInfrastructure($srcDir, $name);
        $this->generateTests($testsDir, $name);
        $this->printNextSteps($name);

        $this->io->success(sprintf('Bounded Context "%s" generated successfully.', $name));

        return Command::SUCCESS;
    }

    private function generateDomain(string $srcDir, string $name): void
    {
        $this->io->section('Domain');

        $files = [
            // Entity
            "Domain/Entity/{$name}.php" => $this->templateEntity($name),

            // ValueObject
            "Domain/ValueObject/{$name}Id.php" => $this->templateValueObjectId($name),

            // Repository
            "Domain/Repository/{$name}RepositoryInterface.php" => $this->templateRepositoryInterface($name),

            // Event
            "Domain/Event/{$name}Created.php" => $this->templateDomainEvent($name, 'Created'),
            "Domain/Event/{$name}Updated.php" => $this->templateDomainEvent($name, 'Updated'),
            "Domain/Event/{$name}Deleted.php" => $this->templateDomainEvent($name, 'Deleted'),

            // Exception
            "Domain/Exception/{$name}NotFoundException.php"    => $this->templateNotFoundException($name),
            "Domain/Exception/{$name}AlreadyExistsException.php" => $this->templateAlreadyExistsException($name),
        ];

        $this->writeFiles($srcDir, $files);
    }

    private function generateApplication(string $srcDir, string $name): void
    {
        $this->io->section('Application');

        $files = [
            // Create
            "Application/Command/Create{$name}/Create{$name}Command.php"        => $this->templateCommand($name, 'Create'),
            "Application/Command/Create{$name}/Create{$name}CommandHandler.php" => $this->templateCommandHandler($name, 'Create'),

            // Update
            "Application/Command/Update{$name}/Update{$name}Command.php"        => $this->templateCommand($name, 'Update'),
            "Application/Command/Update{$name}/Update{$name}CommandHandler.php" => $this->templateCommandHandler($name, 'Update'),

            // Delete
            "Application/Command/Delete{$name}/Delete{$name}Command.php"        => $this->templateCommand($name, 'Delete'),
            "Application/Command/Delete{$name}/Delete{$name}CommandHandler.php" => $this->templateCommandHandler($name, 'Delete'),

            // Get one
            "Application/Query/Get{$name}/Get{$name}Query.php"         => $this->templateQuery($name),
            "Application/Query/Get{$name}/Get{$name}QueryHandler.php"  => $this->templateQueryHandler($name),
            "Application/Query/Get{$name}/{$name}Response.php"         => $this->templateResponse($name),

            // Get collection
            "Application/Query/Get{$name}s/Get{$name}sQuery.php"        => $this->templateCollectionQuery($name),
            "Application/Query/Get{$name}s/Get{$name}sQueryHandler.php" => $this->templateCollectionQueryHandler($name),
            "Application/Query/Get{$name}s/{$name}sResponse.php"        => $this->templateCollectionResponse($name),
            "Application/Query/Get{$name}s/{$name}ItemResponse.php"     => $this->templateItemResponse($name),
        ];

        $this->writeFiles($srcDir, $files);
    }

    private function generateInfrastructure(string $srcDir, string $name): void
    {
        $this->io->section('Infrastructure');

        $lower = strtolower($name);
        $table = $lower.'s';

        $files = [
            // Doctrine Mapping
            "Infrastructure/Persistence/Doctrine/Mapping/{$name}.orm.xml" => $this->templateDoctrineMapping($name, $table),

            // Doctrine Repository
            "Infrastructure/Persistence/Doctrine/Repository/Doctrine{$name}Repository.php" => $this->templateDoctrineRepository($name),

            // Doctrine Types
            "Infrastructure/Persistence/Doctrine/Type/{$name}IdType.php" => $this->templateDoctrineType($name),

            // Http Controllers
            "Infrastructure/Http/Controller/Create{$name}Controller.php"  => $this->templateController($name, 'Create', 'POST',   "/{$table}",      201),
            "Infrastructure/Http/Controller/Get{$name}Controller.php"     => $this->templateController($name, 'Get',    'GET',    "/{$table}/{id}", 200),
            "Infrastructure/Http/Controller/Get{$name}sController.php"    => $this->templateCollectionController($name, $table),
            "Infrastructure/Http/Controller/Patch{$name}Controller.php"   => $this->templateController($name, 'Patch',  'PATCH',  "/{$table}/{id}", 204),
            "Infrastructure/Http/Controller/Put{$name}Controller.php"     => $this->templateController($name, 'Put',    'PUT',    "/{$table}/{id}", 204),
            "Infrastructure/Http/Controller/Delete{$name}Controller.php"  => $this->templateController($name, 'Delete', 'DELETE', "/{$table}/{id}", 204),

            // Http Requests
            "Infrastructure/Http/Request/Create{$name}Request.php" => $this->templateRequest($name, 'Create'),
            "Infrastructure/Http/Request/Patch{$name}Request.php"  => $this->templateRequest($name, 'Patch'),
            "Infrastructure/Http/Request/Put{$name}Request.php"    => $this->templateRequest($name, 'Put'),

            // Fixture
            "Infrastructure/Fixture/{$name}Fixture.php" => $this->templateFixture($name),

            // Messaging
            "Infrastructure/Messaging/{$name}CreatedMessageHandler.php" => $this->templateMessageHandler($name),
        ];

        $this->writeFiles($srcDir, $files);
    }

    private function generateTests(string $testsDir, string $name): void
    {
        $this->io->section('Tests');

        $files = [
            // Unit
            "Unit/{$name}/Domain/Entity/{$name}Test.php"                          => $this->templateEntityTest($name),
            "Unit/{$name}/Domain/Mother/{$name}Mother.php"                        => $this->templateMother($name),
            "Unit/{$name}/Domain/Mother/{$name}IdMother.php"                      => $this->templateIdMother($name),
            "Unit/{$name}/Application/Create{$name}CommandHandlerTest.php"        => $this->templateCommandHandlerTest($name),

            // Integration
            "Integration/{$name}/Infrastructure/Doctrine{$name}RepositoryTest.php" => $this->templateRepositoryTest($name),
        ];

        $this->writeFiles($testsDir, $files);
    }

    /**
     * @param array<string, string> $files
     */
    private function writeFiles(string $baseDir, array $files): void
    {
        foreach ($files as $relativePath => $content) {
            $fullPath = $baseDir.'/'.$relativePath;
            $this->filesystem->dumpFile($fullPath, $content);
            $this->io->writeln(sprintf('  <info>created</info> %s', $relativePath));
        }
    }

    // =========================================================
    // Templates — Domain
    // =========================================================
    private function templateEntity(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Domain\\Entity;

        use App\\Shared\\Domain\\Bus\\Event\\DomainEvent;
        use App\\{$name}\\Domain\\Event\\{$name}Created;
        use App\\{$name}\\Domain\\Event\\{$name}Deleted;
        use App\\{$name}\\Domain\\Event\\{$name}Updated;
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;

        final class {$name}
        {
            /** @var DomainEvent[] */
            private array \$domainEvents = [];

            private function __construct(
                private readonly {$name}Id \$id,
                private \\DateTimeImmutable \$createdAt,
                private \\DateTimeImmutable \$updatedAt,
            ) {}

            public static function create({$name}Id \$id): self
            {
                \$now    = new \\DateTimeImmutable();
                \$entity = new self(\$id, \$now, \$now);

                \$entity->record(new {$name}Created(aggregateId: \$id->value()));

                return \$entity;
            }

            public function delete(): void
            {
                \$this->touch();
                \$this->record(new {$name}Deleted(aggregateId: \$this->id->value()));
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

            public function id(): {$name}Id                    { return \$this->id; }
            public function createdAt(): \\DateTimeImmutable { return \$this->createdAt; }
            public function updatedAt(): \\DateTimeImmutable { return \$this->updatedAt; }
        }
        PHP;
    }

    private function templateValueObjectId(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Domain\\ValueObject;

        use App\\Shared\\Domain\\ValueObject\\Uuid;

        final class {$name}Id extends Uuid {}
        PHP;
    }

    private function templateRepositoryInterface(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Domain\\Repository;

        use App\\Shared\\Domain\\Filter\\Filters;
        use App\\{$name}\\Domain\\Entity\\{$name};
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;

        interface {$name}RepositoryInterface
        {
            public function save({$name} \$entity): void;
            public function delete({$name} \$entity): void;
            public function findById({$name}Id \$id): ?{$name};
            public function findByFilters(Filters \$filters): array;
            public function countByFilters(Filters \$filters): int;
        }
        PHP;
    }

    private function templateDomainEvent(string $name, string $action): string
    {
        $eventName = strtolower($name).'.'.strtolower($action);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Domain\\Event;

        use App\\Shared\\Domain\\Bus\\Event\\DomainEvent;

        final class {$name}{$action} extends DomainEvent
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

    private function templateNotFoundException(string $name): string
    {
        $lower = strtolower($name);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Domain\\Exception;

        use App\\Shared\\Domain\\Exception\\NotFoundException;

        final class {$name}NotFoundException extends NotFoundException
        {
            public static function withId(string \$id): self
            {
                return new self(sprintf('{$name} with id "%s" was not found.', \$id));
            }

            public function errorCode(): string
            {
                return '{$lower}.not_found';
            }
        }
        PHP;
    }

    private function templateAlreadyExistsException(string $name): string
    {
        $lower = strtolower($name);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Domain\\Exception;

        use App\\Shared\\Domain\\Exception\\AlreadyExistsException;

        final class {$name}AlreadyExistsException extends AlreadyExistsException
        {
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

    private function templateCommand(string $name, string $action): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Command\\{$action}{$name};

        use App\\Shared\\Domain\\Bus\\Command\\Command;

        final class {$action}{$name}Command implements Command
        {
            public function __construct(
                public readonly string \$id,
            ) {}
        }
        PHP;
    }

    private function templateCommandHandler(string $name, string $action): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Command\\{$action}{$name};

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$name}\\Domain\\Exception\\{$name}NotFoundException;
        use App\\{$name}\\Domain\\Repository\\{$name}RepositoryInterface;
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'command.bus')]
        final class {$action}{$name}CommandHandler
        {
            public function __construct(
                private readonly {$name}RepositoryInterface \$repository,
                private readonly EventBusInterface          \$eventBus,
                private readonly LoggerInterface            \$logger,
            ) {}

            public function __invoke({$action}{$name}Command \$command): void
            {
                \$this->logger->info('{$action} {$name}', ['id' => \$command->id]);

                // TODO: implement

                \$this->logger->info('{$name} {$action}d', ['id' => \$command->id]);
            }
        }
        PHP;
    }

    private function templateQuery(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name};

        use App\\Shared\\Domain\\Bus\\Query\\Query;

        final class Get{$name}Query implements Query
        {
            public function __construct(
                public readonly string \$id,
            ) {}
        }
        PHP;
    }

    private function templateQueryHandler(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name};

        use App\\{$name}\\Domain\\Exception\\{$name}NotFoundException;
        use App\\{$name}\\Domain\\Repository\\{$name}RepositoryInterface;
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'query.bus')]
        final class Get{$name}QueryHandler
        {
            public function __construct(
                private readonly {$name}RepositoryInterface \$repository,
            ) {}

            public function __invoke(Get{$name}Query \$query): {$name}Response
            {
                \$entity = \$this->repository->findById({$name}Id::fromString(\$query->id));

                if (\$entity === null) {
                    throw {$name}NotFoundException::withId(\$query->id);
                }

                return new {$name}Response(\$entity);
            }
        }
        PHP;
    }

    private function templateResponse(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name};

        use App\\Shared\\Domain\\Bus\\Query\\Response;
        use App\\{$name}\\Domain\\Entity\\{$name};

        final class {$name}Response implements Response
        {
            public readonly string \$id;
            public readonly string \$createdAt;
            public readonly string \$updatedAt;

            public function __construct({$name} \$entity)
            {
                \$this->id        = \$entity->id()->value();
                \$this->createdAt = \$entity->createdAt()->format(\\DateTimeInterface::ATOM);
                \$this->updatedAt = \$entity->updatedAt()->format(\\DateTimeInterface::ATOM);
            }
        }
        PHP;
    }

    private function templateCollectionQuery(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name}s;

        use App\\Shared\\Domain\\Bus\\Query\\Query;
        use App\\Shared\\Domain\\Filter\\Filters;

        final class Get{$name}sQuery implements Query
        {
            public function __construct(
                public readonly Filters \$filters,
            ) {}
        }
        PHP;
    }

    private function templateCollectionQueryHandler(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name}s;

        use App\\{$name}\\Domain\\Repository\\{$name}RepositoryInterface;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'query.bus')]
        final class Get{$name}sQueryHandler
        {
            public function __construct(
                private readonly {$name}RepositoryInterface \$repository,
            ) {}

            public function __invoke(Get{$name}sQuery \$query): {$name}sResponse
            {
                \$entities = \$this->repository->findByFilters(\$query->filters);
                \$total    = \$this->repository->countByFilters(\$query->filters);

                return new {$name}sResponse(
                    items:   array_map(fn(\$e) => new {$name}ItemResponse(\$e), \$entities),
                    total:   \$total,
                    page:    \$query->filters->pagination->page,
                    perPage: \$query->filters->pagination->limit,
                );
            }
        }
        PHP;
    }

    private function templateCollectionResponse(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name}s;

        use App\\Shared\\Domain\\Bus\\Query\\Response;

        final class {$name}sResponse implements Response
        {
            public function __construct(
                public readonly array \$items,
                public readonly int   \$total,
                public readonly int   \$page,
                public readonly int   \$perPage,
            ) {}
        }
        PHP;
    }

    private function templateItemResponse(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Application\\Query\\Get{$name}s;

        use App\\Shared\\Domain\\Bus\\Query\\Response;
        use App\\{$name}\\Domain\\Entity\\{$name};

        final class {$name}ItemResponse implements Response
        {
            public readonly string \$id;
            public readonly string \$createdAt;

            public function __construct({$name} \$entity)
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
    private function templateDoctrineMapping(string $name, string $table): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping
            xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd"
        >
            <entity name="App\\{$name}\\Domain\\Entity\\{$name}" table="{$table}">

                <id name="id" type="{$this->toSnakeCase($name)}_id" column="id"/>

                <field name="createdAt" type="datetime_immutable" column="created_at" nullable="false"/>
                <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="false"/>

            </entity>
        </doctrine-mapping>
        XML;
    }

    private function templateDoctrineRepository(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Persistence\\Doctrine\\Repository;

        use App\\Shared\\Domain\\Filter\\Filters;
        use App\\Shared\\Infrastructure\\Persistence\\Doctrine\\DoctrineFilterApplier;
        use App\\{$name}\\Domain\\Entity\\{$name};
        use App\\{$name}\\Domain\\Repository\\{$name}RepositoryInterface;
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;
        use Doctrine\\ORM\\EntityManagerInterface;

        final class Doctrine{$name}Repository implements {$name}RepositoryInterface
        {
            public function __construct(
                private readonly EntityManagerInterface \$em,
            ) {}

            public function save({$name} \$entity): void
            {
                \$this->em->persist(\$entity);
                \$this->em->flush();
            }

            public function delete({$name} \$entity): void
            {
                \$this->em->remove(\$entity);
                \$this->em->flush();
            }

            public function findById({$name}Id \$id): ?{$name}
            {
                return \$this->em->find({$name}::class, \$id);
            }

            public function findByFilters(Filters \$filters): array
            {
                \$qb = \$this->em->getRepository({$name}::class)
                    ->createQueryBuilder('e');

                DoctrineFilterApplier::apply(\$qb, \$filters, 'e');

                return \$qb->getQuery()->getResult();
            }

            public function countByFilters(Filters \$filters): int
            {
                \$qb = \$this->em->getRepository({$name}::class)
                    ->createQueryBuilder('e')
                    ->select('COUNT(e.id)');

                return (int) \$qb->getQuery()->getSingleScalarResult();
            }
        }
        PHP;
    }

    private function templateDoctrineType(string $name): string
    {
        $typeName = $this->toSnakeCase($name).'_id';

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Persistence\\Doctrine\\Type;

        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;
        use Doctrine\\DBAL\\Platforms\\AbstractPlatform;
        use Doctrine\\DBAL\\Types\\Type;

        final class {$name}IdType extends Type
        {
            public const NAME = '{$typeName}';

            public function getSQLDeclaration(array \$column, AbstractPlatform \$platform): string
            {
                return 'UUID';
            }

            public function convertToPHPValue(mixed \$value, AbstractPlatform \$platform): ?{$name}Id
            {
                return \$value ? {$name}Id::fromString(\$value) : null;
            }

            public function convertToDatabaseValue(mixed \$value, AbstractPlatform \$platform): ?string
            {
                return \$value instanceof {$name}Id ? \$value->value() : \$value;
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

    private function templateController(string $name, string $action, string $method, string $route, int $status): string
    {
        $lower = strtolower($name);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Command\\CommandBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$name}\\Application\\Command\\{$action}{$name}\\{$action}{$name}Command;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('{$route}', methods: ['{$method}'])]
        final class {$action}{$name}Controller
        {
            public function __construct(
                private readonly CommandBusInterface \$commandBus,
                private readonly ApiResponse         \$apiResponse,
            ) {}

            public function __invoke(string \$id): JsonResponse
            {
                \$this->commandBus->dispatch(new {$action}{$name}Command(id: \$id));

                return \$this->apiResponse->noContent();
            }
        }
        PHP;
    }

    private function templateCollectionController(string $name, string $table): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Http\\Controller;

        use App\\Shared\\Domain\\Bus\\Query\\QueryBusInterface;
        use App\\Shared\\Infrastructure\\Http\\Filter\\FiltersBuilder;
        use App\\Shared\\Infrastructure\\Http\\Response\\ApiResponse;
        use App\\{$name}\\Application\\Query\\Get{$name}s\\Get{$name}sQuery;
        use App\\{$name}\\Application\\Query\\Get{$name}s\\{$name}sResponse;
        use Symfony\\Component\\HttpFoundation\\JsonResponse;
        use Symfony\\Component\\HttpFoundation\\Request;
        use Symfony\\Component\\Routing\\Attribute\\Route;

        #[Route('/{$table}', methods: ['GET'])]
        final class Get{$name}sController
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

                /** @var {$name}sResponse \$result */
                \$result = \$this->queryBus->ask(new Get{$name}sQuery(\$filters));

                return \$this->apiResponse->paginated(
                    data:    \$result->items,
                    total:   \$result->total,
                    page:    \$result->page,
                    perPage: \$result->perPage,
                );
            }
        }
        PHP;
    }

    private function templateRequest(string $name, string $action): string
    {
        $required = $action === 'Patch' ? 'false' : 'true';

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Http\\Request;

        use App\\Shared\\Infrastructure\\Http\\Request\\JsonRequest;

        final class {$action}{$name}Request extends JsonRequest
        {
            protected function rules(): array
            {
                return [
                    // 'field' => {$required},
                ];
            }
        }
        PHP;
    }

    private function templateFixture(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Fixture;

        use App\\{$name}\\Domain\\Entity\\{$name};
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;
        use Doctrine\\Bundle\\FixturesBundle\\Fixture;
        use Doctrine\\Persistence\\ObjectManager;

        final class {$name}Fixture extends Fixture
        {
            public const REFERENCE = '{$this->toSnakeCase($name)}.default';

            public function load(ObjectManager \$manager): void
            {
                \$entity = {$name}::create(id: {$name}Id::random());

                \$manager->persist(\$entity);
                \$manager->flush();

                \$this->addReference(self::REFERENCE, \$entity);
            }
        }
        PHP;
    }

    private function templateMessageHandler(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\{$name}\\Infrastructure\\Messaging;

        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\{$name}\\Domain\\Event\\{$name}Created;
        use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

        #[AsMessageHandler(bus: 'event.bus')]
        final class {$name}CreatedMessageHandler
        {
            public function __construct(
                private readonly LoggerInterface \$logger,
            ) {}

            public function __invoke({$name}Created \$event): void
            {
                \$this->logger->info('{$name} created event received', [
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

    private function templateEntityTest(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$name}\\Domain\\Entity;

        use App\\Tests\\Unit\\UnitTestCase;
        use App\\Tests\\Unit\\{$name}\\Domain\\Mother\\{$name}Mother;
        use App\\{$name}\\Domain\\Event\\{$name}Created;

        final class {$name}Test extends UnitTestCase
        {
            public function test_it_creates_a_{$this->toSnakeCase($name)}(): void
            {
                \$entity = {$name}Mother::create();
                \$events = \$entity->pullDomainEvents();

                \$this->assertCount(1, \$events);
                \$this->assertInstanceOf({$name}Created::class, \$events[0]);
            }

            public function test_it_clears_domain_events_after_pull(): void
            {
                \$entity = {$name}Mother::create();
                \$entity->pullDomainEvents();

                \$this->assertEmpty(\$entity->pullDomainEvents());
            }
        }
        PHP;
    }

    private function templateMother(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$name}\\Domain\\Mother;

        use App\\{$name}\\Domain\\Entity\\{$name};
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;

        final class {$name}Mother
        {
            public static function create(?{$name}Id \$id = null): {$name}
            {
                return {$name}::create(id: \$id ?? {$name}IdMother::random());
            }
        }
        PHP;
    }

    private function templateIdMother(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$name}\\Domain\\Mother;

        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;

        final class {$name}IdMother
        {
            public static function random(): {$name}Id
            {
                return {$name}Id::random();
            }

            public static function create(string \$value): {$name}Id
            {
                return {$name}Id::fromString(\$value);
            }
        }
        PHP;
    }

    private function templateCommandHandlerTest(string $name): string
    {
        $lower = $this->toSnakeCase($name);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Unit\\{$name}\\Application;

        use App\\Shared\\Domain\\Bus\\Event\\EventBusInterface;
        use App\\Shared\\Domain\\Logging\\LoggerInterface;
        use App\\Tests\\Unit\\UnitTestCase;
        use App\\{$name}\\Application\\Command\\Create{$name}\\Create{$name}Command;
        use App\\{$name}\\Application\\Command\\Create{$name}\\Create{$name}CommandHandler;
        use App\\{$name}\\Domain\\Repository\\{$name}RepositoryInterface;
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;

        final class Create{$name}CommandHandlerTest extends UnitTestCase
        {
            private {$name}RepositoryInterface \$repository;
            private EventBusInterface           \$eventBus;
            private LoggerInterface             \$logger;
            private Create{$name}CommandHandler \$handler;

            protected function setUp(): void
            {
                \$this->repository = \$this->createMock({$name}RepositoryInterface::class);
                \$this->eventBus   = \$this->createMock(EventBusInterface::class);
                \$this->logger     = \$this->createMock(LoggerInterface::class);

                \$this->handler = new Create{$name}CommandHandler(
                    \$this->repository,
                    \$this->eventBus,
                    \$this->logger,
                );
            }

            public function test_it_creates_a_{$lower}(): void
            {
                \$command = new Create{$name}Command(id: {$name}Id::random()->value());

                \$this->repository->expects(\$this->once())->method('save');
                \$this->eventBus->expects(\$this->once())->method('publish');

                (\$this->handler)(\$command);
            }
        }
        PHP;
    }


    private function templateRepositoryTest(string $name): string
    {
        $lower = $this->toSnakeCase($name);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Tests\\Integration\\{$name}\\Infrastructure;

        use App\\Tests\\Integration\\IntegrationTestCase;
        use App\\Tests\\Unit\\{$name}\\Domain\\Mother\\{$name}Mother;
        use App\\{$name}\\Domain\\ValueObject\\{$name}Id;
        use App\\{$name}\\Infrastructure\\Persistence\\Doctrine\\Repository\\Doctrine{$name}Repository;

        final class Doctrine{$name}RepositoryTest extends IntegrationTestCase
        {
            private Doctrine{$name}Repository \$repository;

            protected function setUp(): void
            {
                parent::setUp();
                \$this->repository = static::getContainer()->get(Doctrine{$name}Repository::class);
            }

            public function test_it_saves_and_finds_a_{$lower}(): void
            {
                \$entity = {$name}Mother::create();
                \$this->repository->save(\$entity);

                \$found = \$this->repository->findById(\$entity->id());

                \$this->assertNotNull(\$found);
                \$this->assertTrue(\$entity->id()->equals(\$found->id()));
            }

            public function test_it_returns_null_when_not_found(): void
            {
                \$this->assertNull(\$this->repository->findById({$name}Id::random()));
            }
        }
        PHP;
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function toSnakeCase(string $name): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
    }

    private function printNextSteps(string $name): void
    {
        $lower    = $this->toSnakeCase($name);
        $typeName = $lower.'_id';

        $this->io->section('Next steps');
        $this->io->listing([
            sprintf('Add Doctrine type in <info>config/packages/doctrine.yaml</info>: <comment>%s: App\\%s\\Infrastructure\\Persistence\\Doctrine\\Type\\%sIdType</comment>', $typeName, $name, $name),
            sprintf('Add Doctrine mapping in <info>config/packages/doctrine.yaml</info> under <comment>orm.mappings.%s</comment>', $name),
            sprintf('Add repository alias in <info>config/services.yaml</info>: <comment>App\\%s\\Domain\\Repository\\%sRepositoryInterface</comment>', $name, $name),
            sprintf('Add RabbitMQ binding key in <info>config/packages/messenger.yaml</info>: <comment>%s.#</comment>', $lower),
            'Add your fields to the entity, repository interface, and XML mapping',
            sprintf('Run <info>make db-diff</info> to generate the migration'),
        ]);
    }
}
