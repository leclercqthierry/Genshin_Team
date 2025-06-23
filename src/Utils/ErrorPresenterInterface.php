<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

interface ErrorPresenterInterface
{
    public function present(ErrorPayload $payload): void;
}
