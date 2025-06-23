<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Objet représentant les données d'une erreur à transmettre à l'utilisateur.
 *
 * Cette classe encapsule un message lisible et un code HTTP associé,
 * utilisés notamment dans le rendu d'une page d'erreur ou d'une réponse API.
 *
 * @package GenshinTeam\Utils
 */
class ErrorPayload
{
    /**
     * @param string $message     Message destiné à l'affichage utilisateur.
     * @param int    $statusCode  Code de statut HTTP associé à l'erreur (par défaut : 500).
     */
    public function __construct(
        private string $message,
        private int $statusCode = 500
    ) {}

    /**
     * Retourne le message d'erreur.
     *
     * @return string Message à afficher.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Retourne le code de statut HTTP associé.
     *
     * @return int Code HTTP (ex. : 404, 500...).
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
