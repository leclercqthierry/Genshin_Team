<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Implémentation factice d'ErrorPresenterInterface.
 *
 * Cette classe est utilisée principalement dans les tests automatisés ou les environnements
 * où l'affichage des erreurs n'est pas requis. Elle respecte l'interface sans effectuer
 * d'action concrète : elle absorbe silencieusement toute erreur.
 *
 * @package GenshinTeam\Utils
 */
class NullErrorPresenter implements ErrorPresenterInterface
{
    /**
     * Ne fait rien.
     *
     * Implémentation vide pour éviter toute sortie lors des tests.
     *
     * @param ErrorPayload $payload Payload d'erreur ignoré.
     *
     * @return void
     */
    public function present(ErrorPayload $payload): void
    {
        // Ne fait rien volontairement
    }
}
