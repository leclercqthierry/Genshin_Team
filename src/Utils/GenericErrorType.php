<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Enum définissant les types d'erreurs génériques pouvant survenir côté applicatif.
 *
 * Chaque constante représente une catégorie d’erreur fréquente et propose
 * un message utilisateur explicite via l’interface {@see FriendlyMessageInterface}.
 *
 * @package GenshinTeam\Utils
 */
enum GenericErrorType: string implements FriendlyMessageInterface {
    /**
     * Erreur liée à une donnée mal formée ou absente.
     */
    case BAD_INPUT = 'Donnée invalide. Veuillez vérifier votre saisie.';

    /**
     * Anomalie logique détectée dans le traitement métier.
     */
    case LOGIC_FAILURE = 'Une incohérence a été détectée dans le traitement.';

    /**
     * Échec d’exécution lié à une erreur technique du serveur.
     */
    case SERVER_FAILURE = 'Une erreur technique empêche l’exécution de l’opération.';

    /**
     * Erreur inattendue ne correspondant à aucune catégorie prédéfinie.
     */
    case UNEXPECTED = 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.';

    /**
     * Retourne le message utilisateur associé à l'erreur générique.
     *
     * @return string Message compréhensible pour l'utilisateur final.
     */
    public function getMessage(): string
    {
        return $this->value;
    }
}
