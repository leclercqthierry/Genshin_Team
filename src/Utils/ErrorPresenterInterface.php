<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Interface ErrorPresenterInterface
 *
 * Définit le contrat pour les classes responsables de l'affichage des erreurs à l'utilisateur final.
 * L’implémentation peut utiliser un moteur de rendu, un logger ou tout autre mécanisme
 * pour transformer un ErrorPayload en réponse visible.
 *
 * @package GenshinTeam\Utils
 */
interface ErrorPresenterInterface
{
    /**
     * Présente une erreur à l'utilisateur.
     *
     * Cette méthode doit convertir le contenu d'un ErrorPayload en sortie lisible,
     * qu'il s'agisse d'une page HTML ou d'une réponse JSON.
     *
     * @param ErrorPayload $payload Données décrivant l'erreur à afficher.
     *
     * @return void
     */
    public function present(ErrorPayload $payload): void;
}
