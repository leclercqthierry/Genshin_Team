<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

class ErrorPayload
{
    public function __construct(
        private string $message,
        private int $statusCode = 500
    ) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
