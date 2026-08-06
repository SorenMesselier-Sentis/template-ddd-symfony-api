<?php

declare(strict_types=1);

namespace App\User\Application\Query\ExportUserData;

use App\Shared\Domain\Privacy\PersonalDataExporterInterface;
use App\User\Domain\Security\UserContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ExportUserDataQueryHandler
{
    /**
     * @param iterable<PersonalDataExporterInterface> $exporters
     */
    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly iterable $exporters,
    ) {
    }

    public function __invoke(ExportUserDataQuery $query): ExportUserDataResponse
    {
        $subjectId = $this->userContext->userId()->value();
        $data = [];

        foreach ($this->exporters as $exporter) {
            $data[$exporter->key()] = $exporter->export($subjectId);
        }

        return new ExportUserDataResponse($data, (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
    }
}
