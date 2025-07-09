<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Enumération des types d'erreurs rencontrées lors de l'envoi d'un email.
 *
 * Implémente l'interface FriendlyMessageInterface pour fournir
 * un message utilisateur lisible selon le type d'erreur.
 *
 * @package GenshinTeam\Utils
 */
enum MailErrorType implements FriendlyMessageInterface {

    /**
     * Échec de connexion au serveur SMTP.
     */
    case SMTP_CONNECT;

    /**
     * Adresse email invalide fournie.
     */
    case INVALID_ADDRESS;

    /**
     * Échec lors de l'envoi du message.
     */
    case MESSAGE_SEND;

    /**
     * Erreur inconnue ou inattendue.
     */
    case UNKNOWN;

    /**
     * Retourne un message lisible correspondant au type d'erreur.
     *
     * @return string Message destiné à l'utilisateur.
     */
    public function getMessage(): string
    {
        return match ($this) {
            self::SMTP_CONNECT => "Connexion au serveur d'envoi impossible. Vérifiez la configuration SMTP.",
            self::INVALID_ADDRESS => "L'adresse email fournie est invalide.",
            self::MESSAGE_SEND => "Le message n'a pas pu être envoyé. Veuillez réessayer.",
            self::UNKNOWN => "Une erreur inattendue est survenue lors de l'envoi du mail.",
        };
    }
}
