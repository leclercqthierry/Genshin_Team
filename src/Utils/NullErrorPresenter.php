<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Pour les tests : n'émet rien du tout.
 */
class NullErrorPresenter implements ErrorPresenterInterface
{
    public function present(ErrorPayload $payload): void
    {
        // ne fait rien
    }
}
