<?php

declare(strict_types=1);

namespace App\Shared\Domain\Health;

final readonly class HealthCheckDetail
{
    public function __construct(
        public string $status,
        public int $durationMs,
        public ?string $detail = null,
    ) {
    }

    /**
     * @return array{status: string, duration_ms: int, detail?: string}
     */
    public function toArray(): array
    {
        $data = [
            'status' => $this->status,
            'duration_ms' => $this->durationMs,
        ];

        if (null !== $this->detail) {
            $data['detail'] = $this->detail;
        }

        return $data;
    }
}
