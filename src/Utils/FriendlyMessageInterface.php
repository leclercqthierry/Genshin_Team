<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Interface contractuelle pour les types d’erreurs fournissant un message utilisateur lisible.
 *
 * Toute classe ou enum qui implémente cette interface doit être capable de
 * fournir un message explicite à destination de l’utilisateur final,
 * indépendamment de la nature technique de l’erreur.
 *
 * @package GenshinTeam\Utils
 */
interface FriendlyMessageInterface
{
    /**
     * Retourne un message clair et compréhensible pour l'utilisateur,
     * décrivant l'erreur sans détails techniques.
     *
     * @return string Message destiné à l’affichage utilisateur.
     */
    public function getMessage(): string;
}
