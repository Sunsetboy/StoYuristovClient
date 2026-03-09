<?php
declare(strict_types=1);

namespace StoYuristov;

class StoYuristovLead
{
    const TYPE_QUESTION = 1; // вопрос (по умолч.)
    const TYPE_CALL = 2;     // запрос звонка

    public function __construct(
        private readonly string $name,
        private readonly string $phone,
        private readonly string $town,
        private readonly int $type,
        private readonly string $question,
        private readonly ?string $email = null,
        private readonly ?int $price = null,
        private readonly ?string $widgetUuid = null,
    ) {
    }

    /**
     * @return string[] List of validation error messages, empty array if valid
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->name === '') {
            $errors[] = 'Не указано имя';
        }
        if ($this->phone === '') {
            $errors[] = 'Не указан номер телефона';
        }
        if ($this->question === '') {
            $errors[] = 'Не указан текст вопроса';
        }
        if ($this->town === '') {
            $errors[] = 'Не указан город';
        }

        return $errors;
    }

    public function getName(): string { return $this->name; }
    public function getPhone(): string { return $this->phone; }
    public function getEmail(): ?string { return $this->email; }
    public function getTown(): string { return $this->town; }
    public function getType(): int { return $this->type; }
    public function getQuestion(): string { return $this->question; }
    public function getPrice(): ?int { return $this->price; }
    public function getWidgetUuid(): ?string { return $this->widgetUuid; }
}
