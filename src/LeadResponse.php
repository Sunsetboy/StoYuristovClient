<?php
declare(strict_types=1);

namespace StoYuristov;

class LeadResponse
{
    public function __construct(
        public readonly int $code,
        public readonly string $message,
        public readonly ?int $leadId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: (int) ($data['code'] ?? 0),
            message: (string) ($data['message'] ?? ''),
            leadId: isset($data['leadId']) ? (int) $data['leadId'] : null,
        );
    }
}
