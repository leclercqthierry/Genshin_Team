<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Enum représentant les principaux types d’erreurs SQLSTATE liées à PDO.
 *
 * Chaque constante correspond à un code SQLSTATE standard et fournit
 * un message utilisateur explicite via l’interface {@see FriendlyMessageInterface}.
 *
 * @package GenshinTeam\Utils
 */
enum PdoErrorType: string implements FriendlyMessageInterface {
    /**
     * Violation de contrainte (unicité, clé étrangère, etc.) — SQLSTATE 23000.
     */
    case CONSTRAINT_VIOLATION = '23000';

    /**
     * Échec de connexion à la base de données — SQLSTATE 08001.
     */
    case CONNECTION_FAILED = '08001';

    /**
     * Erreur de syntaxe SQL ou commande mal formée — SQLSTATE 42000.
     */
    case SYNTAX_ERROR = '42000';

    /**
     * Timeout de la base — SQLSTATE HYT00.
     */
    case TIMEOUT = 'HYT00';

    /**
     * Type inconnu ou non mappé — utilisé par défaut.
     */
    case UNKNOWN = '????';

    /**
     * Retourne un message explicite à destination de l’utilisateur final
     * en fonction du type d’erreur PDO identifié.
     *
     * @return string Message compréhensible pour l’utilisateur.
     */
    public function getMessage(): string
    {
        return match ($this) {
            self::CONSTRAINT_VIOLATION => "Impossible d'enregistrer les données : elles violent une contrainte ou existent déjà.",
            self::CONNECTION_FAILED => "Connexion à la base de données impossible. Veuillez réessayer plus tard.",
            self::SYNTAX_ERROR => "Une erreur technique a été détectée lors du traitement des données.",
            self::TIMEOUT => "Le serveur de base de données met trop de temps à répondre.",
            self::UNKNOWN => "Une erreur inattendue liée à la base de données est survenue.",
        };
    }
}
