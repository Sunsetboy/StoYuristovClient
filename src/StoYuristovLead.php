<?php
declare(strict_types=1);

namespace StoYuristov;

class StoYuristovLead
{
    const TYPE_QUESTION = 1; // вопрос (по умолч.)
    const TYPE_CALL = 2; // запрос звонка

    public function __construct(
        private readonly string $name,
        private readonly string $phone,
        private readonly string $email,
        private readonly string $town,
        private readonly int    $type,
        private readonly string $question,
        private readonly ?int   $price = null,
    ) {

    }

    public function validate(): bool
    {
        return false;
    }
}
